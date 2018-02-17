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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of ListWebPage
 *
 * @author Carlos García Gómez
 */
class ListWebPage extends ExtendedController\ListController
{

    protected function createViews()
    {
        /// Web pages
        $this->addView('\FacturaScripts\Dinamic\Model\WebPage', 'ListWebPage', 'pages', 'fa-globe');
        $this->addSearchFields('ListWebPage', ['title', 'description']);
        $this->addOrderBy('ListWebPage', 'permalink');
        $this->addOrderBy('ListWebPage', 'title');
        $this->addOrderBy('ListWebPage', 'ordernum');

        /// Web blocks
        $this->addView('\FacturaScripts\Dinamic\Model\WebBlock', 'ListWebBlock', 'blocks', 'fa-code');
        $this->addSearchFields('ListWebBlock', ['content']);
        $this->addOrderBy('ListWebBlock', 'idblock', 'code');
        $this->addOrderBy('ListWebBlock', 'idpage');
        $this->addOrderBy('ListWebBlock', 'ordernum');
        
        /// Web clusters
        $this->addView('\FacturaScripts\Dinamic\Model\WebCluster', 'ListWebCluster', 'clusters', 'fa-newspaper-o');
        $this->addSearchFields('ListWebCluster', ['title','description']);
        $this->addOrderBy('ListWebCluster', 'title');
    }

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'pages';
        $pageData['menu'] = 'admin';
        $pageData['icon'] = 'fa-globe';

        return $pageData;
    }

    protected function execAfterAction($action)
    {
        $this->setPortalAsHome();
        parent::execAfterAction($action);
    }

    private function setPortalAsHome()
    {
        $appSettings = new AppSettings();
        if ($appSettings->get('default', 'homepage') !== 'PortalHome') {
            $appSettings->set('default', 'homepage', 'PortalHome');
            $appSettings->save();
        }
        
        /// set portal home page
        if ($appSettings->get('webportal', 'homepage') === null) {
            $appSettings->set('webportal', 'homepage', 1);
            $appSettings->save();
        }
    }
}
