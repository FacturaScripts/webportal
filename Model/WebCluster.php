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

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of WebCluster
 *
 * @author Carlos García Gómez
 */
class WebCluster extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Page description.
     *
     * @var string
     */
    public $description;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcluster;

    /**
     * Page title.
     *
     * @var string
     */
    public $title;

    /**
     * TODO
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webclusters';
    }

    /**
     * TODO
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcluster';
    }

    /**
     * TODO
     *
     * @return bool
     */
    public function test()
    {
        $this->description = mb_substr(Utils::noHtml($this->description), 0, 300);
        $this->title = Utils::noHtml($this->title);
        return true;
    }

    /**
     * TODO
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url($type = 'auto', $list = 'List')
    {
        return parent::url($type, 'ListWebPage?active=List');
    }
}
