<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Description of ListContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListContacto extends ListController
{

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'contacts';
        $data['icon'] = 'fas fa-users';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewContacts();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewContacts(string $viewName = 'ListContacto')
    {
        $this->addView($viewName, 'Contacto', 'contacts', 'fas fa-users');
        $this->addSearchFields($viewName, ['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2', 'lastip']);
        $this->addOrderBy($viewName, ['email'], 'email');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['empresa'], 'company');
        $this->addOrderBy($viewName, ['puntos'], 'points');
        $this->addOrderBy($viewName, ['fechaalta'], 'creation-date', 2);

        /// filters
        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect($viewName, 'codpais', 'country', 'codpais', $countries);

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');
        $this->addFilterCheckbox($viewName, 'admitemarketing', 'allow-marketing', 'admitemarketing');
    }
}
