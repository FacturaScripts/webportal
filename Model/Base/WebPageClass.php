<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\webportal\Model\Base;

use FacturaScripts\Core\Model\Base\ModelClass;

/**
 * Description of WebPageClass
 *
 * @author Carlos García Gómez
 */
abstract class WebPageClass extends ModelClass
{

    /**
     * Creation date.
     *
     * @var string
     */
    public $creationdate;

    /**
     * Language code, in 2 characters,
     *
     * @var string
     */
    public $langcode;

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
     * Disable lastmod update.
     *
     * @var bool
     */
    public $lastmoddisable;

    /**
     * Position number.
     *
     * @var int
     */
    public $ordernum;

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
        $this->creationdate = date('d-m-Y H:i:s');
        $this->langcode = substr(FS_LANG, 0, 2);
        $this->lastmod = date('d-m-Y H:i:s');
        $this->ordernum = 100;
        $this->visitcount = 0;
    }

    /**
     * Increase visit counter and save. To improve performancem this will only happen every 2 or 10 times.
     * 
     * @param string $ipAddress
     *
     * @return bool
     */
    public function increaseVisitCount(string $ipAddress = '')
    {
        if ($ipAddress == $this->lastip) {
            return false;
        }

        $this->lastip = $ipAddress;
        $this->lastmoddisable = true;
        if ($this->visitcount < 100 && mt_rand(0, 1) == 0) {
            $this->visitcount += 2;
            $this->save();
        } elseif ($this->visitcount >= 100 && mt_rand(0, 9) === 0) {
            $this->visitcount += 10;
            $this->save();
        }

        $this->lastmoddisable = false;
        return true;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->lastmod = (true === $this->lastmoddisable) ? $this->lastmod : date('d-m-Y H:i:s');
        if (empty($this->creationdate)) {
            $this->creationdate = date('d-m-Y');
        }

        return parent::test();
    }
}
