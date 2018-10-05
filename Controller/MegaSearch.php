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

use FacturaScripts\Core\Controller\MegaSearch as ParentController;
use FacturaScripts\Dinamic\Lib\WebPortal\SearchEngine;

/**
 * Description of MegaSearch
 *
 * @author Carlos García Gómez
 */
class MegaSearch extends ParentController
{

    protected function search()
    {
        parent::search();

        $searchEngine = new SearchEngine();
        $results = $searchEngine->search($this->query);

        if (!empty($results)) {
            $this->results['webportal'] = [
                'columns' => ['icon' => 'icon', 'title' => 'title', 'description' => 'description'],
                'icon' => 'fas fa-globe',
                'title' => 'webportal',
                'results' => $results,
            ];
        }
    }
}
