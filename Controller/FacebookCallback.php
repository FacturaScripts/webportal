<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\webportal\Controller;

require_once __DIR__ . '/../vendor/autoload.php';

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of FacebookCallback
 *
 * @author Carlos García Gómez
 */
class FacebookCallback extends PortalController
{

    private $facebook;

    /**
     * TODO
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'facebook-callback';
        $pageData['menu'] = 'web';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * TODO
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);

        if (!session_id()) {
            session_start();
        }

        $this->facebook = new Facebook([
            'app_id' => AppSettings::get('webportal', 'fbappid'),
            'app_secret' => AppSettings::get('webportal', 'fbappsecret'),
            'default_graph_version' => 'v2.12',
        ]);

        $errMessage = $this->request->query->get('error_message');
        if (empty($errMessage)) {
            $this->getFacebookCallback();
        } else {
            $this->miniLog->warning($errMessage);
        }
    }

    /**
     * TODO
     */
    private function getFacebookCallback()
    {
        $helper = $this->facebook->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch (FacebookResponseException $e) {
            $this->miniLog->critical('Graph returned an error: ' . $e->getMessage());
            return;
        } catch (FacebookSDKException $e) {
            $this->miniLog->critical('Facebook SDK returned an error: ' . $e->getMessage());
            return;
        }

        $response = $this->facebook->get('/me?fields=id, first_name, last_name, email', $accessToken);
        $userData = $response->getGraphNode()->asArray();
        $this->checkContact($userData);
    }

    /**
     * TODO
     *
     * @param $data
     */
    private function checkContact($data)
    {
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->miniLog->alert($this->i18n->trans('invalid-email'));
            return;
        }

        $contact = new Contacto();
        $where = [new DataBaseWhere('email', $data['email'])];
        if (!$contact->loadFromCode(null, $where)) {
            $contact->email = $data['email'];
            $contact->nombre = $data['first_name'];
            $contact->apellidos = $data['last_name'];
        }

        if ($contact->save()) {
            $this->contact = $contact;
            $this->updateCookies($this->contact, true);
            $this->response->headers->set('Refresh', '0; ' . AppSettings::get('webportal', 'url'));
        }
    }
}
