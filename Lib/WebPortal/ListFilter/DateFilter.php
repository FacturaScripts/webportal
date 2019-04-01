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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\ListFilter;

use FacturaScripts\Core\Lib\ListFilter\DateFilter as ParentFilter;

/**
 * Description of DateFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DateFilter extends ParentFilter
{

    public function render()
    {
        $label = static::$i18n->trans($this->label);
        return '<div class="column col-2 col-xs-12 mb-2">'
            . '<div class="form-group">'
            . '<div class="input-group">'
            . '<span class="input-group-addon" title="' . $label . '">'
            . '<i class="far fa-calendar-alt fa-fw" aria-hidden="true"></i>'
            . '</span>'
            . '<input type="text" name="' . $this->name() . '" value="' . $this->value . '" class="form-input datepicker"'
            . ' placeholder="' . $label . '" autocomplete="off"' . $this->onChange() . '/>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
