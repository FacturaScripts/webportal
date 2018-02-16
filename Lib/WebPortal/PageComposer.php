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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Model\WebBlock;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of PageComposer
 *
 * @author Carlos García Gómez
 */
class PageComposer
{

    /**
     *
     * @var WebBlock[]
     */
    private $blocks;

    /**
     *
     * @var WebBlock
     */
    private $webBlock;

    public function __construct()
    {
        $this->blocks = [];
        $this->webBlock = new WebBlock();
    }

    public function getBlocks($type)
    {
        $blocks = [];
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    public function set(WebPage &$page)
    {
        $this->blocks = [];

        /// Page blocks for this page
        $where = [new DataBaseWhere('idpage', $page->idpage)];
        foreach ($this->webBlock->all($where, ['ordernum' => 'ASC'], 0, 0) as $block) {
            $this->addBlock($block, $page);
        }

        /// Page blocks for all pages
        $where2 = [new DataBaseWhere('idpage', null, 'IS')];
        foreach ($this->webBlock->all($where2, ['ordernum' => 'ASC'], 0, 0) as $block) {
            $this->addBlock($block, $page);
        }
    }

    private function addBlock(WebBlock $block, WebPage $page)
    {
        $container = 'container';
        switch ($block->type) {
            case 'body-container-fluid':
                $container .= 'fluid';
            /// no break
            case 'body-container':
                $block->type = 'body';
                $block->content = '<br/><div class="' . $container . '"><div class="row"><div class="col-12">'
                    . '<small>' . $page->lastmod . '</small>' . $block->content . '</div></div></div>';
                break;
        }

        $this->blocks[] = $block;
    }
}
