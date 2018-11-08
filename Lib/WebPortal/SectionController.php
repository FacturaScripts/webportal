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

use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\Widget\VisualItem;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget\WidgetAutocomplete;

/**
 * Description of SectionController
 *
 * @author Carlos Garcia Gomez
 */
abstract class SectionController extends PortalController
{

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     *
     * @var string
     */
    public $active = '';

    /**
     *
     * @var CodeModel
     */
    protected $codeModel;

    /**
     *
     * @var string
     */
    public $current = '';

    /**
     *
     * @var array
     */
    public $navigationLinks = [];

    /**
     *
     * @var ListSection[]
     */
    public $sections = [];

    abstract protected function createSections();

    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        AssetManager::add('css', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/jquery/dist/jquery.min.js');
        AssetManager::add('js', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js');
        $this->codeModel = new CodeModel();
    }

    /**
     * 
     * @return ListSection
     */
    public function getCurrentSection()
    {
        return $this->sections[$this->current];
    }

    /**
     * 
     * @return array
     */
    public function getSectionGroups()
    {
        $group = [];
        foreach ($this->sections as $name => $section) {
            $group[$section->group][$name] = $section;
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

    /**
     * 
     * @param string $sectionName
     */
    public function setCurrentSection($sectionName)
    {
        $this->current = $sectionName;
    }

    /**
     * 
     * @param string $sectionName
     * @param array  $btnArray
     */
    protected function addButton($sectionName, $btnArray)
    {
        $row = $this->sections[$sectionName]->getRow('actions');
        if ($row) {
            $row->addButton($btnArray);
        }
    }

    /**
     * Adds a Edit type section to the controller.
     *
     * @param string $sectionName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @param string $group
     */
    protected function addEditSection($sectionName, $modelName, $viewTitle, $viewIcon = 'fas fa-edit', $group = '')
    {
        $newSection = new EditSection($sectionName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon, $group);
        $this->addSection($sectionName, $newSection);
    }

    /**
     * Adds a EditList type section to the controller.
     *
     * @param string $sectionName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     * @param string $group
     */
    protected function addEditListSection($sectionName, $modelName, $viewTitle, $viewIcon = 'fas fa-edit', $group = '')
    {
        $newSection = new EditListSection($sectionName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon, $group);
        $this->addSection($sectionName, $newSection);
    }

    /**
     * Adds a boolean condition type filter to the ListSection.
     *
     * @param string $sectionName
     * @param string $key        (Filter identifier)
     * @param string $label      (Human reader description)
     * @param string $field      (Field of the model to apply filter)
     * @param string $operation  (operation to perform with match value)
     * @param mixed  $matchValue (Value to match)
     * @param DataBaseWhere[] $default (where to apply when filter is empty)
     */
    protected function addFilterCheckbox($sectionName, $key, $label = '', $field = '', $operation = '=', $matchValue = true, $default = [])
    {
        $filter = new ListFilter\CheckboxFilter($key, $field, $label, $operation, $matchValue, $default);
        $this->sections[$sectionName]->filters[$key] = $filter;
    }

    /**
     * Adds a date type filter to the ListSection.
     *
     * @param string $sectionName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param string $operation (Operation to perform)
     */
    protected function addFilterDatePicker($sectionName, $key, $label = '', $field = '', $operation = '>=')
    {
        $filter = new ListFilter\DateFilter($key, $field, $label, $operation);
        $this->sections[$sectionName]->filters[$key] = $filter;
    }

    /**
     * Add a select type filter to a ListSection.
     *
     * @param string $sectionName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param array  $values    (Values to show)
     */
    protected function addFilterSelect($sectionName, $key, $label, $field, $values = [])
    {
        $filter = new ListFilter\SelectFilter($key, $field, $label, $values);
        $this->sections[$sectionName]->filters[$key] = $filter;
    }

    /**
     * Adds a HTML type section to the controller.
     *
     * @param string $sectionName
     * @param string $title
     * @param string $fileName
     * @param string $modelName
     * @param string $icon
     * @param string $group
     */
    protected function addHtmlSection($sectionName, $title, $fileName = 'Section/WebPage', $modelName = 'WebPage', $icon = 'fab fa-html5', $group = '')
    {
        $newSection = new HtmlSection($sectionName, $title, self::MODEL_NAMESPACE . $modelName, $fileName, $icon, $group);
        $this->addSection($sectionName, $newSection);
    }

    /**
     * 
     * @param string $sectionName
     * @param string $modelName
     * @param string $label
     * @param string $icon
     * @param string $group
     */
    protected function addListSection($sectionName, $modelName, $label, $icon = 'fas fa-file', $group = '')
    {
        $newSection = new ListSection($sectionName, $label, self::MODEL_NAMESPACE . $modelName, $icon, $group);
        $this->addSection($sectionName, $newSection);
    }

    /**
     * 
     * @param string $link
     * @param string $title
     */
    protected function addNavigationLink($link, $title)
    {
        $this->navigationLinks[] = ['title' => $title, 'url' => $link];
    }

    /**
     * 
     * @param string $sectionName
     * @param array  $fields
     * @param string $label
     * @param int    $selection
     */
    protected function addOrderOption($sectionName, $fields, $label, $selection = 0)
    {
        $this->sections[$sectionName]->addOrderBy($fields, $label, $selection);
    }

    /**
     * 
     * @param string $sectionName
     * @param array  $fields
     */
    protected function addSearchOptions($sectionName, $fields)
    {
        $this->sections[$sectionName]->searchFields = $fields;
    }

    /**
     * 
     * @param string      $sectionName
     * @param ListSection $newSection
     */
    protected function addSection($sectionName, $newSection)
    {
        if ($sectionName !== $newSection->getViewName()) {
            $this->miniLog->error('$sectionName must be equals to $view->name');
            return;
        }

        $newSection->loadPageOptions();
        $this->sections[$sectionName] = $newSection;
        if ('' === $this->active) {
            $this->active = $sectionName;
        }
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return string
     */
    protected function autocompleteAction()
    {
        $data = [];
        foreach (['field', 'source', 'fieldcode', 'fieldtitle', 'term', 'formname'] as $value) {
            $data[$value] = $this->request->get($value);
        }
        if ($data['source'] == '') {
            return $this->getAutocompleteValues($data['formname'], $data['field']);
        }

        /// is this search allowed?
        if (!WidgetAutocomplete::allowed($data['source'], $data['fieldcode'], $data['fieldtitle'])) {
            return [];
        }

        $results = [];
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term']) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        return $results;
    }

    /**
     * Returns true if we can safely edit this model object.
     *
     * @param object $model
     *
     * @return boolean
     */
    protected function checkModelSecurity($model)
    {
        return true;
    }

    protected function commonCore()
    {
        $this->setTemplate('Master/SectionController');
        $this->setLevel();

        $this->active = $this->request->request->get('activetab', $this->request->query->get('activetab', ''));
        $this->createSections();

        // Get any operations that have to be performed
        $action = $this->request->request->get('action', $this->request->query->get('action', ''));

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }

        // Loads data for each section
        foreach (array_keys($this->sections) as $key) {
            if ($this->active == $key) {
                $this->sections[$key]->processFormData($this->request, 'load');
            } else {
                $this->sections[$key]->processFormData($this->request, 'preload');
            }

            $this->loadData($key);
        }

        // General operations with the loaded data
        $this->execAfterAction($action);
    }

    /**
     * Action to delete data.
     *
     * @return bool
     */
    protected function deleteAction()
    {
        $model = $this->sections[$this->active]->model;
        $code = $this->request->request->get('code', '');
        if ($model->loadFromCode($code) && $this->checkModelSecurity($model) && $model->delete()) {
            // deleting a single row?
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            return true;
        }

        $this->miniLog->warning($this->i18n->trans('record-deleted-error'));
        return false;
    }

    /**
     * Runs the data edit action.
     *
     * @return bool
     */
    protected function editAction()
    {
        // loads model data
        $code = $this->request->request->get('code', '');
        if (!$this->sections[$this->active]->model->loadFromCode($code)) {
            $this->miniLog->error($this->i18n->trans('record-not-found'));
            return false;
        }

        // loads form data
        $this->sections[$this->active]->processFormData($this->request, 'edit');

        // checks security
        if (!$this->checkModelSecurity($this->sections[$this->active]->model)) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            $this->sections[$this->active]->model->clear();
            return false;
        }

        // has PK value been changed?
        $this->sections[$this->active]->newCode = $this->sections[$this->active]->model->primaryColumnValue();
        if ($code != $this->sections[$this->active]->newCode) {
            $pkColumn = $this->sections[$this->active]->model->primaryColumn();
            $this->sections[$this->active]->model->{$pkColumn} = $code;
            // change in database
            if (!$this->sections[$this->active]->model->changePrimaryColumnValue($this->sections[$this->active]->newCode)) {
                $this->miniLog->error($this->i18n->trans('record-save-error'));
                return false;
            }
        }

        // save in database
        if ($this->sections[$this->active]->model->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $this->sections[$this->active]->model->clear();
            return true;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
        return false;
    }

    /**
     * General operations with the loaded data.
     *
     * @param string $action
     */
    protected function execAfterAction(string $action)
    {
        ;
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
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $results = $this->autocompleteAction();
                $this->response->setContent(json_encode($results));
                return false;

            case 'delete':
                $this->deleteAction();
                break;

            case 'edit':
                $this->editAction();
                break;

            case 'insert':
                $this->insertAction();
                break;
        }

        return true;
    }

    /**
     * Changes the template to show the first section as fixed.
     */
    protected function fixedSection()
    {
        $this->setTemplate('Master/SectionControllerFixed');
    }

    /**
     * Return values from Widget Values for autocomplete action
     *
     * @param string $sectionName
     * @param string $fieldName
     *
     * @return array
     */
    protected function getAutocompleteValues(string $sectionName, string $fieldName): array
    {
        $result = [];
        $column = $this->sections[$sectionName]->columnForField($fieldName);
        if (!empty($column)) {
            foreach ($column->widget->values as $value) {
                $result[] = ['key' => $this->i18n->trans($value['title']), 'value' => $value['value']];
            }
        }
        return $result;
    }

    /**
     * Runs data insert action.
     */
    protected function insertAction()
    {
        // loads form data
        $this->sections[$this->active]->processFormData($this->request, 'edit');
        if ($this->sections[$this->active]->model->exists()) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return false;
        }

        // empty primary key?
        if (empty($this->sections[$this->active]->model->primaryColumnValue())) {
            $model = $this->sections[$this->active]->model;
            // assign a new value
            $this->sections[$this->active]->model->{$model->primaryColumn()} = $model->newCode();
        }

        // checks security
        if (!$this->checkModelSecurity($this->sections[$this->active]->model)) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            $this->sections[$this->active]->model->clear();
            return false;
        }

        // save in database
        if ($this->sections[$this->active]->model->save()) {
            $this->sections[$this->active]->newCode = $this->sections[$this->active]->model->primaryColumnValue();
            $this->sections[$this->active]->model->clear();
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
        return false;
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $this->sections[$sectionName]->loadData();
    }

    /**
     * 
     * @param string $sectionName
     * @param array  $where
     */
    protected function loadListSection(string $sectionName, array $where = [])
    {
        $this->miniLog->alert('loadListSection($sectionName, $where) is deprecated. Please, '
            . 'use $this->sections[$sectionName]->loadData(\'\', $where)');
        $this->sections[$sectionName]->loadData('', $where);
    }

    /**
     * Sets contact security level to use in render.
     */
    protected function setLevel()
    {
        if ($this->contact) {
            VisualItem::setLevel($this->contact->level);
        }
    }
}
