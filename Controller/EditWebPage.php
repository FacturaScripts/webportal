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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\UpdateRoutes;

/**
 * Description of EditWebPage.
 *
 * @author Carlos García Gómez
 */
class EditWebPage extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'WebPage';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'web';
        $pageData['title'] = 'page';
        $pageData['icon'] = 'fas fa-globe';
        return $pageData;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addEditListView('EditWebBlock', 'WebBlock', 'blocks', 'fas fa-code');
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'preview':
                $model = $this->views['EditWebPage']->model;
                if ($model !== false) {
                    $this->redirect($model->url('public'));
                    UpdateRoutes::setRoutes();
                }
                if ($this->user->homepage !== 'PortalHome') {
                    $this->user->homepage = 'PortalHome';
                    $this->user->save();
                }
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if (!parent::execPreviousAction($action)) {
            return false;
        }

        $actions = ['edit', 'delete', 'insert'];
        if (in_array($action, $actions)) {
            UpdateRoutes::setRoutes();
        }

        return true;
    }

    /**
     * Load data view procedure.
     *
     * @param string   $keyView
     * @param BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditWebBlock':
                $idpage = $this->getViewModelValue('EditWebPage', 'idpage');
                $where = [new DataBaseWhere('idpage', $idpage)];
                $view->loadData('', $where, ['ordernum' => 'ASC']);
                break;

            default:
                parent::loadData($keyView, $view);
        }
    }
}
