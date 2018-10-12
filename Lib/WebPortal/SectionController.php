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
     * @var string
     */
    public $current = '';

    /**
     *
     * @var ListSection[]
     */
    public $sections = [];

    abstract protected function createSections();

    abstract protected function loadData(string $sectionName);

    public function getCurrentSection()
    {
        return $this->sections[$this->current];
    }

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

    public function setCurrentSection($sectionName)
    {
        $this->current = $sectionName;
    }

    protected function addButton($sectionName, $link, $label, $icon)
    {
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }

        $this->sections[$sectionName]->buttons[] = [
            'icon' => $icon,
            'label' => $this->i18n->trans($label),
            'link' => $link
        ];
    }

    protected function addListSection($sectionName, $modelName, $label, $icon = 'fas fa-file', $group = '')
    {
        $newSection = new ListSection($sectionName, $label, self::MODEL_NAMESPACE . $modelName, $icon, $group);
        $this->addSection($sectionName, $newSection);
    }

    protected function addOrderOption($sectionName, $field, $label, $selection = 0)
    {
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }
    }

    protected function addSearchOptions($sectionName, $fields)
    {
        if (!isset($this->sections[$sectionName])) {
            $this->miniLog->critical('Section not found: ' . $sectionName);
            return;
        }

        $this->sections[$sectionName]->searchFields = $fields;
    }

    protected function addSection($sectionName, $newSection)
    {
        if ($sectionName !== $newSection->getViewName()) {
            $this->miniLog->error('$viewName must be equals to $view->name');
            return;
        }

        $newSection->loadPageOptions();
        $this->sections[$sectionName] = $newSection;
        if ('' === $this->active) {
            $this->active = $sectionName;
        }
    }

    protected function commonCore()
    {
        $this->setTemplate('Master/SectionController');

        $this->active = $this->request->get('activetab', '');
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
}
