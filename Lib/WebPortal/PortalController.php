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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Plugins\webportal\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalController
 *
 * @author Carlos García Gómez
 */
class PortalController extends Controller
{

    /**
     * Visitor language code.
     * 
     * @var string 
     */
    public $langcode2;

    /**
     * Web block to add to this page.
     * 
     * @var Model\WebBlock[]
     */
    public $webBlocks;

    /**
     * The web page object.
     * 
     * @var Model\WebPage 
     */
    public $webPage;

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

    /**
     * 
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->processWebPage();
    }

    /**
     * 
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->processWebPage();
    }

    private function getAuxMenu($where)
    {
        $menu = [];
        if ($this->langcode2 !== '') {
            $where[] = new DataBaseWhere('langcode', $this->langcode2);
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

    private function processWebPage()
    {
        $this->setTemplate('Public/PortalHome');

        $this->langcode2 = $this->request->get('langcode');
        if (empty($this->langcode2)) {
            foreach ($this->request->getLanguages() as $lang) {
                $this->langcode2 = substr($lang, 0, 2);
                break;
            }
        }

        $permalink = $this->request->get('permalink', 'home');
        if ($this->request->get('amp') !== null) {
            $this->setTemplate('Public/PortalHomeAMP');
        }

        $this->loadWebPage($permalink, $this->langcode2);
    }

    private function loadWebPage($permalink, $langcode = '')
    {
        $where = [new DataBaseWhere('permalink', $permalink),];
        if ($langcode !== '') {
            $where[] = new DataBaseWhere('langcode', $langcode);
        }

        $this->webBlocks = [];
        $this->webPage = new Model\WebPage();
        if ($this->webPage->loadFromCode('', $where)) {
            $webBlockModel = new Model\WebBlock();
            $whereBlocks = [
                new DataBaseWhere('idpage', $this->webPage->idpage, '=', 'OR'),
                new DataBaseWhere('type', 'head', '=', 'OR')
            ];
            $this->webBlocks = $webBlockModel->all($whereBlocks, ['posnumber' => 'ASC']);
        } elseif ($langcode !== '') {
            /// if fails, we try to load any page with the same permalink
            $this->loadWebPage($permalink);
        }
    }
}
