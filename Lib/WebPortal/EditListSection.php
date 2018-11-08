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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\EditListView;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;

/**
 * Description of EditListSection
 *
 * @author Carlos García Gómez
 */
class EditListSection extends EditListView
{

    /**
     *
     * @var string
     */
    public $group;

    /**
     * 
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     * @param string $group
     */
    public function __construct($name, $title, $modelName, $icon, $group = '')
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->group = $group;
        $this->template = 'Section/EditListSection.html.twig';
    }

    /**
     * 
     * @param mixed $user
     */
    public function loadPageOptions($user = false)
    {
        $viewName = explode('-', $this->getViewName())[0];
        $where = [new DataBaseWhere('name', $viewName),];
        if (!$this->pageOption->loadFromCode('', $where)) {
            VisualItemLoadEngine::installXML($viewName, $this->pageOption);
        }

        VisualItemLoadEngine::setNamespace('\\FacturaScripts\\Dinamic\\Lib\\WebPortal\\Widget\\');
        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->pageOption);
    }

    protected function assets()
    {
        ;
    }
}
