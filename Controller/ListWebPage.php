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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Description of ListWebPage
 *
 * @author Carlos García Gómez
 */
class ListWebPage extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'web';
        $pageData['title'] = 'pages';
        $pageData['icon'] = 'fas fa-globe-americas';
        return $pageData;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// Web pages
        $this->createViewWebPages();

        /// Web blocks
        $this->createViewWebBlock();

        /// Searches
        $this->createViewWebSearch();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewWebBlock($viewName = 'ListWebBlock')
    {
        $this->addView($viewName, 'WebBlock', 'blocks', 'fas fa-code');
        $this->addSearchFields($viewName, ['content']);
        $this->addOrderBy($viewName, ['idblock'], 'code');
        $this->addOrderBy($viewName, ['idpage']);
        $this->addOrderBy($viewName, ['lastmod'], 'last-update', 2);
        $this->addOrderBy($viewName, ['ordernum']);

        $blockTypes = $this->codeModel->all('webblocks', 'type', 'type');
        $this->addFilterSelect($viewName, 'type', 'type', 'type', $blockTypes);

        $pages = $this->codeModel->all('webpages', 'idpage', 'permalink');
        $this->addFilterSelect($viewName, 'idpage', 'page', 'idpage', $pages);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewWebPages($viewName = 'ListWebPage')
    {
        $this->addView($viewName, 'WebPage', 'pages', 'fas fa-globe-americas');
        $this->addSearchFields($viewName, ['title', 'description']);
        $this->addOrderBy($viewName, ['permalink']);
        $this->addOrderBy($viewName, ['title']);
        $this->addOrderBy($viewName, ['equivalentpage'], 'equivalence');
        $this->addOrderBy($viewName, ['ordernum']);
        $this->addOrderBy($viewName, ['visitcount'], 'visit-counter');
        $this->addOrderBy($viewName, ['lastmod'], 'last-update');

        $langValues = $this->codeModel->all('webpages', 'langcode', 'langcode');
        $this->addFilterSelect($viewName, 'langcode', 'language', 'langcode', $langValues);
        $this->addFilterCheckbox($viewName, 'showonmenu', 'show-on-menu', 'showonmenu');
        $this->addFilterCheckbox($viewName, 'showonfooter', 'show-on-footer', 'showonfooter');
        $this->addFilterCheckbox($viewName, 'noindex', 'no-index', 'noindex');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewWebSearch($viewName = 'ListWebSearch')
    {
        $this->addView($viewName, 'WebSearch', 'searches', 'fas fa-search');
        $this->addSearchFields($viewName, ['query']);
        $this->addOrderBy($viewName, ['lastmod'], 'last-update', 2);
        $this->addOrderBy($viewName, ['visitcount'], 'visit-counter');
    }
}
