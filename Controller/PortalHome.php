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
namespace FacturaScripts\Plugins\webportal\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Model;

/**
 * Description of PortalHome
 *
 * @author Carlos García Gómez
 */
class PortalHome extends Controller
{

    /**
     * Visitor language code.
     * 
     * @var string 
     */
    public $langcode;

    /**
     *
     * @var WebPage 
     */
    public $webPage;

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'webportal';
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function getPublicMenu()
    {
        $where = [new DataBaseWhere('showonmenu', true)];
        return $this->getAuxMenu($where);
    }

    public function getPublicFooter()
    {
        $where = [new DataBaseWhere('showonfooter', true)];
        return $this->getAuxMenu($where);
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('Public/PortalHome');

        $this->langcode = $this->request->get('langcode');
        if(empty($this->langcode)) {
            foreach($this->request->getLanguages() as $lang) {
                $this->langcode = substr($lang, 0, 2);
                break;
            }
        }

        $permalink = $this->request->get('permalink', 'home');
        if ($this->request->get('amp') !== null) {
            $this->setTemplate('Public/PortalHomeAMP');
        }

        $this->loadWebPage($permalink, $this->langcode);
    }

    private function getAuxMenu($where)
    {
        $menu = [];
        if ($this->langcode !== '') {
            $where[] = new DataBaseWhere('langcode', $this->langcode);
        }

        $webPageModel = new Model\WebPage();
        foreach ($webPageModel->all($where, ['posnumber' => 'ASC']) as $webPage) {
            $menu[] = [
                'id' => $webPage->idpage,
                'title' => $webPage->title,
                'link' => $webPage->link()
            ];
        }

        return $menu;
    }

    private function loadWebPage($permalink, $langcode = '')
    {
        $where = [new DataBaseWhere('permalink', $permalink),];
        if ($langcode !== '') {
            $where[] = new DataBaseWhere('langcode', $langcode);
        }

        $this->webPage = new Model\WebPage();
        if (!$this->webPage->loadFromCode('', $where) && $langcode !== '') {
            /// if fails, we try to load any page with the same permalink
            $this->loadWebPage($permalink);
        }
    }
}
