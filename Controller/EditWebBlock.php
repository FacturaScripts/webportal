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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of EditWebBlock
 *
 * @author Carlos García Gómez
 */
class EditWebBlock extends ExtendedController\EditController
{

    /**
     * Returns the model name
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'WebBlock';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'web-block';
        $pagedata['menu'] = 'web';
        $pagedata['icon'] = 'fas fa-code';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'preview':
                $model = $this->views['EditWebBlock']->model;
                if ($model !== false && $model->idpage !== null) {
                    $webPage = new WebPage();
                    $webPage->loadFromCode($model->idpage);
                    $this->response->headers->set('Refresh', '0; ' . $webPage->url('public'));
                }
                break;

            default:
                parent::execAfterAction($action);
        }
    }
}
