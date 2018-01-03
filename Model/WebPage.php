<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\webportal\Model;

use FacturaScripts\Core\Model\Base;

/**
 * Description of WebPage
 *
 * @author Carlos García Gómez
 */
class WebPage
{
    use Base\ModelTrait;
    
    public $idpage;
    public $title;
    public $permalink;
    public $description;
    
    public function tableName()
    {
        return 'webpages';
    }
    
    public function primaryColumn()
    {
        return 'idpage';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . static::tableName() . " (title,description,permalink)"
            . " VALUES ('Home','Home','home');";
    }
}
