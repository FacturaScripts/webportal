<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalRegisterMe
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class PortalRegisterMe extends PortalController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'webportal';
        $pageData['menu'] = 'registered-contacts';
        $pageData['icon'] = 'fa-user-plus';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('PortalRegisterMe');
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('PortalRegisterMe');

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'register':
                $this->registerContact();
                return false;
        }

        return true;
    }

    private function registerContact()
    {
        $contact = new Contacto();
        $email = $this->request->request->get('email');

        if ($contact->loadFromCode('', [new DataBaseWhere('email', $email)]) === false) {
            $userName = \explode('@', $email);
            preg_match_all('/[A-Za-z0-9_]/', $userName[0], $userName);
            $userName = \implode('', $userName[0]);
            $contact->nombre = !empty($this->request->request->get('name')) ? $this->request->request->get('name') : $userName;
            $contact->email = $email;
            $newPassword = $this->request->request->get('password');
            if ($newPassword !== null && $newPassword === $this->request->request->get('password2')) {
                $contact->setPassword($newPassword);
            }

            if ($contact->save()) {
                $this->updateCookies($contact, true);
                $homeUrl = AppSettings::get('webportal', 'url');
                $this->response->headers->set('Refresh', '0; ' . $homeUrl);
            } else {
                $this->miniLog->error(
                    $this->i18n->trans('new-contact-not-saved')
                );
            }
        } else {
            $this->miniLog->error(
                $this->i18n->trans('email-contact-already-used')
            );
        }
    }
}
