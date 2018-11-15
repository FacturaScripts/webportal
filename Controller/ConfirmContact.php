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

use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of ConfirmContact
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ConfirmContact extends PortalController
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
        $this->response->headers->set('Refresh', '0; ' . 'EditProfile');
        $this->setTemplate(false);
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        if ($this->verifyContact()) {
            $url = empty(AppSettings::get('webportal', 'url')) ? 'EditProfile' : AppSettings::get('webportal', 'url');
            $this->response->headers->set('Refresh', '0; ' . $url);
        }

        $this->setTemplate(false);
    }

    /**
     * Verify the contact using the specify url
     *
     * @return bool
     */
    private function verifyContact()
    {
        $email = $this->request->get('email', '');
        if (empty($email)) {
            return false;
        }

        $contact = new Contacto();
        if($contact->loadFromCode('', [new DataBaseWhere('email', $email)])) {
            $contact->verificado = true;
            if($contact->save()) {
                $this->updateCookies($contact, true);
            } else {
                $this->miniLog->alert($this->i18n->trans('error-verificate-contact'));
                return false;
            }
        }

        return true;
    }
}
