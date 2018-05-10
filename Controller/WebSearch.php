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
namespace FacturaScripts\Plugins\webportal\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\WebBlock;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of WebSearch
 *
 * @author Carlos García Gómez
 */
class WebSearch extends PortalController
{

    /**
     *
     * @var string
     */
    public $query;

    /**
     *
     * @var array
     */
    public $searchResults = [];

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
        $pageData['icon'] = 'fa-search';

        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->initSearch();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->initSearch();
    }

    protected function initSearch()
    {
        $this->setTemplate('WebSearch');
        $this->query = $this->request->get('query', '');
        if (!empty($this->query)) {
            $this->search();
        }
    }

    protected function search()
    {
        $webPageModel = new WebPage();
        $wherePage = [
            new DataBaseWhere('description', $this->query, 'LIKE', 'OR'),
            new DataBaseWhere('title', $this->query, 'LIKE', 'OR'),
        ];
        foreach ($webPageModel->all($wherePage, ['visitcount' => 'DESC']) as $wpage) {
            $link = $wpage->url('link');
            $this->searchResults[$link] = [
                'icon' => 'fa-file-o',
                'title' => $wpage->title,
                'description' => $wpage->description,
                'link' => $link
            ];
        }

        $webBlockModel = new WebBlock();
        $whereBlock = [
            new DataBaseWhere('content', $this->query, 'LIKE')
        ];
        foreach ($webBlockModel->all($whereBlock) as $wblock) {
            $link = $wblock->url('link');
            if (empty($link)) {
                continue;
            }

            $this->searchResults[$link] = [
                'icon' => 'fa-file-o',
                'title' => $link,
                'description' => $wblock->content(true),
                'link' => $link
            ];
        }
    }
}
