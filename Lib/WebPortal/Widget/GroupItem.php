<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget;

use FacturaScripts\Core\Lib\Widget\GroupItem as ParentClass;

/**
 * Description of GroupItem
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class GroupItem extends ParentClass
{

    use VisualItemTrait;

    /**
     * 
     * @return string
     */
    protected function legend()
    {
        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        return '<div class="column">'
            . '<h4>' . $icon . static::$i18n->trans($this->title) . '</h4>'
            . '</div>'
            . '</div>'
            . '<div class="columns mb-2">';
    }
}
