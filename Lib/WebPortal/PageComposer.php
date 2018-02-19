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
     *
     * @var WebBlock[]
     */
    private $blocks;

    /**
     *
     * @var WebBlock
     */
    private $webBlock;

    /**
     *
     * @var WebCluster
     */
    private $webCluster;

    public function __construct()
    {
        $this->blocks = [];
        $this->webBlock = new WebBlock();
        $this->webCluster = new WebCluster();
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

        $this->checkBody($page);
    }

    private function addBlock(WebBlock $block, WebPage $page)
    {
        $container = 'container';
        switch ($block->type) {
            case 'body-cluster':
                $block->type = 'body';
                $block->content = $this->getClusterHtml($block->content, $page);
                break;

            case 'body-container-fluid':
                $container .= '-fluid';
            /// no break
            case 'body-container':
                $block->type = 'body';
                $block->content = $this->getHtmlContainer($block->content, $container);
                break;
        }

        $this->blocks[] = $block;
    }

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
            $emptyBlock->type = 'body-container';
            $emptyBlock->content = '<h1>' . $page->title . '</h1><p>' . $page->description . '</p>';
            $this->addBlock($emptyBlock, $page);
        }
    }

    private function getClusterHtml($idcluster, WebPage $page)
    {
        $cluster = $this->webCluster->get($idcluster);
        if (!$cluster) {
            return $this->getHtmlContainer('<h3>Cluster no encontrado</h3>');
        }

        $html = '<br/><div class="container"><div class="row">'
            . '<div class="col-md-12"><h3>' . $cluster->title . '</h3><p>' . $cluster->description . '</p></div>'
            . '</div><div class="row">';
        foreach ($page->all([new DataBaseWhere('idcluster', $idcluster)]) as $key => $clusterPage) {
            if ($clusterPage->idpage === $page->idpage) {
                continue;
            }

            $html .= '<div class="col-md-4"><a href="' . $clusterPage->link() . '" class="btn btn-'
                . $this->getColorClass($key) . ' btn-lg btn-block">'
                . '<i class="fa ' . $clusterPage->icon . ' fa-4x"></i><br/>' . $clusterPage->title . '</a>'
                . '<p>' . $clusterPage->description . '</p></div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private function getColorClass($key = 0)
    {
        $classes = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        return $classes[$key];
    }

    private function getH1Ttitle($content)
    {
        $matches = [];
        preg_match_all("/<h1>(.*?)<\/h1>/", $content, $matches);
        return isset($matches[1][0]) ? $matches[1][0] : '';
    }

    private function getHtmlContainer($content, $containerClass = 'container')
    {
        return '<br/><div class="' . $containerClass . '"><div class="row"><div class="col-12">'
            . $content . '</div></div></div>';
    }
}
