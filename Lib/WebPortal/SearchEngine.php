<?php
/**
 * This file is part of webportal plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Plugins\webportal\Model\WebBlock;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of SearchEngine
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SearchEngine
{

    const MAX_DESCRIPTION_LENGTH = 300;

    /**
     * 
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $results = [];
        foreach ($this->getFinalQueries($query) as $string) {
            $this->findResults($results, $string);
        }

        $this->sort($results);
        return $results;
    }

    /**
     * Adds item to search results.
     * 
     * @param array  $results
     * @param array  $item
     * @param string $query
     * 
     * @return bool
     */
    protected function addSearchResults(&$results, $item, $query): bool
    {
        /// link already in results
        if (isset($results[$item['link']])) {
            return false;
        }

        $item['position'] = false;
        $text = mb_strtolower($item['title'] . ' ' . $item['description']);
        foreach (explode(' ', $query) as $subQuery) {
            if (empty($subQuery)) {
                continue;
            }

            $position = mb_strpos($text, $subQuery);
            if (false === $position) {
                $item['position'] = false;
                break;
            } elseif (false === $item['position']) {
                $item['position'] = $position;
                continue;
            }

            $item['position'] = min([(int) $item['position'], (int) $position]);
        }

        $item['icon'] = isset($item['icon']) ? $item['icon'] : 'fas fa-file';
        $item['title'] = isset($item['title']) ? $item['title'] : 'Title';
        $item['description'] = $this->fixDescription($item['description']);
        $item['priority'] = isset($item['priority']) ? $item['priority'] : 0;
        $results[$item['link']] = $item;
        return true;
    }

    /**
     * 
     * @param array $results
     */
    private function beforeSort(&$results)
    {
        /// we need maximum value of position and priority
        $maxPosition = 0;
        $maxPriority = -1000;
        foreach ($results as $item) {
            if ($item['position'] > $maxPosition) {
                $maxPosition = $item['position'];
            }

            if ($item['priority'] > $maxPriority) {
                $maxPriority = $item['priority'];
            }
        }

        $lastOrdernum = 0;
        $lastPriority = -1000;
        foreach ($results as $key => $value) {
            if ($value['priority'] != $lastPriority) {
                $lastPriority = $value['priority'];
                $lastOrdernum = 0;
            }

            /// add max position when position is FALSE
            if (false === $value['position']) {
                $results[$key]['position'] = 1 + $maxPosition;
            }

            $results[$key]['ordernum'] = $lastOrdernum;
            $lastOrdernum += 0.5;

            if ($value['priority'] == $maxPriority) {
                $results[$key]['index'] = $results[$key]['position'] + $results[$key]['ordernum'];
            } else {
                $results[$key]['index'] = ($results[$key]['position'] + $results[$key]['ordernum']) * pow(2, abs($maxPriority - $value['priority']));
            }
        }
    }

    /**
     * 
     * @param array  $results
     * @param string $query
     */
    protected function findResults(&$results, $query)
    {
        $this->findWebPages($results, $query);
        $this->findWebBlocks($results, $query);
    }

    /**
     * 
     * @param array  $results
     * @param string $query
     */
    protected function findWebBlocks(&$results, $query)
    {
        $webBlockModel = new WebBlock();
        $where = [new DataBaseWhere('content', $query, 'XLIKE')];
        foreach ($webBlockModel->all($where) as $wblock) {
            $link = $wblock->url('public');
            if (empty($link)) {
                continue;
            }

            $this->addSearchResults($results, [
                'icon' => 'fas fa-file',
                'title' => $link,
                'description' => $wblock->content(true),
                'link' => $link
                ], $query);
        }
    }

    /**
     * 
     * @param array  $results
     * @param string $query
     */
    protected function findWebPages(&$results, $query)
    {
        $webPageModel = new WebPage();
        $where = [new DataBaseWhere('description|title', $query, 'XLIKE')];
        foreach ($webPageModel->all($where, ['visitcount' => 'DESC']) as $wpage) {
            $this->addSearchResults($results, [
                'icon' => $wpage->icon,
                'title' => $wpage->title,
                'description' => $wpage->description,
                'link' => $wpage->url('public')
                ], $query);
        }
    }

    /**
     * Fix search results descriptions.
     * 
     * @param string $txt
     * 
     * @return string
     */
    protected static function fixDescription(string $txt): string
    {
        if (null === $txt) {
            return '';
        }

        $final = trim(Utils::trueTextBreak($txt, self::MAX_DESCRIPTION_LENGTH));
        return (mb_strlen($final) < self::MAX_DESCRIPTION_LENGTH) ? $final : $final . '...';
    }

    /**
     * Returns an array with the query and the same query without accents.
     * 
     * @param string $query
     *
     * @return array
     */
    protected function getFinalQueries($query): array
    {
        $queries = [$query];
        $transform = [
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        ];
        $newQuery = strtr($query, $transform);
        if ($newQuery != $query) {
            $queries[] = $newQuery;
        }

        return $queries;
    }

    /**
     * Sorts search results.
     * 
     * @param array $results
     */
    protected function sort(&$results)
    {
        $this->beforeSort($results);

        /// sort by index
        usort($results, function($item1, $item2) {
            if ($item1['index'] == $item2['index']) {
                return 0;
            } else if ($item1['index'] > $item2['index']) {
                return 1;
            }

            return -1;
        });
    }
}
