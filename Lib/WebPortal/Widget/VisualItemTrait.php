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

trait VisualItemTrait
{

    /**
     * 
     * @param string $class
     *
     * @return string
     */
    protected function css($class)
    {
        switch ($class) {
            case 'col':
                return 'column';

            case 'col-md-':
                return 'column col-';

            case 'form-control':
            case 'form-control-file':
                return 'form-input';

            case 'input-group-prepend':
                return 'input-group-addon';

            case 'row':
            case 'form-row':
                return 'columns mb-2';

            default:
                return $class;
        }
    }

    /**
     *
     * @param string $color
     * @param string $prefix
     *
     * @return string
     */
    protected function colorToClass($color, $prefix)
    {
        switch ($color) {
            case 'danger':
                return $prefix . 'error';

            case 'dark':
            case 'info':
            case 'light':
            case 'primary':
            case 'secondary':
            case 'success':
            case 'warning':
                return $prefix . $color;
        }

        return '';
    }
}
