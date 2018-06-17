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

require_once __DIR__ . '/../vendor/autoload.php';

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DownloadTools;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Hybridauth\Provider\Facebook;
use Hybridauth\Provider\Google;
use Hybridauth\Provider\Twitter;
use Hybridauth\User\Profile;

/**
 * Description of HybridLogin
 *
 * @author Carlos García Gómez
 */
class HybridLogin extends PortalController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'hybrid-login';
        $pageData['menu'] = 'web';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Execute the public part of the controller.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);

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
     * Manager FacturaScripts contact login.
     *
     * @return bool Returns false if fails, or return true and set headers to redirect.
     */
    private function contactLogin(): bool
    {
        if (AppSettings::get('webportal', 'allowlogincontacts', 'false') === 'false') {
            return false;
        }

        $email = $this->request->request->get('fsContact', '');
        $passwd = $this->request->request->get('fsContactPass', '');
        if ($email !== '') {
            $contact = new Contacto();
            $where = [new DataBaseWhere('email', $email)];
            if ($contact->loadFromCode('', $where) && $contact->verifyPassword($passwd)) {
                $this->contact = $contact;
                $this->updateCookies($this->contact, true);
                $this->response->headers->set('Refresh', '0; ' . \FS_ROUTE);
                return true;
            }

            $this->miniLog->alert($this->i18n->trans('invalid-email-or-password'));
            return false;
        }

        $this->miniLog->alert($this->i18n->trans('invalid-email', ['%email%' => $email]));
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
     * Return details from IP Info DB as associative array.
     * Available fields: 'status', 'unknownField', 'ipAddress', 'countryCode', 'countryName', 'regionName', 'cityName',
     * 'zipCode', 'lat', 'long', 'timezone'.
     *
     * @return array
     */
    private function getGeoIpData(): array
    {
        $key = AppSettings::get('webportal', 'ipinfodbkey');
        if ($key === null) {
            return [];
        }

        $downloader = new DownloadTools();
        $reply = $downloader->getContents('http://api.ipinfodb.com/v3/ip-city/?key=' . $key);
        if ($reply === 'ERROR') {
            return [];
        }

        $data = \explode(';', $reply);
        return [
            'status' => $data[0],
            'unknownField' => $data[1],
            'ipAddress' => $data[2],
            'countryCode' => $data[3],
            'countryName' => $data[4],
            'regionName' => $data[5],
            'cityName' => $data[6],
            'zipCode' => $data[7],
            'lat' => $data[8],
            'long' => $data[9],
            'timezone' => $data[10],
        ];
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
     * Set string to field, truncated to max field length.
     * 
     * @param Contact $contact
     * @param string $field
     * @param string $string
     */
    private function setContactField(&$contact, string $field, string $string)
    {
        $size = (int) preg_replace('/[^0-9]/', '', $contact->getModelFields()[$field]['type']);
        if (\property_exists(\get_class($contact), $field)) {
            $contact->{$field} = \mb_strlen($string) > $size ? \substr($string, 0, $size - 3) . '...' : $string;
        }
    }

    /**
     * Set geoIP details to contact.
     * 
     * @param Contacto $contact
     */
    private function setGeoIpData(&$contact)
    {
        $this->miniLog->alert('yolo');
        
        $ipAddress = $this->request->getClientIp() ?? '::1';
        $excludedIp = ['127.0.0.1', '::1'];
        if ($contact !== null && !\in_array($ipAddress, $excludedIp, true)) {
            $data = $this->getGeoIpData();
            if (empty($data)) {
                return;
            }

            $this->setContactField($contact, 'ciudad', $data['cityName']);
            $this->setContactField($contact, 'provincia', $data['regionName']);
            $country = new Pais();
            if ($country->loadFromCode('', [new DataBaseWhere('codiso', $data['countryCode'])])) {
                $contact->codpais = $country->codpais;
            }
        }
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
