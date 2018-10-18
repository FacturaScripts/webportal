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

use FacturaScripts\Core\Lib\ExtendedController\HtmlView;

/**
 * Description of HtmlSection
 *
 * @author Carlos García Gómez
 */
class HtmlSection extends HtmlView
{

    /**
     *
     * @var string
     */
    public $group;

    public function __construct($name, $title, $modelName, $fileName, $icon, $group = '')
    {
        parent::__construct($name, $title, $modelName, $fileName, $icon);
        $this->group = $group;
    }
}
