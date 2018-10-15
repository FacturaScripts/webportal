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

use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of SectionTest
 *
 * @author carlos
 */
class SectionTest extends SectionController
{

    protected function createSections()
    {
        $this->addListSection('ListProducto', 'Producto', 'products', 'fas fa-cubes');
        $this->addSearchOptions('ListProducto', ['referencia', 'descripcion']);
        $this->addOrderOption('ListProducto', ['referencia'], 'reference');

        $newButton = [
            'action' => 'EditProducto',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'level' => 1,
            'tag' => 'button',
            'type' => 'link',
        ];
        $this->addButton('ListProducto', $newButton);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect('ListProducto', 'codfamilia', 'family', 'codfamilia', $families);

        $this->addListSection('ListAsiento', 'Asiento', 'accounting-entries');
        $this->addSearchOptions('ListAsiento', ['numero', 'concepto']);
        $this->addOrderOption('ListAsiento', ['numero'], 'numero');

        $this->addListSection('ListFabricante', 'Fabricante', 'manufacturers', 'fas fa-columns', 'other');
        $this->addListSection('ListFamilia', 'Familia', 'families', 'fas fa-object-group', 'other');
    }

    protected function loadData(string $sectionName)
    {
        $this->sections[$sectionName]->loadData();
    }
}
