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

require_once __DIR__ . '/../vendor/autoload.php';

use DialogFlow\Client;
use FacturaScripts\Core\App\AppSettings;
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

    /**
     * TODO
     *
     * @return null|string
     */
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

    /**
     * TODO
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'chatbot';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-commenting-o';

        return $pageData;
    }

    /**
     * TODO
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \FacturaScripts\Dinamic\Model\User $user
     * @param \FacturaScripts\Core\Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('ChatBot');
        $this->processChat();
        $this->getChatMessages();
    }

    /**
     * TODO
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('ChatBot');
        $this->processChat();
        $this->getChatMessages();
    }

    /**
     * TODO
     */
    private function getChatMessages()
    {
        $chatBotMessage = new ChatBotMessage();
        $where = [new DataBaseWhere('humanid', $this->getHumanid())];
        $this->messages = $chatBotMessage->all($where, ['creationtime' => 'DESC']);
    }

    /**
     * TODO
     *
     * @param string $content
     * @param bool $unmatched
     * @param bool $isChatbot
     */
    private function newChatMessage(string $content, bool $unmatched = false, bool $isChatbot = false)
    {
        $chatBotMessage = new ChatBotMessage();
        $chatBotMessage->content = $content;
        $chatBotMessage->humanid = $this->getHumanid();
        $chatBotMessage->ischatbot = $isChatbot;
        $chatBotMessage->unmatched = $unmatched;

        if ($isChatbot) {
            $chatBotMessage->creationtime++;
        }

        $chatBotMessage->save();
    }

    /**
     * TODO
     */
    private function processChat()
    {
        $userInput = $this->request->request->get('question', '');
        if ('' === $userInput) {
            return;
        }

        try {
            $client = new Client(AppSettings::get('webportal', 'dfclitoken'));
            $query = $client->get('query', [
                'query' => $userInput,
                'sessionId' => time()
            ]);

            $response = json_decode((string) $query->getBody(), true);
            $botMessage = isset($response['result']['fulfillment']['speech']) ? $response['result']['fulfillment']['speech'] : '-';
            $unmatched = ($response['result']['action'] === 'input.unknown');
            $this->newChatMessage($userInput, $unmatched);
            $this->newChatMessage($botMessage, $unmatched, true);
        } catch (\Exception $error) {
            $this->newChatMessage($userInput, true);
            $this->newChatMessage($error->getMessage(), true, true);
            $this->miniLog->alert($error->getMessage());
        }
    }
}
