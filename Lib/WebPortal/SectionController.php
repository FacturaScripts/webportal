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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

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

    abstract protected function loadData(string $sectionName);

    public function getCurrentSection(): array
    {
        return $this->sections[$this->current];
    }

    public function getSectionGroups()
    {
        $group = [];
        foreach ($this->sections as $section) {
            if (!$section['fixed']) {
                $group[$section['group']][] = $section;
            }
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

    protected function addButton(string $sectionName, string $link, string $label, string $icon)
    {
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }

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
            'template' => $templateName,
        ];
        return $this->addSection($sectionName, $newSection);
    }

    protected function addOrderOption(string $sectionName, string $field, string $label, int $selection = 0)
    {
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }

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
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }

        $this->sections[$sectionName]['searchOptions'] = $fields;
    }

    protected function addSection(string $sectionName, array $params): bool
    {
        $fixed = isset($params['fixed']) ? $params['fixed'] : false;
        if ('' === $this->active && !$fixed) {
            $this->active = $sectionName;
            $this->current = $sectionName;
        }

        $newSection = [
            'icon' => '',
            'buttons' => [],
            'count' => 0,
            'cursor' => [],
            'fixed' => $fixed,
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
            'template' => 'Section/WebPage.html.twig',
            'jsfile' => '',
            'where' => [],
        ];

        foreach ($params as $key => $value) {
            $newSection[$key] = ($key === 'template') ? $value . '.html.twig' : $value;
        }

        $this->sections[$sectionName] = $newSection;
        return true;
    }
    protected function addFilterAutocomplete($viewName, $key, $label, $field, $table, $fieldcode = '', $fieldtitle = '', $where = [])
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $fcode = empty($fieldcode) ? $field : $fieldcode;
        $ftitle = empty($fieldtitle) ? $fcode : $fieldtitle;
        $this->views[$viewName]->addFilter($key, ListFilter::newAutocompleteFilter($label, $field, $table, $fcode, $ftitle, $value, $where));
    }
    
    /**
     * Adds a boolean condition type filter to the SectionView.
     *
     * @param string $viewName
     * @param string $key        (Filter identifier)
     * @param string $label      (Human reader description)
     * @param string $field      (Field of the model to apply filter)
     * @param bool   $inverse    (If you need to invert the selected value)
     * @param mixed  $matchValue (Value to match)
     */
    protected function addFilterCheckbox($viewName, $key, $label, $field, $inverse = false, $matchValue = true)
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $this->views[$viewName]->addFilter($key, ListFilter::newCheckboxFilter($field, $value, $label, $inverse, $matchValue));
    }
    
    /**
     * Adds a date type filter to the SectionView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterDatePicker($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'datepicker');
    }
    
    /**
     * Adds a filter to a type of field to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param string $type
     */
    private function addFilterFromType($viewName, $key, $label, $field, $type)
    {
        $config = [
            'field' => $field,
            'label' => $label,
            'valueFrom' => ($viewName == $this->active) ? $this->request->get($key . '-from', '') : '',
            'operatorFrom' => $this->request->get($key . '-from-operator', '>='),
            'valueTo' => ($viewName == $this->active) ? $this->request->get($key . '-to', '') : '',
            'operatorTo' => $this->request->get($key . '-to-operator', '<='),
        ];
        
        $this->views[$viewName]->addFilter($key, ListFilter::newStandardFilter($type, $config));
    }
    
    /**
     * Adds a numeric type filter to the SectionView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterNumber($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'number');
    }
    
    /**
     * Add a select type filter to a ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param array  $values    (Values to show)
     */
    protected function addFilterSelect($viewName, $key, $label, $field, $values = [])
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $this->views[$viewName]->addFilter($key, ListFilter::newSelectFilter($label, $field, $values, $value));
    }
    
    /**
     * Adds a text type filter to the SectionView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterText($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'text');
    }
    

    protected function commonCore()
    {
        $this->setTemplate('Master/SectionController');

        $this->active = $this->request->get('active', '');
        $this->createSections();

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }

        // Loads data for each section
        foreach (array_keys($this->sections) as $key) {
            $this->loadData($key);
        }

        // don't combine with previous foreach
        foreach ($this->sections as $key => $section) {
            if ($section['count'] === 0) {
                $this->sections[$key]['count'] = count($section['cursor']);
            }
        }

        // General operations with the loaded data
        $this->execAfterAction($action);
    }

    /**
     * General operations with the loaded data.
     *
     * @param string $action
     */
    protected function execAfterAction(string $action)
    {

    }

    /**
     * Run operations on the data before reading it. Returns false to stop process.
     *
     * @param string $action
     *
     * @return boolean
     */
    protected function execPreviousAction(string $action)
    {
        return true;
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

    protected function loadListSection(string $sectionName, array $where = [])
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
