<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of MenuComposer
 *
 * @author Carlos García Gómez
 */
class MenuComposer
{

    /**
     *
     * @var WebPage
     */
    protected $webPage;

    public function __construct()
    {
        $this->webPage = new WebPage();
    }

    /**
     * Return cookies policy page.
     *
     * @return string
     */
    public function cookiesPage()
    {
        $where = [new DataBaseWhere('permalink', '/cookies')];
        foreach ($this->webPage->all($where) as $cookiePage) {
            return $cookiePage;
        }

        return $this->webPage;
    }

    /**
     * Return the page details.
     *
     * @param string $equivalence
     *
     * @return string
     */
    public function getPublicUrl(string $equivalence): string
    {
        $webPage = new WebPage();
        $where = [
            new DataBaseWhere('equivalentpage', $equivalence),
            new DataBaseWhere('langcode', $this->webPage->langcode)
        ];
        $webPage->loadFromCode('', $where);
        return \FS_ROUTE . $webPage->permalink;
    }

    /**
     * Return public footer.
     *
     * @return array
     */
    public function getFooterMenu()
    {
        $footer = [];
        $where = [new DataBaseWhere('showonfooter', true)];
        foreach ($this->getAuxMenu($where) as $wpage) {
            if (empty($wpage->menu)) {
                $footer[] = $wpage;
                continue;
            }

            $footer[$wpage->menu][] = $wpage;
        }

        return $footer;
    }

    /**
     * Return a list of pages clasified by lang code.
     *
     * @return array
     */
    public function getLanguageRoots()
    {
        $roots = [];
        $where = [new DataBaseWhere('showonmenu', true)];
        foreach ($this->getAuxMenu($where, false) as $wpage) {
            if (!isset($roots[$wpage->langcode])) {
                $roots[$wpage->langcode] = $wpage;
            }
        }

        return $roots;
    }

    /**
     * Return public menu.
     *
     * @return array
     */
    public function getTopMenu()
    {
        $menu = [];
        $where = [new DataBaseWhere('showonmenu', true)];
        foreach ($this->getAuxMenu($where) as $wpage) {
            if (empty($wpage->menu)) {
                $menu[] = $wpage;
                continue;
            }

            $menu[$wpage->menu][] = $wpage;
        }

        return $menu;
    }

    /**
     * Sets selected page on menu.
     *
     * @param WebPage $page
     */
    public function set(WebPage &$page)
    {
        $this->webPage = $page;
    }

    /**
     * Return auxiliar menu.
     *
     * @param array $where
     * @param bool  $filterLangcode
     *
     * @return WebPage[]
     */
    private function getAuxMenu(array $where, bool $filterLangcode = true)
    {
        if ($this->webPage && $filterLangcode) {
            $where[] = new DataBaseWhere('langcode', $this->webPage->langcode);
        }

        return $this->webPage->all($where, ['ordernum' => 'ASC', 'shorttitle' => 'ASC']);
    }
}
