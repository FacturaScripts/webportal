<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\webportal\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Email\ButtonBlock;
use FacturaScripts\Core\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\GeoLocation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Hybridauth\Provider\Facebook;
use Hybridauth\Provider\Google;
use Hybridauth\Provider\Twitter;
use Hybridauth\User\Profile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of HybridLogin
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class HybridLogin extends PortalController
{

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('Master/LoginToContinue');

        if (!session_id()) {
            session_start();
        }

        /// we need to save url to return
        $return = $this->request->get('return', '');
        if ('' !== $return) {
            $_SESSION['hybridLoginReturn'] = $return;
        }

        $prov = $this->request->get('prov', '');
        switch ($prov) {
            case 'facebook':
                return $this->facebookLogin();

            case 'fs':
                return $this->contactLogin();

            case 'google':
                return $this->googleLogin();

            case 'recover':
                return $this->recoverAccount();

            case 'twitter':
                return $this->twitterLogin();

            default:
                $this->toolBox()->log()->warning('no-login-provider');
                $this->setIPWarning();
                break;
        }
    }

    /**
     * Check contact data and update if needed.
     */
    protected function checkContact(Profile $userProfile)
    {
        if (!isset($userProfile->email) || !filter_var($userProfile->email, FILTER_VALIDATE_EMAIL)) {
            $this->toolBox()->i18nLog()->warning('not-valid-email', ['%email%' => $userProfile->email]);
            $this->setIPWarning();
            return;
        }

        $contact = new Contacto();
        $where = [new DataBaseWhere('email', $userProfile->email)];
        if (!$contact->loadFromCode('', $where)) {
            $contact->email = $userProfile->email;
            $contact->nombre = $userProfile->firstName;
            $contact->apellidos = $userProfile->lastName;
        } elseif (!$contact->habilitado) {
            $this->toolBox()->i18nLog()->warning('email-disabled', ['%email%' => $contact->email]);
            $this->setIPWarning();
            return false;
        }

        $this->setGeoIpData($contact);
        if ($contact->save()) {
            $this->contact = $contact;
            $this->updateCookies($this->contact, true);
            $this->returnAfterLogin();
        }
    }

    /**
     * Manager FacturaScripts contact login.
     *
     * @return bool Returns false if fails, or return true and set headers to redirect.
     */
    protected function contactLogin(): bool
    {
        if ($this->toolBox()->appSettings()->get('webportal', 'allowlogincontacts', 'false') === 'false') {
            return false;
        }

        $email = \strtolower($this->request->request->get('fsContact', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->toolBox()->i18nLog()->warning('not-valid-email', ['%email%' => $email]);
            $this->setIPWarning();
            return false;
        }

        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->toolBox()->i18nLog()->warning('email-not-registered', ['%email%' => $email]);
            $this->setIPWarning();
            return false;
        } elseif (!$contact->habilitado) {
            $this->toolBox()->i18nLog()->warning('email-disabled', ['%email%' => $email]);
            $this->setIPWarning();
            return false;
        } elseif (!$contact->verificado) {
            $this->sendEmailConfirmation($contact);
            $this->toolBox()->i18nLog()->warning('activation-email-sent');
            $this->setIPWarning();
            return false;
        }

        /// Password forgotten?
        if ($this->request->request->get('fsContactPassForgot', '') === 'true') {
            $this->sendRecoveryMail($contact);
            return false;
        }

        $passwd = $this->request->request->get('fsContactPass', '');
        if ($contact->verifyPassword($passwd)) {
            $this->setGeoIpData($contact);
            $this->contact = $contact;
            $this->updateCookies($this->contact, true);
            $this->returnAfterLogin();
            return true;
        }

        $this->toolBox()->i18nLog()->warning('login-password-fail');
        $this->setIPWarning();
        return false;
    }

    /**
     * 
     * @return string
     */
    protected function defaultWebportalUrl()
    {
        return $this->toolBox()->appSettings()->get('webportal', 'url');
    }

    /**
     * Manager Facebook login
     */
    protected function facebookLogin()
    {
        $config = [
            'callback' => $this->defaultWebportalUrl() . '/HybridLogin?prov=facebook',
            'keys' => [
                'key' => $this->toolBox()->appSettings()->get('webportal', 'fbappid'),
                'secret' => $this->toolBox()->appSettings()->get('webportal', 'fbappsecret')
            ]
        ];

        try {
            $facebook = new Facebook($config);
            $facebook->authenticate();

            $userProfile = $facebook->getUserProfile();
            $this->checkContact($userProfile);
        } catch (Exception $exc) {
            $this->toolBox()->log()->warning($exc->getMessage());
            $this->setIPWarning();
        }
    }

    /**
     * 
     * @param Contacto $contact
     *
     * @return string
     */
    protected function getActivationCode($contact): string
    {
        return sha1($contact->idcontacto . $contact->password);
    }

    /**
     *
     * @param Contacto $contact
     *
     * @return string
     */
    protected function getContactRecoverykey($contact)
    {
        if (empty($contact->logkey)) {
            $contact->logkey = $this->toolBox()->utils()->randomString(99);
            $contact->save();
        }

        return rawurlencode(base64_encode($contact->logkey));
    }

    /**
     * Manage Google login
     */
    protected function googleLogin()
    {
        $config = [
            'callback' => $this->defaultWebportalUrl() . '/HybridLogin?prov=google',
            'keys' => [
                'key' => $this->toolBox()->appSettings()->get('webportal', 'googleappid'),
                'secret' => $this->toolBox()->appSettings()->get('webportal', 'googleappsecret')
            ]
        ];

        try {
            $google = new Google($config);
            $google->authenticate();

            $userProfile = $google->getUserProfile();
            $this->checkContact($userProfile);
        } catch (Exception $exc) {
            $this->toolBox()->log()->warning($exc->getMessage());
            $this->setIPWarning();
        }
    }

    /**
     * Try to recover access to the account from the link data sent by email.
     *
     * @return bool
     */
    protected function recoverAccount(): bool
    {
        /// Checks email
        $email = rawurldecode($this->request->get('email', ''));
        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)]) || !$contact->habilitado) {
            $this->toolBox()->i18nLog()->warning('email-not-registered', ['%email%' => $email]);
            $this->setIPWarning();
            return false;
        }

        /// key is ok?
        $recoveryKey = urldecode(base64_decode($this->request->get('key', '')));
        if ($contact->verifyLogkey($recoveryKey)) {
            $this->setGeoIpData($contact);
            $contact->verificado = true;
            if ($contact->save()) {
                $this->contact = $contact;
                $this->updateCookies($contact, true);
                return $this->sendEditProfile($this->defaultWebportalUrl());
            }

            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        $this->toolBox()->i18nLog()->warning('recovery-timed-out');
        $this->setIPWarning();
        return false;
    }

    protected function returnAfterLogin()
    {
        $return = empty($_SESSION['hybridLoginReturn']) ? $this->defaultWebportalUrl() : $_SESSION['hybridLoginReturn'];
        $this->redirect($return);
    }

    /**
     *
     * @param string $baseUrl
     *
     * @return bool
     */
    protected function sendEditProfile($baseUrl)
    {
        $this->toolBox()->i18nLog()->notice('recovered-access-go-to-account', ['%link%' => $baseUrl . '/EditProfile']);
        return true;
    }

    /**
     * Send and email with data posted from form.
     *
     * @param Contacto $contact
     *
     * @return bool
     */
    protected function sendEmailConfirmation($contact)
    {
        $i18n = $this->toolBox()->i18n();
        $link = $this->defaultWebportalUrl() . '/PortalRegisterMe?action=activate'
            . '&cod=' . $this->getActivationCode($contact)
            . '&email=' . rawurlencode($contact->email);

        $mail = new NewMail();
        $mail->fromName = $this->toolBox()->appSettings()->get('webportal', 'title');
        $mail->addAddress($contact->email);
        $mail->title = $i18n->trans('confirm-email');
        $mail->text = $i18n->trans('please-click-on-confirm-email');
        $mail->addMainBlock(new ButtonBlock($i18n->trans('confirm-email'), $link));
        return $mail->send();
    }

    /**
     * Send email with recovery link to the contact.
     *
     * @param Contacto $contact
     *
     * @return bool
     */
    protected function sendRecoveryMail($contact)
    {
        $i18n = $this->toolBox()->i18n();
        $link = $this->defaultWebportalUrl() . '/HybridLogin?prov=recover&email='
            . rawurlencode($contact->email) . '&key=' . $this->getContactRecoverykey($contact);

        $mail = new NewMail();
        $mail->fromName = $this->toolBox()->appSettings()->get('webportal', 'title');
        $mail->addAddress($contact->email);
        $mail->title = $i18n->trans('recover-your-account');
        $mail->text = $i18n->trans('recover-your-account-body', ['%link%' => $link]);
        $mail->addMainBlock(new ButtonBlock($i18n->trans('confirm-email'), $link));
        if ($mail->send()) {
            $this->toolBox()->i18nLog()->notice('recover-email-send-ok');
            return true;
        }

        $this->toolBox()->i18nLog()->critical('send-mail-error');
        return false;
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     */
    protected function setGeoIpData(&$contact)
    {
        /// we don't need update contact location if we already know
        if (!empty($contact->ciudad)) {
            return;
        }

        $ipAddress = $this->toolBox()->ipFilter()->getClientIp();
        $geoLocation = new GeoLocation();
        $geoLocation->setGeoIpData($contact, $ipAddress);
    }

    protected function setIPWarning()
    {
        $ipFilter = $this->toolBox()->ipFilter();
        $ipFilter->setAttempt($ipFilter->getClientIp());
    }

    /**
     * Manage Twitter login
     */
    protected function twitterLogin()
    {
        $config = [
            'callback' => $this->defaultWebportalUrl() . '/HybridLogin?prov=twitter',
            'keys' => [
                'key' => $this->toolBox()->appSettings()->get('webportal', 'twitterappid'),
                'secret' => $this->toolBox()->appSettings()->get('webportal', 'twitterappsecret')
            ],
            'includeEmail' => true
        ];

        try {
            $twitter = new Twitter($config);
            $twitter->authenticate();

            $userProfile = $twitter->getUserProfile();
            $this->checkContact($userProfile);
        } catch (Exception $exc) {
            $this->toolBox()->log()->warning($exc->getMessage());
            $this->setIPWarning();
        }
    }
}
