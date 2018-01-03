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
        $menu = [];
        $webPageModel = new Model\WebPage();
        foreach ($webPageModel->all() as $webPage) {
            if(!$webPage->showonmenu) {
                continue;
            }
            
            $menu[] = [
                'id' => $webPage->idpage,
                'title' => $webPage->title,
                'url' => $this->url() . '&permalink=' . $webPage->permalink . '.html'
            ];
        }

        return $menu;
    }

    public function getPublicFooter()
    {
        $menu = [];
        $webPageModel = new Model\WebPage();
        foreach ($webPageModel->all() as $webPage) {
            if(!$webPage->showonfooter) {
                continue;
            }
            
            $menu[] = [
                'id' => $webPage->idpage,
                'title' => $webPage->title,
                'url' => $this->url() . '&permalink=' . $webPage->permalink . '.html'
            ];
        }

        return $menu;
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('Public/PortalHome');

        $permalink = $this->request->get('permalink', 'home');
        if (substr($permalink, -9) === '.amp.html') {
            $this->setTemplate('Public/PortalHomeAMP');
            $permalink = substr($permalink, 0, -9);
        } else if (substr($permalink, -5) === '.html') {
            $permalink = substr($permalink, 0, -5);
        }

        $this->loadWebPage($permalink);
    }

    private function loadWebPage($permalink)
    {
        $this->webPage = new Model\WebPage();
        $this->webPage->loadFromCode('', [new DataBaseWhere('permalink', $permalink)]);
    }
}
