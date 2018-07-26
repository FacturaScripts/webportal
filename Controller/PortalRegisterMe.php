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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\GeoLocation;
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
        if ($contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $this->miniLog->error($this->i18n->trans('email-contact-already-used'));
            return;
        }

        $emailData = \explode('@', $email);
        $contact->nombre = empty($this->request->request->get('name')) ? $emailData[0] : $this->request->request->get('name');
        $contact->apellidos = $this->request->request->get('surname', '');
        $contact->descripcion = $this->request->request->get('description', $this->i18n->trans('my-address'));
        $contact->email = $email;
        $newPassword = $this->request->request->get('password', '');
        $newPassword2 = $this->request->request->get('password2', '');
        if (empty($newPassword) || $newPassword !== $newPassword2) {
            $this->miniLog->alert($this->i18n->trans('different-passwords', ['%userNick%' => $email]));
            return;
        }

        $contact->setPassword($newPassword);
        $this->setGeoIpData($contact);
        if ($contact->save()) {
            $this->updateCookies($contact, true);
            $url = empty(AppSettings::get('webportal', 'url')) ? 'EditProfile' : AppSettings::get('webportal', 'url');
            $this->response->headers->set('Refresh', '0; ' . $url);
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     */
    private function setGeoIpData(&$contact)
    {
        $ipAddress = $this->request->getClientIp() ?? '::1';
        $geoLocation = new GeoLocation();
        $geoLocation->setGeoIpData($contact, $ipAddress);
    }
}
