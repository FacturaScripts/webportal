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
class WebBlock extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Block content.
     * 
     * @var string
     */
    public $content;

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
     * Position number.
     * 
     * @var int
     */
    public $ordernum;

    /**
     * Block type: body, meta, css, javascript, footer.
     * 
     * @var string
     */
    public $type;

    public static function tableName()
    {
        return 'webblocks';
    }

    public static function primaryColumn()
    {
        return 'idblock';
    }

    public function clear()
    {
        parent::clear();
        $this->content = 'Hello world!';
        $this->ordernum = 100;
        $this->type = 'bodyContainer';
    }

    public function test()
    {
        $this->content = Utils::noHtml($this->content);

        return true;
    }

    public function content()
    {
        return Utils::fixHtml($this->content);
    }
    
    public function url($type = 'auto', $list = 'List')
    {
        return parent::url($type, 'ListWebPage?active=List');
    }
}
