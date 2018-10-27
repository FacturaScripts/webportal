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

use FacturaScripts\Core\Lib\Widget\WidgetAutocomplete as ParentClass;

/**
 * Description of WidgetAutocomplete
 *
 * @author Carlos García Gómez
 */
class WidgetAutocomplete extends ParentClass
{

    use VisualItemTrait;

    /**
     *
     * @var array
     */
    protected static $allowed = [];

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        self::$allowed[] = [
            'source' => $this->source,
            'fieldcode' => $this->fieldcode,
            'fieldtitle' => $this->fieldtitle,
        ];
    }

    /**
     * Returns true if this search has been authorized.
     *
     * @param string $source
     * @param string $fieldcode
     * @param string $fieldtitle
     * 
     * @return bool
     */
    public static function allowed($source, $fieldcode, $fieldtitle)
    {
        foreach (self::$allowed as $line) {
            if ($source === $line['source'] && $fieldcode === $line['fieldcode'] && $fieldtitle === $line['fieldtitle']) {
                return true;
            }
        }

        return false;
    }

    /**
     * 
     * @return string
     */
    protected function inputGroupClearBtn()
    {
        return '<button class="btn btn-error input-group-btn" type="button" onclick="this.form.' . $this->fieldname . '.value = \'\'; this.form.submit();">'
            . '<i class="fas fa-times" aria-hidden="true"></i>'
            . '</button>';
    }
}
