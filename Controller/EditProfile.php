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

use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of EditProfile
 *
 * @author Carlos García Gómez
 */
class EditProfile extends SectionController
{

    /**
     * 
     * @return array
     */
    public function getCountries(): array
    {
        $pais = new Pais();
        return $pais->all([], ['nombre' => 'ASC'], 0, 0);
    }

    /**
     * Check if password if valid. If the user don´t write nothing, the password is the same and storage the rest of the changes.
     *
     * @return bool
     */
    protected function changedPassword(): bool
    {
        $password = $this->request->get('password', '');
        $repassword = $this->request->get('re-password', '');

        if ('' == $password && $repassword == '') {
            return true;
        }

        if ($password !== $repassword) {
            $this->miniLog->warning($this->i18n->trans('different-passwords', ['%userNick%' => $this->contact->email]));
            return false;
        }

        $this->contact->setPassword($password);
        return true;
    }

    /**
     * Storage the personal data 
     *
     * @return bool
     */
    protected function changedPersonalData()
    {
        $this->contact->nombre = $this->request->get('nombre', '');
        $this->contact->apellidos = $this->request->get('apellidos', '');
        $this->contact->direccion = $this->request->get('direccion', '');
        $this->contact->apartado = $this->request->get('apartado', '');
        $this->contact->codpostal = $this->request->get('codpostal', '');
        $this->contact->ciudad = $this->request->get('ciudad', '');
        $this->contact->provincia = $this->request->get('provincia', '');
        $this->contact->codpais = $this->request->get('codpais', '');
        return true;
    }

    /**
     * 
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('profile', 'profile', 'Section/EditProfile');
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'edit':
                if (!$this->contact->exists()) {
                    /// we must prevent from unauthorized contact creation
                    return true;
                }

                if ($this->changedPersonalData() && $this->changedPassword()) {
                    if ($this->contact->save()) {
                        $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                    } else {
                        $this->miniLog->alert($this->i18n->trans('record-save-error'));
                    }
                }
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }
}
