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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Base;

/**
 * Description of WebPage
 *
 * @author Carlos García Gómez
 */
class WebPage
{

    use Base\ModelTrait {
        clear as traitClear;
    }

    const DEFAULT_CONTROLLER = 'PortalHome';

    /**
     * Custom controller to redir when clic on this link.
     * 
     * @var string 
     */
    public $customcontroller;

    /**
     * Page description.
     * 
     * @var string 
     */
    public $description;
    
    /**
     * Icon to use in menu.
     * 
     * @var string
     */
    public $icon;
    
    /**
     * Primary key.
     * 
     * @var int 
     */
    public $idpage;

    /**
     * Language code, in 2 characters,
     * 
     * @var string
     */
    public $langcode;

    /**
     * Permanent link.
     * 
     * @var string 
     */
    public $permalink;

    /**
     * Position number.
     * 
     * @var int
     */
    public $posnumber;
    
    /**
     * Short tittle to show on menu.
     * 
     * @var string
     */
    public $shorttitle;

    /**
     * Show link on menu.
     * 
     * @var bool 
     */
    public $showonmenu;

    /**
     * Show link on footer.
     * 
     * @var bool 
     */
    public $showonfooter;

    /**
     * Page title.
     * 
     * @var string
     */
    public $title;

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
        return 'INSERT INTO ' . static::tableName() . " (title,shorttitle,description,permalink,langcode)"
            . " VALUES ('Home','Home','Home','home','" . substr(FS_LANG, 0, 2) . "');";
    }

    public function clear()
    {
        $this->traitClear();
        $this->langcode = substr(FS_LANG, 0, 2);
        $this->posnumber = 100;
        $this->showonmenu = true;
        $this->showonfooter = true;
    }

    public function link()
    {
        $webPath = AppSettings::get('webportal', 'path', '');
        return $webPath . '/' . $this->langcode . '/' . $this->permalink;
    }

    public function internalLink()
    {
        $extra = '&langcode=' . $this->langcode;
        if ($this->customcontroller) {
            return 'index.php?page=' . $this->customcontroller . $extra;
        }

        return 'index.php?page=' . self::DEFAULT_CONTROLLER . '&permalink=' . $this->permalink . $extra;
    }

    public function test()
    {
        $this->description = self::noHtml($this->description);
        $this->icon = self::noHtml($this->icon);
        $this->permalink = self::noHtml($this->permalink);
        $this->title = self::noHtml($this->title);
        $this->shorttitle = self::noHtml($this->shorttitle);

        return true;
    }
}
