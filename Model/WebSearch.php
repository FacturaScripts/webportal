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
namespace FacturaScripts\Plugins\webportal\Model;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of WebSearch
 *
 * @author Carlos García Gómez
 */
class WebSearch extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Creation date.
     *
     * @var string
     */
    public $creationdate;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * IP from last visitor.
     *
     * @var string
     */
    public $lastip;

    /**
     * Last modification date.
     *
     * @var string
     */
    public $lastmod;

    /**
     *
     * @var int
     */
    public $numresults;

    /**
     * Query to search.
     *
     * @var string
     */
    public $query;

    /**
     * Visit counter.
     *
     * @var int
     */
    public $visitcount;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
        $this->lastmod = date('d-m-Y');
        $this->numresults = 0;
        $this->visitcount = 0;
    }

    /**
     * Increase visit counter and save. To improve performancem this will only happen every 2 or 10 times.
     */
    public function increaseVisitCount(string $ipAddress = '')
    {
        if ($ipAddress == $this->lastip) {
            return;
        }

        $this->lastip = $ipAddress;
        if ($this->visitcount < 100 && mt_rand(0, 1) == 0) {
            $this->visitcount += 2;
            $this->save();
        } elseif ($this->visitcount >= 100 && mt_rand(0, 9) === 0) {
            $this->visitcount += 10;
            $this->save();
        }
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'websearches';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->query = Utils::noHtml($this->query);
        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListWebPage?activetab=List')
    {
        $value = $this->query;
        $model = $this->modelClassName();
        switch ($type) {
            case 'edit':
                return 'WebSearch?code=' . $value;

            case 'list':
                return $list . $model;

            case 'new':
                return 'WebSearch';
        }

        /// default
        return empty($value) ? $list . $model : 'WebSearch?code=' . $value;
    }
}
