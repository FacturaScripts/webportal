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
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of WebPage
 *
 * @author Carlos García Gómez
 */
class WebPage extends Base\ModelClass
{

    use Base\ModelTrait;

    const DEFAULT_CONTROLLER = 'PortalHome';

    /**
     * Cluster name to add this page.
     *
     * @var string
     */
    public $cluster;

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
     * Icon to use in cluster.
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
     * Last modification date.
     *
     * @var string
     */
    public $lastmod;

    /**
     * Hide to search engines.
     *
     * @var bool
     */
    public $noindex;

    /**
     * Position number.
     *
     * @var int
     */
    public $ordernum;

    /**
     * Permanent link.
     *
     * @var string
     */
    public $permalink;

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

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webpages';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idpage';
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'permalink';
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
        return 'INSERT INTO ' . static::tableName() . " (title,shorttitle,description,"
            . "permalink,langcode,showonmenu,showonfooter,noindex,icon) VALUES "
            . "('Home','Home','Home description','/home','" . substr(FS_LANG, 0, 2) . "',true,false,false,'fa-file-o'),"
            . "('Cookies','Cookies','Cookies description','/cookies','" . substr(FS_LANG, 0, 2) . "',false,true,true,'fa-file-o'),"
            . "('Privacy','Privacy','Privacy description','/privacy','" . substr(FS_LANG, 0, 2) . "',false,true,true,'fa-file-o');";
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->icon = 'fa-file-o';
        $this->langcode = substr(FS_LANG, 0, 2);
        $this->lastmod = date('d-m-Y');
        $this->noindex = false;
        $this->ordernum = 100;
        $this->showonmenu = true;
        $this->showonfooter = true;
    }

    /**
     * Return default homepage link or permalink.
     *
     * @return string
     */
    public function link()
    {
        if ($this->idpage === AppSettings::get('webportal', 'homepage')) {
            return FS_ROUTE . '/';
        }

        return FS_ROUTE . $this->permalink;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cluster = Utils::noHtml($this->cluster);
        $this->description = str_replace("\n", ' ', $this->description);
        $this->description = mb_substr(Utils::noHtml($this->description), 0, 300);
        $this->permalink = Utils::noHtml($this->permalink);
        $this->title = Utils::noHtml($this->title);
        $this->shorttitle = Utils::noHtml($this->shorttitle);

        if ($this->langcode !== substr(FS_LANG, 0, 2) && substr($this->permalink, 0, 4) !== '/' . $this->langcode . '/') {
            $this->permalink = '/' . $this->langcode . '/' . $this->permalink;
        } elseif ($this->permalink[0] !== '/') {
            $this->permalink = '/' . $this->permalink;
        }

        $this->lastmod = date('d-m-Y');
        return true;
    }
}
