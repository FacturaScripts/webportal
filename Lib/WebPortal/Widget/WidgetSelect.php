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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget;

use FacturaScripts\Core\Lib\Widget\WidgetSelect as ParentClass;

/**
 * Description of WidgetSelect
 *
 * @author Carlos García Gómez
 */
class WidgetSelect extends ParentClass
{

    use VisualItemTrait;

    /**
     *
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        if ($this->readonly === 'true' || ($this->readonly === 'dinamic' && !empty($this->value))) {
            return parent::inputHtml($type, $extraClass);
        }

        $html = '<select name="' . $this->fieldname . '" class="form-select"' . $this->inputHtmlExtraParams() . '>';
        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            $selected = ($option['value'] == $this->value) ? ' selected="selected" ' : '';
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $title . '</option>';
        }

        $html .= '</select>';
        return $html;
    }
}
