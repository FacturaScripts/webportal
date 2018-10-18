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
     * List of web blocks.
     * 
     * @var WebBlock[]
     */
    private $blocks;

    /**
     * A web block.
     * 
     * @var WebBlock
     */
    private $webBlock;

    /**
     * PageComposer constructor.
     */
    public function __construct()
    {
        $this->blocks = [];
        $this->webBlock = new WebBlock();
    }

    /**
     * Return blocks for a specific type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getBlocks(string $type): array
    {
        $blocks = [];
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    /**
     * Sets the page to get blocks from.
     *
     * @param WebPage $page
     */
    public function set(WebPage &$page)
    {
        $this->blocks = [];

        if (null !== $page) {
            /// Page blocks for this page
            $where = [new DataBaseWhere('idpage', $page->idpage)];
            foreach ($this->webBlock->all($where, ['ordernum' => 'ASC'], 0, 0) as $block) {
                $this->addBlock($block, $page);
            }
        }

        /// Page blocks for all pages
        $where2 = [new DataBaseWhere('idpage', null, 'IS')];
        foreach ($this->webBlock->all($where2, ['ordernum' => 'ASC'], 0, 0) as $block) {
            $this->addBlock($block, $page);
        }

        $this->checkBody($page);
    }

    /**
     * Add blocks to a page.
     *
     * @param WebBlock $block
     * @param WebPage  $page
     */
    private function addBlock(WebBlock $block, WebPage $page)
    {
        switch ($block->type) {
            case 'body-container':
            case 'bodyContainer':
                $block->type = 'body';
                $block->content = $this->getHtmlContainer($block->content);
                break;
        }

        $this->blocks[] = $block;
    }

    /**
     * Check body of a page.
     *
     * @param WebPage $page
     */
    private function checkBody(WebPage &$page)
    {
        $bodyFound = false;
        $title = '';
        foreach ($this->blocks as $block) {
            if ('body' === substr($block->type, 0, 4)) {
                $bodyFound = true;
                $title = $this->getH1Ttitle($block->content());
                break;
            }
        }

        if ($bodyFound) {
            if ($title !== '' && $page->title !== $title) {
                $page->title = $title;
                $page->save();
            }
        } else {
            $emptyBlock = new WebBlock();
            $emptyBlock->idpage = $page->idpage;
            $emptyBlock->type = 'bodyContainer';
            $emptyBlock->content = '<h1>' . $page->title . '</h1><p>' . $page->description . '</p>';
            $this->addBlock($emptyBlock, $page);
        }
    }

    /**
     * Returns the H1 title for the content.
     *
     * @param string $content
     *
     * @return string
     */
    private function getH1Ttitle(string $content): string
    {
        $matches = [];
        preg_match_all("/<h1>(.*?)<\/h1>/", $content, $matches);
        return isset($matches[1][0]) ? $matches[1][0] : '';
    }

    /**
     * Returns the HTML container.
     *
     * @param string $content
     * @param string $containerClass
     *
     * @return string
     */
    private function getHtmlContainer(string $content, string $containerClass = 'container grid-lg'): string
    {
        return '<br/><div class="' . $containerClass . '"><div class="row"><div class="col-12">'
            . $content . '</div></div></div>';
    }
}
