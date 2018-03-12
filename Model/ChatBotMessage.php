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
namespace FacturaScripts\Plugins\webportal\Model;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of ChatBotMessage
 *
 * @author Carlos García Gómez
 */
class ChatBotMessage extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Message content.
     *
     * @var string
     */
    public $content;

    /**
     * Creation time, in seconds.
     * 
     * @var int
     */
    public $creationtime;

    /**
     * Human identification.
     * 
     * @var string 
     */
    public $humanid;

    /**
     * Primary key.
     * 
     * @var int 
     */
    public $idchat;

    /**
     * To identify chatbot messages,
     * 
     * @var bool
     */
    public $ischatbot;

    public function clear()
    {
        parent::clear();
        $this->creationtime = time();
        $this->ischatbot = false;
    }

    public static function primaryColumn()
    {
        return 'idchat';
    }

    public static function tableName()
    {
        return 'chatbotmessages';
    }

    public function test()
    {
        $this->content = Utils::noHtml($this->content);
        return true;
    }
}
