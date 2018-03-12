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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Sitemap
 *
 * @author Carlos García Gómez
 */
class Sitemap extends Controller
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'sitemap';
        $pageData['menu'] = 'web';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->generateSitemap();
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->generateSitemap();
    }

    private function generateSitemap()
    {
        $this->setTemplate(false);
        $this->response->headers->set('Content-type', 'text/xml');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($this->getSitemapItems() as $item) {
            $xml .= '<url>
            <loc>' . $item['loc'] . '</loc>
            <lastmod>' . $item['lastmod'] . '</lastmod>
            <changefreq>' . $item['changefreq'] . '</changefreq>
            <priority>' . $item['priority'] . '</priority>
         </url>';
        }
        $xml .= '</urlset>';

        $this->response->setContent($xml);
    }

    private function getSitemapItems()
    {
        $items = [];

        $webpageModel = new WebPage();
        foreach ($webpageModel->all([], [], 0, 0) as $wpage) {
            if($wpage->noindex) {
                continue;
            }
            
            $items[] = [
                'loc' => $wpage->permalink,
                'lastmod' => date('Y-m-d', strtotime($wpage->lastmod)),
                'changefreq' => 'always',
                'priority' => 0.7
            ];
        }

        return $items;
    }
}
