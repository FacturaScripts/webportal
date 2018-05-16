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

/**
 * Description of SectionController
 *
 * @author Carlos Garcia Gomez
 */
abstract class SectionController extends PortalController
{

    /**
     *
     * @var string
     */
    public $active = '';

    /**
     *
     * @var string
     */
    public $current = '';

    /**
     *
     * @var array
     */
    public $sections = [];

    abstract protected function createSections();

    abstract protected function loadData($sectionName);

    public function getCurrentSection(): array
    {
        return $this->sections[$this->current];
    }

    public function getSectionGroups()
    {
        $group = [];
        foreach ($this->sections as $section) {
            $group[$section['group']][] = $section;
        }

        return $group;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->commonCore();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->commonCore();
    }

    public function setCurrentSection(string $sectionName)
    {
        $this->current = $sectionName;
    }

    protected function addButton(string $sectionName, string $link, string $label, string $icon = '')
    {
        $this->sections[$sectionName]['buttons'][] = [
            'icon' => $icon,
            'label' => $this->i18n->trans($label),
            'link' => $link
        ];
    }

    protected function addListSection(string $sectionName, string $modelName, string $templateName, string $label, string $icon = 'fa-file-o', string $group = ''): bool
    {
        $modelClass = '\FacturaScripts\Dinamic\Model\\' . $modelName;
        if (!class_exists($modelClass)) {
            $this->miniLog->alert($modelClass . ' not found.');
            return false;
        }

        $newSection = [
            'icon' => $icon,
            'group' => $group,
            'label' => $this->i18n->trans($label),
            'model' => new $modelClass(),
            'template' => $templateName . '.html.twig',
        ];
        return $this->addSection($sectionName, $newSection);
    }

    protected function addOrderOption(string $sectionName, string $field, string $label, int $selection = 0)
    {
        $this->sections[$sectionName]['orderOptions'][] = [
            'field' => $field,
            'label' => $this->i18n->trans($label),
            'order' => 'ASC',
            'selected' => (1 == $selection),
        ];

        $this->sections[$sectionName]['orderOptions'][] = [
            'field' => $field,
            'label' => $this->i18n->trans($label),
            'order' => 'DESC',
            'selected' => (2 == $selection),
        ];

        switch ($selection) {
            case 1:
                $this->sections[$sectionName]['order'] = [$field => 'ASC'];
                break;

            case 2:
                $this->sections[$sectionName]['order'] = [$field => 'DESC'];
                break;
        }

        $order = $this->request->get('order', '');
        if ($sectionName === $this->active && '' !== $order) {
            foreach ($this->sections[$sectionName]['orderOptions'] as $key => $option) {
                if ($order !== $option['field'] . ' ' . $option['order']) {
                    $this->sections[$sectionName]['orderOptions'][$key]['selected'] = false;
                    continue;
                }

                $this->sections[$sectionName]['order'] = [$option['field'] => $option['order']];
                $this->sections[$sectionName]['orderOptions'][$key]['selected'] = true;
            }
        }
    }

    protected function addSearchOptions(string $sectionName, array $fields)
    {
        $this->sections[$sectionName]['searchOptions'] = $fields;
    }

    protected function addSection(string $sectionName, array $params): bool
    {
        if ($this->active === '') {
            $this->active = $sectionName;
            $this->current = $sectionName;
        }

        $newSection = [
            'icon' => '',
            'buttons' => [],
            'count' => 0,
            'cursor' => [],
            'group' => '',
            'label' => '',
            'model' => null,
            'name' => $sectionName,
            'offset' => ($this->active == $sectionName) ? $this->request->get('offset', 0) : 0,
            'order' => [],
            'orderOptions' => [],
            'pages' => [],
            'query' => ($this->active == $sectionName) ? $this->request->get('query', '') : '',
            'searchOptions' => [],
            'template' => '',
            'where' => [],
        ];

        foreach ($params as $key => $value) {
            $newSection[$key] = $value;
        }

        $this->sections[$sectionName] = $newSection;
        return true;
    }

    protected function commonCore()
    {
        $this->active = $this->request->get('active', '');
        $this->createSections();
        if (!empty($this->sections)) {
            $this->setTemplate('Master/SectionController');
        }

        foreach (array_keys($this->sections) as $key) {
            $this->loadData($key);
        }

        /// don't combine with previous foreach
        foreach ($this->sections as $key => $section) {
            if ($section['count'] === 0) {
                $this->sections[$key]['count'] = count($section['cursor']);
            }
        }
    }

    protected function getPagination(array $section): array
    {
        $pages = [];
        $i = $num = 0;
        $current = 1;

        /// añadimos todas la página
        while ($num < $section['count']) {
            $pages[$i] = [
                'offset' => $i * FS_ITEM_LIMIT,
                'num' => $i + 1,
                'current' => ($num == $section['offset'])
            ];
            if ($num == $section['offset']) {
                $current = $i;
            }
            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach (array_keys($pages) as $j) {
            $enmedio = intval($i / 2);
            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j > 1 && $j < $current - 5 && $j != $enmedio) || ( $j > $current + 5 && $j < $i - 1 && $j != $enmedio)) {
                unset($pages[$j]);
            }
        }

        return (count($pages) > 1) ? $pages : [];
    }

    protected function loadListSection(string $sectionName, array $where)
    {
        $section = $this->sections[$sectionName];

        $finalWhere = $where;
        if ($sectionName === $this->active && '' !== $section['query']) {
            $fields = implode('|', $section['searchOptions']);
            $finalWhere[] = new DataBaseWhere($fields, $section['query'], 'LIKE');
        }

        $this->sections[$sectionName]['count'] = $section['model']->count($finalWhere);
        $this->sections[$sectionName]['cursor'] = $section['model']->all($finalWhere, $section['order'], $section['offset']);
        $this->sections[$sectionName]['where'] = $where;

        $this->sections[$sectionName]['pages'] = $this->getPagination($this->sections[$sectionName]);
    }
}
