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
 * @author carlos
 */
class EditProfile extends SectionController
{

    public function getGravatar(string $email, int $size = 80): string
    {
        return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=" . $size;
    }

    protected function createSections()
    {
        $this->addSection('plugin', ['fixed' => true, 'template' => 'Section/Profile']);
    }

    protected function execPreviousAction(string $action)
    {
        if ($action === 'edit') {
            $this->changedPersonalData();

            if($this->changedPassword()) {
                if ($this->contact->save()) {
                    $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                } else {
                    $this->miniLog->alert($this->i18n->trans('record-save-error'));
                }
            }
            return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Check if password if valid. If the user don´t write nothing, the password is the same and storage the rest of the changes.
     *
     * @return boolean
     */
    private function changedPassword() : bool
    {
        $password = $this->request->get('password', '');
        $repassword = $this->request->get('re-password', '');

        var_dump($password);
        var_dump($repassword);
        
        if ('' == $password && $repassword == '') {
            return true;
        }

        if ($password !== $repassword) {
            $this->miniLog->error($this->i18n->trans('different-passwords-to-contact-' . $this->contact->nombre));
            return false;
        }

        if (!$this->contact->verifyPassword($password)) {
            $this->miniLog->error($this->i18n->trans('not-can-update-password'));
            return false;
        }

        return true;
    }

    /**
     * Storage the personal data 
     *
     * @return void
     */
    private function changedPersonalData()
    {
        $this->contact->nombre = $this->request->get('nombre', '');
        $this->contact->apellidos = $this->request->get('apellidos', '');
        $this->contact->direccion = $this->request->get('direccion', '');
        $this->contact->apartado = $this->request->get('apartado', '');
        $this->contact->codpostal = $this->request->get('codpostal', '');
        $this->contact->ciudad = $this->request->get('ciudad', '');
        $this->contact->provincia = $this->request->get('provincia', '');
        $this->contact->codpais = $this->request->get('codpais', '');
    }

    public function getCountries() : array
    {
        $pais = new Pais();
        return $pais->all([],[],0,0);
    }

    protected function loadData(string $sectionName)
    {
        
    }
}
