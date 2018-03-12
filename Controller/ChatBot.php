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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\ChatBotMessage;

/**
 * Description of ChatBot
 *
 * @author Carlos García Gómez
 */
class ChatBot extends PortalController
{

    /**
     *
     * @var ChatBotMessage[]
     */
    public $messages = [];

    public function getHumanid()
    {
        if ($this->user) {
            return $this->user->nick;
        }

        if ($this->contact) {
            return $this->contact->email;
        }

        return $this->request->getClientIp();
    }

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'chatbot';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-commenting-o';

        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('ChatBot');
        $this->processChat();
        $this->getChatMessages();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('ChatBot');
        $this->processChat();
        $this->getChatMessages();
    }

    private function getChatMessages()
    {
        $chatBotMessage = new ChatBotMessage();
        $where = [new DataBaseWhere('humanid', $this->getHumanid())];
        $this->messages = $chatBotMessage->all($where, ['creationtime' => 'ASC']);
    }

    private function newChatMessage($content, $isChatbot = false)
    {
        $chatBotMessage = new ChatBotMessage();
        $chatBotMessage->content = $content;
        $chatBotMessage->humanid = $this->getHumanid();
        $chatBotMessage->ischatbot = $isChatbot;
        $chatBotMessage->save();
    }

    private function processChat()
    {
        $userInput = $this->request->request->get('question', '');
        if ('' !== $userInput) {
            $this->newChatMessage($userInput);
            $this->newChatMessage($userInput, true);
        }
    }
}
