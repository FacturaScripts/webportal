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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\ListFilter;

use FacturaScripts\Core\Lib\ListFilter\AutocompleteFilter as ParentFilter;

/**
 * Description of AutocompleteFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AutocompleteFilter extends ParentFilter
{

    /**
     * 
     * @return string
     */
    public function render()
    {
        $label = static::$i18n->trans($this->label);
        $html = '<div class="column col-2 col-xs-12 mb-2">'
            . '<input type="hidden" name="' . $this->name() . '" value="' . $this->value . '"/>'
            . '<div class="form-group">'
            . '<div class="input-group">';

        if ('' === $this->value || null === $this->value) {
            $html .= '<span class="input-group-addon" title="' . $label . '">'
                . '<i class="fas fa-search fa-fw" aria-hidden="true"></i>'
                . '</span>';
        } else {
            $html .= '<button class="btn btn-error input-group-btn" type="button" onclick="this.form.' . $this->name() . '.value = \'\'; this.form.submit();" title="' . $label . '">'
                . '<i class="fas fa-times fa-fw" aria-hidden="true"></i>'
                . '</button>';
        }

        $html .= '<input type="text" value="' . $this->getDescription() . '" class="form-input filter-autocomplete"'
            . ' data-name="' . $this->name() . '" data-field="' . $this->field . '" data-source="' . $this->table . '" data-fieldcode="' . $this->fieldcode
            . '" data-fieldtitle="' . $this->fieldtitle . '" placeholder = "' . $label . '" autocomplete="off"/>'
            . '</div>'
            . '</div>'
            . '</div>';

        return $html;
    }
}
