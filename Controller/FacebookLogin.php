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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of FacebookLogin
 *
 * @author Carlos García Gómez
 */
class FacebookLogin extends PortalController
{

    private $facebook;

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'facebook-login';
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

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

        $this->login();
    }

    private function login()
    {
        $helper = $this->facebook->getRedirectLoginHelper();

        try {
            $permissions = ['email']; // Optional permissions
            $loginUrl = $helper->getLoginUrl(AppSettings::get('webportal', 'url') . '/FacebookCallback', $permissions);
            $this->response->headers->set('Refresh', '0; ' . $loginUrl);
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            $this->miniLog->critical('Graph returned an error: ' . $e->getMessage());
            return;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            $this->miniLog->critical('Facebook SDK returned an error: ' . $e->getMessage());
            return;
        }
    }
}
