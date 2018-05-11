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
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\WebBlock;
use FacturaScripts\Plugins\webportal\Model\WebPage;
use FacturaScripts\Plugins\webportal\Model\WebSearch as WebSearchModel;

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

    public function getCommonSearches()
    {
        $queries = [];
        $webSearch = new WebSearchModel();
        foreach ($webSearch->all([], ['visitcount' => 'DESC']) as $wsearch) {
            $queries[] = $wsearch->query;
        }

        return json_encode($queries);
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

    protected function addSearchResults(array $item)
    {
        if (isset($this->searchResults[$item['link']])) {
            return;
        }

        $item['position'] = stripos($item['title'] . ' ' . $item['description'], $this->query);
        $item['description'] = mb_substr($item['description'], 0, 300);
        $this->searchResults[$item['link']] = $item;
    }

    protected function initSearch()
    {
        $this->setTemplate('WebSearch');
        $this->query = $this->sanitizeSearch();
        if (mb_strlen($this->query) <= 2) {
            return;
        }

        /// load or create search query for statics
        $webSearch = new WebSearchModel();
        if (!$webSearch->loadFromCode($this->query)) {
            $webSearch->query = $this->query;
        }

        $webSearch->increaseVisitCount($this->request->getClientIp());
        $this->search();
        $this->sort();
    }

    /**
     * Returns the query without HTML or upper case characters.
     * 
     * @return string
     */
    protected function sanitizeSearch()
    {
        $query = $this->request->get('query', '');
        return Utils::noHtml(mb_strtolower($query));
    }

    /**
     * Add search results to list.
     */
    protected function search()
    {
        $webPageModel = new WebPage();
        $wherePage = [
            new DataBaseWhere('description', $this->query, 'LIKE', 'OR'),
            new DataBaseWhere('title', $this->query, 'LIKE', 'OR'),
        ];
        foreach ($webPageModel->all($wherePage, ['visitcount' => 'DESC']) as $wpage) {
            $link = $wpage->url('link');
            $this->addSearchResults([
                'icon' => 'fa-file-o',
                'title' => $wpage->title,
                'description' => $wpage->description,
                'link' => $link
            ]);
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

            $this->addSearchResults([
                'icon' => 'fa-file-o',
                'title' => $link,
                'description' => $wblock->content(true),
                'link' => $link
            ]);
        }
    }

    protected function sort()
    {
        /// we need maximum value of position
        $maxPosition = 0;
        foreach ($this->searchResults as $item) {
            if ($item['position'] > $maxPosition) {
                $maxPosition = $item['position'];
            }
        }

        /// add max position when position is FALSE
        foreach ($this->searchResults as $key => $value) {
            if (false === $value['position']) {
                $this->searchResults[$key]['position'] = $maxPosition;
            }
        }

        /// sort by position
        usort($this->searchResults, function($item1, $item2) {
            if ($item1['position'] == $item2['position']) {
                return 0;
            } else if ($item1['position'] > $item2['position']) {
                return 1;
            }

            return -1;
        });
    }
}
