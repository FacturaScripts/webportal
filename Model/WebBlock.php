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
class WebBlock
{

    use Base\ModelTrait {
        clear as traitClear;
        url as private traitUrl;
    }

    /**
     * Primary key.
     * 
     * @var int 
     */
    public $idblock;

    /**
     * Page related.
     * 
     * @var int 
     */
    public $idpage;

    /**
     * Block type: head, footer, columns.
     * 
     * @var string 
     */
    public $type;

    /**
     * Number of columns.
     * 
     * @var int
     */
    public $numcolumns;

    /**
     * Content of column 1.
     * 
     * @var string
     */
    public $column1;

    /**
     * Content of column 2.
     * 
     * @var string
     */
    public $column2;

    /**
     * Content of column 3.
     * 
     * @var string
     */
    public $column3;

    /**
     * Content of column 4.
     * 
     * @var string
     */
    public $column4;

    /**
     * Position number.
     * 
     * @var type 
     */
    public $posnumber;

    public function tableName()
    {
        return 'webblocks';
    }

    public function primaryColumn()
    {
        return 'idblock';
    }

    public function clear()
    {
        $this->traitClear();
        $this->type = 'columns';
        $this->numcolumns = 1;
        $this->column1 = 'Hello world!';
        $this->posnumber = 100;
    }

    public function test()
    {
        $this->column1 = self::noHtml($this->column1);
        $this->column2 = self::noHtml($this->column2);
        $this->column3 = self::noHtml($this->column3);
        $this->column4 = self::noHtml($this->column4);

        if ($this->numcolumns > 4) {
            $this->numcolumns = 4;
        } else if ($this->numcolumns < 1) {
            $this->numcolumns = 1;
        }

        if ($this->numcolumns < 4 || $this->type !== 'columns') {
            $this->column4 = '';
        }

        if ($this->numcolumns < 3 || $this->type !== 'columns') {
            $this->column3 = '';
        }

        if ($this->numcolumns < 2 || $this->type !== 'columns') {
            $this->column2 = '';
        }

        return true;
    }

    public function column1()
    {
        return self::fixHtml($this->column1);
    }

    public function column2()
    {
        return self::fixHtml($this->column2);
    }

    public function column3()
    {
        return self::fixHtml($this->column3);
    }

    public function column4()
    {
        return self::fixHtml($this->column4);
    }

    /**
     * Returns the url where to see/modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitUrl($type, 'ListWebPage&active=List');
    }
}
