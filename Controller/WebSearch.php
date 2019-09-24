<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Lib\WebPortal\SearchEngine;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\WebSearch as WebSearchModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of WebSearch
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WebSearch extends PortalController
{

    const MAX_TOP_QUERIES = 100;
    const MAX_WORD_DISTANCE = 3;

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
     *
     * @var array
     */
    public $similarQueries = [];

    /**
     *
     * @var array
     */
    public $topQueries = [];

    /**
     * 
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->initSearch();
    }

    /**
     * 
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->initSearch();
    }

    protected function initSearch()
    {
        $this->title = $this->toolBox()->i18n()->trans('search');
        $this->setTemplate('WebSearch');
        $this->setTopQueries();

        $this->query = $this->sanitizeSearch();
        if (mb_strlen($this->query) <= 2) {
            return;
        }

        $searchEngine = new SearchEngine();
        $this->searchResults = $searchEngine->search($this->query);

        /// load or create search query for statistics
        $webSearch = new WebSearchModel();
        if (!$webSearch->loadFromCode('', [new DataBaseWhere('query', $this->query)])) {
            $webSearch->query = $this->query;
        }
        $webSearch->numresults = count($this->searchResults);
        $webSearch->increaseVisitCount($this->toolBox()->ipFilter()->getClientIp());

        $this->setSimilarQueries();
    }

    /**
     * Returns the query without HTML or upper case characters.
     * 
     * @return string
     */
    protected function sanitizeSearch()
    {
        $code = $this->request->get('code', '');
        $query = $this->request->get('query', $code);
        return $this->toolBox()->utils()->noHtml(mb_strtolower($query, 'UTF8'));
    }

    /**
     * Adds to $this->similarQueries similar queries to $this->query
     */
    protected function setSimilarQueries()
    {
        foreach ($this->topQueries as $query) {
            $distance = levenshtein($this->query, $query);
            if ($distance > 0 && $distance <= self::MAX_WORD_DISTANCE) {
                $this->similarQueries[] = $query;
            }
        }
    }

    /**
     * Adds queries with more visits to $this->topQueries
     */
    protected function setTopQueries()
    {
        $webSearch = new WebSearchModel();
        $where = [new DataBaseWhere('numresults', 0, '>')];
        foreach ($webSearch->all($where, ['visitcount' => 'DESC'], 0, self::MAX_TOP_QUERIES) as $wsearch) {
            $this->topQueries[] = $wsearch->query;
        }
    }
}
