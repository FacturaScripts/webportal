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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalProfile
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class PortalProfile extends PortalController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'my-profile';
        $pageData['menu'] = 'contact-profile';
        $pageData['icon'] = 'fa-user-circle';
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
        $this->setTemplate('PortalProfile');
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }

        if (null === $this->contact) {
            $this->setTemplate('PortalRegisterMe');
            return;
        }
        $this->setTemplate('PortalProfile');
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'update-details':
                return $this->updateContact();
        }

        return true;
    }

    /**
     * Update contact details.
     */
    private function updateContact(): bool
    {
        $contact = new Contacto();
        $email = $this->request->request->get('email');

        if ($contact->loadFromCode('', [new DataBaseWhere('email', $email)]) && $contact->idcontacto === $this->contact->idcontacto) {
            $this->contact->nombre = $this->request->request->get('name');
            $this->contact->apellidos = $this->request->request->get('surname');
            $this->contact->email = $this->request->request->get('email');

            if ($this->contact->save()) {
                $this->miniLog->alert($this->i18n->trans('record-updated-correctly'));
                return true;
            }

            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return false;
        }

        $this->miniLog->error($this->i18n->trans('email-contact-already-used'));
        return false;
    }
}
