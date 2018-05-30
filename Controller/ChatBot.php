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

require_once __DIR__ . '/../vendor/autoload.php';

use DialogFlow\Client;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\ChatMessage;
use FacturaScripts\Plugins\webportal\Model\ChatSession;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Description of ChatBot
 *
 * @author Carlos García Gómez
 */
class ChatBot extends PortalController
{

    /**
     * All messages in current chat session.
     * 
     * @var ChatMessage[]
     */
    public $messages = [];

    /**
     *
     * @var ChatSession
     */
    public $session;

    /**
     * Returns basic page attributes
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
     * Runs the controller's private logic.
     *
     * @param \Symfony\Component\HttpFoundation\Response      $response
     * @param \FacturaScripts\Dinamic\Model\User              $user
     * @param \FacturaScripts\Core\Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('ChatBot');
        $this->getChatMessages();
        $this->processChat();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('ChatBot');
        $this->getChatMessages();
        $this->processChat();
    }

    protected function askDialogflow(string $token, string $userInput)
    {
        try {
            $client = new Client($token);
            $query = $client->get('query', [
                'query' => $userInput,
                'sessionId' => $this->session->idchat
            ]);

            $response = json_decode((string) $query->getBody(), true);
            $botMessage = $response['result']['fulfillment']['speech'] ?? '-';
            $unmatched = ($response['result']['action'] === 'input.unknown');
            $this->newChatMessage($userInput, $unmatched);
            $this->newChatMessage($botMessage, false, true);
        } catch (\Exception $error) {
            $this->newChatMessage($userInput, true);
            $this->newChatMessage($error->getMessage(), false, true);
            $this->miniLog->alert($error->getMessage());
        }
    }

    /**
     * Return all chat messages with this user.
     */
    protected function getChatMessages()
    {
        $chatMessage = new ChatMessage();
        $chatSession = $this->getChatSession();
        $where = [new DataBaseWhere('idchat', $chatSession->idchat)];
        $this->messages = array_reverse($chatMessage->all($where, ['creationtime' => 'DESC']));
    }

    protected function getChatSession()
    {
        if (isset($this->session)) {
            return $this->session;
        }

        $this->session = new ChatSession();
        $sessionId = $this->request->cookies->get('chatSessionId', '');
        if (!empty($sessionId) && $this->session->loadFromCode($sessionId)) {
            return $this->session;
        }

        if ($this->session->save()) {
            $expire = time() + self::PUBLIC_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('chatSessionId', $this->session->idchat, $expire));
        }

        return $this->session;
    }

    /**
     * Saves new chat message (answer or reply).
     *
     * @param string $content
     * @param bool   $unmatched
     * @param bool   $isChatbot
     */
    protected function newChatMessage(string $content, bool $unmatched = false, bool $isChatbot = false)
    {
        $chatMessage = new ChatMessage();
        $chatMessage->content = $content;
        $chatMessage->idchat = $this->session->idchat;
        $chatMessage->ischatbot = $isChatbot;
        $chatMessage->unmatched = $unmatched;

        if ($isChatbot) {
            $chatMessage->creationtime++;
        }

        if ($chatMessage->save()) {
            $this->messages[] = $chatMessage;
        }
    }

    /**
     * Process answer and reply.
     */
    protected function processChat()
    {
        $dfToken = AppSettings::get('webportal', 'dfclitoken', '');
        $userInput = $this->request->request->get('question', '');
        if ('' === $dfToken || '' === $userInput) {
            /// no token or message
            return;
        }

        /// anonymous comment. We check message limits.
        $maxAnonymousMsgs = AppSettings::get('webportal', 'dfmaxanonymous', '');
        if ('' === $maxAnonymousMsgs || count($this->messages) < (int) $maxAnonymousMsgs) {
            $this->askDialogflow($dfToken, $userInput);
            return;
        }

        $this->setTemplate('Master/LoginToContinue');
    }
}
