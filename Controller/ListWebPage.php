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
namespace FacturaScripts\Plugins\webportal\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of ListWebPage
 *
 * @author Carlos García Gómez
 */
class ListWebPage extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'pages';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-globe-americas';

        return $pageData;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// Web pages
        $this->addView('ListWebPage', 'WebPage', 'pages', 'fas fa-globe-americas');
        $this->addSearchFields('ListWebPage', ['title', 'description']);
        $this->addOrderBy('ListWebPage', ['permalink']);
        $this->addOrderBy('ListWebPage', ['title']);
        $this->addOrderBy('ListWebPage', ['equivalentpage'], 'equivalence');
        $this->addOrderBy('ListWebPage', ['ordernum']);
        $this->addOrderBy('ListWebPage', ['visitcount'], 'visit-counter');
        $this->addOrderBy('ListWebPage', ['lastmod'], 'last-update');

        $langValues = $this->codeModel->all('webpages', 'langcode', 'langcode');
        $this->addFilterSelect('ListWebPage', 'langcode', 'language', 'langcode', $langValues);
        $this->addFilterCheckbox('ListWebPage', 'showonmenu', 'show-on-menu', 'showonmenu');
        $this->addFilterCheckbox('ListWebPage', 'showonfooter', 'show-on-footer', 'showonfooter');
        $this->addFilterCheckbox('ListWebPage', 'noindex', 'no-index', 'noindex');

        /// Web blocks
        $this->createViewWebBlock();

        /// Searches
        $this->createViewWebSearch();
    }

    protected function createViewWebBlock()
    {
        $this->addView('ListWebBlock', 'WebBlock', 'blocks', 'fas fa-code');
        $this->addSearchFields('ListWebBlock', ['content']);
        $this->addOrderBy('ListWebBlock', ['idblock'], 'code');
        $this->addOrderBy('ListWebBlock', ['idpage']);
        $this->addOrderBy('ListWebBlock', ['ordernum']);

        $blockTypes = $this->codeModel->all('webblocks', 'type', 'type');
        $this->addFilterSelect('ListWebBlock', 'type', 'type', 'type', $blockTypes);

        $pages = $this->codeModel->all('webpages', 'idpage', 'permalink');
        $this->addFilterSelect('ListWebBlock', 'idpage', 'page', 'idpage', $pages);
    }

    protected function createViewWebSearch()
    {
        $this->addView('ListWebSearch', 'WebSearch', 'searches', 'fas fa-search');
        $this->addSearchFields('ListWebSearch', ['query']);
        $this->addOrderBy('ListWebSearch', ['lastmod'], 'last-update', 2);
        $this->addOrderBy('ListWebSearch', ['visitcount'], 'visit-counter');
    }
}
