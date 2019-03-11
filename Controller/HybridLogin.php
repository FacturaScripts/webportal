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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Dinamic\Lib\IPFilter;
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
 * @author Carlos García Gómez
 */
class HybridLogin extends PortalController
{

    /**
     *
     * @var IPFilter
     */
    protected $ipFilter;

    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->ipFilter = new IPFilter();
    }

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
                $this->miniLog->warning('no-login-provider');
                $this->ipFilter->setAttempt($this->request->getClientIp());
                break;
        }
    }

    /**
     * Check contact data and update if needed.
     */
    protected function checkContact(Profile $userProfile)
    {
        if (!isset($userProfile->email) || !filter_var($userProfile->email, FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->warning($this->i18n->trans('invalid-email', ['%email%' => $userProfile->email]));
            $this->ipFilter->setAttempt($this->request->getClientIp());
            return;
        }

        $contact = new Contacto();
        $where = [new DataBaseWhere('email', $userProfile->email)];
        if (!$contact->loadFromCode('', $where)) {
            $contact->email = $userProfile->email;
            $contact->nombre = $userProfile->firstName;
            $contact->apellidos = $userProfile->lastName;
        }

        $this->setGeoIpData($contact);
        if ($contact->save()) {
            $this->contact = $contact;
            $this->updateCookies($this->contact, true);

            $return = empty($_SESSION['hybridLoginReturn']) ? AppSettings::get('webportal', 'url') : $_SESSION['hybridLoginReturn'];
            $this->response->headers->set('Refresh', '0; ' . $return);
        }
    }

    /**
     * Manager FacturaScripts contact login.
     *
     * @return bool Returns false if fails, or return true and set headers to redirect.
     */
    protected function contactLogin(): bool
    {
        if (AppSettings::get('webportal', 'allowlogincontacts', 'false') === 'false') {
            return false;
        }

        $email = \strtolower($this->request->request->get('fsContact', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->warning($this->i18n->trans('not-valid-email', ['%email%' => $email]));
            $this->ipFilter->setAttempt($this->request->getClientIp());
            return false;
        }

        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->miniLog->warning($this->i18n->trans('email-not-registered'));
            $this->ipFilter->setAttempt($this->request->getClientIp());
            return false;
        }

        $passwd = $this->request->request->get('fsContactPass', '');
        if ($contact->verifyPassword($passwd)) {
            $this->setGeoIpData($contact);
            $this->contact = $contact;
            $this->updateCookies($this->contact, true);

            $return = empty($_SESSION['hybridLoginReturn']) ? AppSettings::get('webportal', 'url') : $_SESSION['hybridLoginReturn'];
            $this->response->headers->set('Refresh', '0; ' . $return);
            return true;
        }

        $this->miniLog->warning($this->i18n->trans('login-password-fail'));
        $this->ipFilter->setAttempt($this->request->getClientIp());

        /// Send email to contact with link
        $link = AppSettings::get('webportal', 'url') . '/HybridLogin?prov=recover&email='
            . urlencode($email) . '&key=' . $this->getContactRecoverykey($contact);

        $this->sendRecoveryMail($email, $link);
        return false;
    }

    /**
     * Manager Facebook login
     */
    protected function facebookLogin()
    {
        $config = [
            'callback' => AppSettings::get('webportal', 'url') . '/HybridLogin?prov=facebook',
            'keys' => [
                'key' => AppSettings::get('webportal', 'fbappid'),
                'secret' => AppSettings::get('webportal', 'fbappsecret')
            ]
        ];

        try {
            $facebook = new Facebook($config);
            $facebook->authenticate();

            $userProfile = $facebook->getUserProfile();
            $this->checkContact($userProfile);
        } catch (\Exception $exc) {
            $this->miniLog->error($exc->getMessage());
            $this->ipFilter->setAttempt($this->request->getClientIp());
        }
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
            $contact->logkey = Utils::randomString(99);
            $contact->save();
        }

        return urlencode(base64_encode($contact->logkey));
    }

    /**
     * Manage Google login
     */
    protected function googleLogin()
    {
        $config = [
            'callback' => AppSettings::get('webportal', 'url') . '/HybridLogin?prov=google',
            'keys' => [
                'key' => AppSettings::get('webportal', 'googleappid'),
                'secret' => AppSettings::get('webportal', 'googleappsecret')
            ]
        ];

        try {
            $google = new Google($config);
            $google->authenticate();

            $userProfile = $google->getUserProfile();
            $this->checkContact($userProfile);
        } catch (\Exception $exc) {
            $this->miniLog->error($exc->getMessage());
            $this->ipFilter->setAttempt($this->request->getClientIp());
        }
    }

    /**
     * Try to recover the contact account
     *
     * @return bool
     */
    protected function recoverAccount(): bool
    {
        /// Checks email
        $email = $this->request->get('email', '');
        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->ipFilter->setAttempt($this->request->getClientIp());
            return false;
        }

        $baseUrl = AppSettings::get('webportal', 'url');

        /// no key? then send email
        if (empty($this->request->get('key', ''))) {
            /// Send email to contact with link
            $link = $baseUrl . '/HybridLogin?prov=recover&email=' . urlencode($email)
                . '&key=' . $this->getContactRecoverykey($contact);

            return $this->sendRecoveryMail($email, $link);
        }

        /// key is ok?
        $recoveryKey = urldecode(base64_decode($this->request->get('key', '')));
        if ($contact->verifyLogkey($recoveryKey)) {
            $this->setGeoIpData($contact);
            if ($contact->save()) {
                $this->contact = $contact;
                $this->updateCookies($contact, true);
                return $this->sendEditProfile($baseUrl);
            }

            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return false;
        }

        $this->sendTimeOut($baseUrl);
        return false;
    }

    /**
     *
     * @param string $baseUrl
     *
     * @return bool
     */
    protected function sendEditProfile($baseUrl)
    {
        $this->miniLog->notice(
            $this->i18n->trans(
                'recovered-access-go-to-account', ['%link%' => $baseUrl . '/EditProfile']
            )
        );
        return true;
    }

    /**
     *
     * @param string $email
     * @param string $link
     *
     * @return bool
     */
    protected function sendRecoveryMail($email, $link)
    {
        $emailTools = new EmailTools();

        $mail = $emailTools->newMail();
        $mail->Subject = $this->i18n->trans('recover-your-account');
        $mail->addAddress($email);

        $params = [
            'body' => $this->i18n->trans('recover-your-account-body', ['%link%' => $link]),
            'company' => AppSettings::get('webportal', 'title'),
            'footer' => AppSettings::get('webportal', 'copyright'),
            'title' => $mail->Subject,
        ];
        $mail->msgHTML($emailTools->getTemplateHtml($params));
        if ($emailTools->send($mail)) {
            $this->miniLog->notice($this->i18n->trans('recover-email-send-ok'));
            return true;
        }

        $this->miniLog->critical($this->i18n->trans('send-mail-error'));
        return false;
    }

    /**
     *
     * @param string $baseUrl
     */
    protected function sendTimeOut($baseUrl)
    {
        $this->miniLog->alert($this->i18n->trans('recovery-timed-out', ['%link%' => $baseUrl . '/EditProfile']));
        $this->ipFilter->setAttempt($this->request->getClientIp());
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     */
    private function setGeoIpData(&$contact)
    {
        /// we don't need update contact location if we already know
        if (!empty($contact->ciudad)) {
            return;
        }

        $ipAddress = $this->request->getClientIp() ?? '::1';
        $geoLocation = new GeoLocation();
        $geoLocation->setGeoIpData($contact, $ipAddress);
    }

    /**
     * Manage Twitter login
     */
    protected function twitterLogin()
    {
        $config = [
            'callback' => AppSettings::get('webportal', 'url') . '/HybridLogin?prov=twitter',
            'keys' => [
                'key' => AppSettings::get('webportal', 'twitterappid'),
                'secret' => AppSettings::get('webportal', 'twitterappsecret')
            ],
            'includeEmail' => true
        ];

        try {
            $twitter = new Twitter($config);
            $twitter->authenticate();

            $userProfile = $twitter->getUserProfile();
            $this->checkContact($userProfile);
        } catch (\Exception $exc) {
            $this->miniLog->error($exc->getMessage());
            $this->ipFilter->setAttempt($this->request->getClientIp());
        }
    }
}
