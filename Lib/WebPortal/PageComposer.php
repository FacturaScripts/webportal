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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Model\WebBlock;
use FacturaScripts\Plugins\webportal\Model\WebCluster;
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
     * A web cluster.
     * 
     * @var WebCluster
     */
    private $webCluster;

    /**
     * PageComposer constructor.
     */
    public function __construct()
    {
        $this->blocks = [];
        $this->webBlock = new WebBlock();
        $this->webCluster = new WebCluster();
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
     * Set a page to blocks.
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
            case 'body-cluster':
            case 'bodyCluster':
                $block->type = 'body';
                $block->content = $this->getClusterHtml($block->content, $page);
                break;

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
     * Returns a cluster of html page.
     *
     * @param int     $idcluster
     * @param WebPage $page
     *
     * @return string
     */
    private function getClusterHtml(int $idcluster, WebPage $page): string
    {
        $cluster = $this->webCluster->get($idcluster);
        if (!$cluster) {
            return $this->getHtmlContainer('<h3>Cluster no encontrado</h3>');
        }

        $html = '<div class="empty"><h3 class="empty-title">' . $cluster->title . '</h3>'
            . '<p class="empty-subtitle">' . $cluster->description . '</p><div class="container grid-lg">'
            . '<div class="columns">';
        foreach ($page->all([new DataBaseWhere('idcluster', $idcluster)]) as $key => $clusterPage) {
            if ($clusterPage->idpage === $page->idpage) {
                continue;
            }

            $html .= '<div class="column col-md-4 col-sm-12">'
                . '<div class="text-center"><i class="fa ' . $clusterPage->icon . ' fa-4x"></i></div>&nbsp;'
                . '<a href="' . $clusterPage->url('link') . '" class="btn btn-' . $this->getColorClass($key) . ' btn-block">' . $clusterPage->shorttitle . '</a>'
                . '<p>' . $clusterPage->description . '</p></div>';
        }
        $html .= '</div></div></div>';

        return $html;
    }

    /**
     * Returns the color class.
     *
     * @param int $key
     *
     * @return string
     */
    private function getColorClass(int $key = 0): string
    {
        $classes = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        return isset($classes[$key]) ? $classes[$key] : 'light';
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
