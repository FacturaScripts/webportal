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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PageComposer;
use FacturaScripts\Plugins\webportal\Model;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalController class
 *
 * @author Carlos García Gómez
 */
class PortalController extends Controller
{

    /**
     * Public cookie expiration = 1 year.
     */
    const PUBLIC_COOKIES_EXPIRE = 31536000;

    /**
     * Period to update contact activity and cookies = 1 hour.
     */
    const PUBLIC_UPDATE_ACTIVITY_PERIOD = 3600;

    /**
     * The associated contact.
     *
     * @var Contacto
     */
    public $contact;

    /**
     * Page description.
     *
     * @var string
     */
    public $description;

    /**
     * The page composer.
     *
     * @var PageComposer
     */
    public $pageComposer;

    /**
     * If cookies policy needs to be showed to the user.
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

    /**
     * Return cookies policy page.
     *
     * @return string
     */
    public function cookiesPage()
    {
        foreach ($this->webPage->all([new DataBaseWhere('permalink', '/cookies')]) as $cookiePage) {
            return $cookiePage;
        }

        return $this->webPage;
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
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'web';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Return public footer.
     *
     * @return array
     */
    public function getPublicFooter()
    {
        $where = [new DataBaseWhere('showonfooter', true)];
        return $this->getAuxMenu($where);
    }

    /**
     * Return public menu.
     *
     * @return array
     */
    public function getPublicMenu()
    {
        $where = [new DataBaseWhere('showonmenu', true)];
        return $this->getAuxMenu($where);
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        /// loads contact
        $contact = new Contacto();
        if (!empty($this->user->email) && $contact->loadFromCode('', [new DataBaseWhere('email', $this->user->email)])) {
            $this->contact = $contact;
        }

        $this->processWebPage();
        $this->showCookiesPolicy = false;
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->contactAuth();
        $this->processWebPage();

        /// cookie policy check
        $this->showCookiesPolicy = true;
        if ('TRUE' === $this->request->request->get('okCookies', '')) {
            $expire = time() + self::PUBLIC_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('okCookies', time(), $expire));
            $this->showCookiesPolicy = false;
        } elseif ('' !== $this->request->cookies->get('okCookies', '')) {
            $this->showCookiesPolicy = false;
        }
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url()
    {
        return empty($this->webPage->permalink) ? parent::url() : $this->webPage->url('public');
    }

    /**
     * Authenticate the contact.
     *
     * @return bool
     */
    private function contactAuth()
    {
        if ('TRUE' === $this->request->query->get('public_logout')) {
            $this->response->headers->clearCookie('fsIdcontacto');
            $this->response->headers->clearCookie('fsLogkey');
            $this->contact = null;
            return false;
        }

        $idcontacto = $this->request->cookies->get('fsIdcontacto', '');
        if ($idcontacto === '') {
            return false;
        }

        $contacto = new Contacto();
        if ($contacto->loadFromCode($idcontacto)) {
            if ($contacto->verifyLogkey($this->request->cookies->get('fsLogkey'))) {
                $this->contact = $contacto;
                $this->updateCookies($this->contact);
                return true;
            }

            $this->miniLog->alert($this->i18n->trans('login-cookie-fail'));
            $this->response->headers->clearCookie('fsIdcontacto');
            return false;
        }

        $this->miniLog->alert($this->i18n->trans('login-contact-not-found'));
        return false;
    }

    /**
     * Return auxiliar menu.
     *
     * @param array $where
     * @param bool $filterLangcode
     *
     * @return Model\WebPage[]
     */
    private function getAuxMenu(array $where, bool $filterLangcode = true)
    {
        if ($this->webPage && $filterLangcode) {
            $where[] = new DataBaseWhere('langcode', $this->webPage->langcode);
        }

        $webPageModel = new Model\WebPage();
        return $webPageModel->all($where, ['ordernum' => 'ASC', 'shorttitle' => 'ASC']);
    }

    /**
     * Returns the webpage.
     *
     * @return Model\WebPage
     */
    protected function getWebPage()
    {
        $webPage = new Model\WebPage();

        /// show default page?
        if ($this->uri === '/' || $this->uri === '/index.php') {
            if ($webPage->loadFromCode(AppSettings::get('webportal', 'homepage'))) {
                return $webPage;
            }
        }

        /// perfect match?
        if ($webPage->loadFromCode('', [new DataBaseWhere('permalink', $this->uri)])) {
            return $webPage;
        }

        /// match with pages with * in permalink?
        foreach ($webPage->all([new DataBaseWhere('permalink', '*', 'LIKE')], [], 0, 0) as $wpage) {
            if (0 === strncmp($this->uri, $wpage->permalink, strlen($wpage->permalink) - 1)) {
                return $wpage;
            }
        }

        /// language root page?
        if (in_array(strlen($this->uri), [3, 4])) {
            foreach ($webPage->all([new DataBaseWhere('langcode', substr($this->uri, 1, 2))], [], 0, 0) as $wpage) {
                return $wpage;
            }
        }

        /// if no page found, then we use this page with noindex activated.
        $webPage->noindex = true;
        $webPage->title = $this->title;
        return $webPage;
    }

    /**
     * Process the web page.
     */
    protected function processWebPage()
    {
        $this->setTemplate('Master/PortalTemplate');
        $this->pageComposer = new PageComposer();
        $this->webPage = $this->getWebPage();

        $this->title = $this->webPage->title;
        $this->description = $this->webPage->description;

        if (null !== $this->webPage->idpage) {
            $ipAddress = $this->request->getClientIp() ?? '::1';
            $this->webPage->increaseVisitCount($ipAddress);
        }

        $this->pageComposer->set($this->webPage);
    }

    /**
     * Update contact cookies.
     *
     * @param Contacto $contact
     * @param bool     $force
     */
    protected function updateCookies(&$contact, bool $force = false)
    {
        if ($force || \time() - \strtotime($contact->lastactivity) > self::PUBLIC_UPDATE_ACTIVITY_PERIOD) {
            $contact->newLogkey($this->request->getClientIp());
            $contact->save();

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('fsIdcontacto', $contact->idcontacto, $expire));
            $this->response->headers->setCookie(new Cookie('fsLogkey', $contact->logkey, $expire));
        }
    }
}
