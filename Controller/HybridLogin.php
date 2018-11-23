<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\EmailTools;
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
                $this->facebookLogin();
                break;

            case 'google':
                $this->googleLogin();
                break;

            case 'twitter':
                $this->twitterLogin();
                break;

            case 'fs':
                $this->contactLogin();
                break;

            case 'recover':
                $this->recoverAccount();
                break;

            default:
                $this->miniLog->alert('no-login-provider');
                break;
        }
    }

    /**
     * Check contact data and update if needed.
     */
    private function checkContact(Profile $userProfile)
    {
        if (!isset($userProfile->email) || !filter_var($userProfile->email, FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->alert($this->i18n->trans('invalid-email', ['%email%' => $userProfile->email]));
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
     * Try to recover the contact account
     *
     * @return bool
     */
    private function recoverAccount(): bool
    {
        /// Checks email
        $email = $this->request->get('email', '');
        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            return false;
        }

        $baseUrl = AppSettings::get('webportal', 'url');

        /// no jey? then send email
        if (empty($this->request->get('key', ''))) {
            /// Send email to contact with link
            $logKey = urlencode(base64_encode($contact->logkey));
            $link = $baseUrl . '/HybridLogin?prov=recover&email=' . urlencode($email) . '&key=' . $logKey;
            $emailTools = new EmailTools();
            $mail = $emailTools->newMail();
            $mail->Subject = $this->i18n->trans('recover-your-account');
            $mail->addAddress($email);
            $mail->msgHTML($this->i18n->trans('recover-your-account-body', ['%link%' => $link]));
            if ($emailTools->send($mail)) {
                $this->miniLog->notice('send-mail-ok');
                return true;
            }

            $this->miniLog->critical('send-mail-error');
            return false;
        }

        /// key is ok?
        $logKey = urldecode(base64_decode($this->request->get('key', '')));
        if ($contact->verifyLogkey($logKey)) {
            $this->setGeoIpData($contact);
            if ($contact->save()) {
                $this->contact = $contact;
                $this->miniLog->notice(
                    $this->i18n->trans(
                        'recovered-access-go-to-account', ['%link%' => $baseUrl . '/EditProfile']
                    )
                );
                $this->updateCookies($contact, true);
                return true;
            }

            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return false;
        }

        $this->miniLog->alert($this->i18n->trans('recovery-timed-out', ['%link%' => $baseUrl . '/EditProfile']));
        return false;
    }

    /**
     * Manager FacturaScripts contact login.
     *
     * @return bool Returns false if fails, or return true and set headers to redirect.
     */
    private function contactLogin(): bool
    {
        if (AppSettings::get('webportal', 'allowlogincontacts', 'false') === 'false') {
            return false;
        }

        $email = \strtolower($this->request->request->get('fsContact', ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->alert($this->i18n->trans('not-valid-email', ['%email%' => $email]));
            return false;
        }

        $contact = new Contacto();
        if (!$contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->miniLog->alert($this->i18n->trans('email-not-registered'));
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

        $this->miniLog->alert($this->i18n->trans('login-password-fail'));

        $link = AppSettings::get('webportal', 'url') . '/HybridLogin?prov=recover&email=' . urlencode($email);
        $this->miniLog->info($this->i18n->trans('recover-your-account-access', ['%link%' => $link]));
        return false;
    }

    /**
     * Manager Facebook login
     */
    private function facebookLogin()
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
        }
    }

    /**
     * Manage Google login
     */
    private function googleLogin()
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
        }
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
    private function twitterLogin()
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
        }
    }
}
