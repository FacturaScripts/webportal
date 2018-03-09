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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PageComposer;
use FacturaScripts\Plugins\webportal\Model;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalController
 *
 * @author Carlos García Gómez
 */
class PortalController extends Controller
{

    /**
     *
     * @var PageComposer
     */
    public $pageComposer;

    /**
     *
     * @var bool
     */
    public $showCookiesPolicy = false;

    /**
     * The web page object.
     * 
     * @var Model\WebPage 
     */
    public $webPage;

    public function getPublicFooter()
    {
        $where = [new DataBaseWhere('showonfooter', true)];
        return $this->getAuxMenu($where);
    }

    public function getPublicMenu()
    {
        $where = [new DataBaseWhere('showonmenu', true)];
        return $this->getAuxMenu($where);
    }

    public function cookiesPolicy()
    {
        $html = '';
        foreach ($this->webPage->all([new DataBaseWhere('permalink', '/cookies')]) as $cookiePage) {
            $html = $cookiePage->description . ' <a href="' . $cookiePage->link() . '">'
                . $this->i18n->trans('read-more') . '</a> | <a href="?okCookies=TRUE">'
                . $this->i18n->trans('accept') . '</a>';
            break;
        }

        return $html;
    }

    /**
     * 
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->processWebPage();

        /// cookie check
        $this->showCookiesPolicy = true;
        if ('TRUE' === $this->request->query->get('okCookies', '')) {
            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('okCookies', time(), $expire));
            $this->showCookiesPolicy = false;
        } elseif ('' !== $this->request->cookies->get('okCookies', '')) {
            $this->showCookiesPolicy = false;
        }
    }

    /**
     * 
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->processWebPage();
    }

    private function getAuxMenu($where)
    {
        if ($this->webPage) {
            $where[] = new DataBaseWhere('langcode', $this->webPage->langcode);
        }

        $webPageModel = new Model\WebPage();
        return $webPageModel->all($where, ['ordernum' => 'ASC', 'shorttitle' => 'ASC']);
    }

    private function getWebPage()
    {
        $webPage = new Model\WebPage();

        if ($this->uri === '/' || $this->uri === 'index.php') {
            if ($webPage->loadFromCode(AppSettings::get('webportal', 'homepage'))) {
                return $webPage;
            }
        }

        $webPage->loadFromCode(false, [new DataBaseWhere('permalink', $this->uri)]);
        return $webPage;
    }

    private function processWebPage()
    {
        $this->setTemplate('Master/PortalTemplate');
        $this->pageComposer = new PageComposer();
        $this->webPage = $this->getWebPage();

        $this->pageComposer->set($this->webPage);
    }
}
