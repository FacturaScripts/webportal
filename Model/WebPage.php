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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;

/**
 * Description of WebPage
 *
 * @author Carlos García Gómez
 */
class WebPage extends WebPageClass
{

    use Base\ModelTrait;

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
     * Used to identify equivalent pages in different langagues.
     *
     * @var string
     */
    public $equivalentpage;

    /**
     * Icon to use in page.
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
     *
     * @var string
     */
    public $menu;

    /**
     * Hide to search engines.
     *
     * @var bool
     */
    public $noindex;

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
     * Show link on footer.
     *
     * @var bool
     */
    public $showonfooter;

    /**
     * Show link on menu.
     *
     * @var bool
     */
    public $showonmenu;

    /**
     * Page title.
     *
     * @var string
     */
    public $title;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->icon = 'fas fa-file';
        $this->menu = '';
        $this->noindex = false;
        $this->showonmenu = true;
        $this->showonfooter = true;
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webpages';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->description = str_replace("\n", ' ', $this->description);
        $this->description = mb_substr(Utils::noHtml($this->description), 0, 300);
        $this->permalink = Utils::noHtml($this->permalink);
        $this->title = Utils::noHtml($this->title);
        $this->shorttitle = empty($this->shorttitle) ? $this->title : Utils::noHtml($this->shorttitle);

        $homepage = $this->get(AppSettings::get('webportal', 'homepage'));
        if ((false !== $homepage) && $this->langcode !== $homepage->langcode && substr($this->permalink, 0, 4) !== '/' . $this->langcode . '/') {
            $this->permalink = '/' . $this->langcode . '/' . $this->permalink;
        } elseif ($this->permalink[0] !== '/') {
            $this->permalink = '/' . $this->permalink;
        }

        return parent::test();
    }

    /**
     * Returns url to list or edit this model.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        switch ($type) {
            case 'public':
                /// don't use ===
                if ($this->idpage == AppSettings::get('webportal', 'homepage')) {
                    return FS_ROUTE . '/';
                }

                if ('*' === substr($this->permalink, -1)) {
                    return FS_ROUTE . substr($this->permalink, 0, -1);
                }

                return FS_ROUTE . $this->permalink;

            default:
                return parent::url($type, $list);
        }
    }
}
