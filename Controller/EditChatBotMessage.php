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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Description of EditChatBotMessage
 *
 * @author Carlos García Gómez
 */
class EditChatBotMessage extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'chat-message';
        $pageData['menu'] = 'web';
        $pageData['showonmenu'] = false;
        $pageData['icon'] = 'fa-commenting-o';

        return $pageData;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('ChatBotMessage', 'EditChatBotMessage', 'chat-message', 'fa-commenting-o');
        $this->addListView('ChatBotMessage', 'ListChatBotMessage', 'chat-messages', 'fa-comments');
        
        $this->views['ListChatBotMessage']->disableColumn('humanid', true);
    }

    /**
     * Load data view procedure
     *
     * @param string $keyView
     * @param ExtendedController\BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditChatBotMessage':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListChatBotMessage':
                $humanid = $this->getViewModelValue('EditChatBotMessage', 'humanid');
                $view->loadData(false, [new DataBaseWhere('humanid', $humanid)], ['creationtime' => 'DESC']);
                break;
        }
    }
}
