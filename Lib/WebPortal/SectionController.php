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

    abstract function createSections();

    abstract function loadData($sectionName);

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

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('Master/SectionController');
        $this->active = $this->request->get('active', '');
        $this->createSections();

        foreach (array_keys($this->sections) as $key) {
            if ($this->active === '') {
                $this->active = $key;
                $this->current = $key;
            }
            $this->loadData($key);
        }

        foreach ($this->sections as $key => $section) {
            if ($section['count'] === 0) {
                $this->sections[$key]['count'] = count($section['cursor']);
            }
        }
    }

    public function setCurrentSection(string $name)
    {
        $this->current = $name;
    }

    protected function newSection(string $name, string $modelName, string $templateName, string $label, string $icon = 'fa-file-o', string $group = ''): bool
    {
        $modelClass = '\FacturaScripts\Dinamic\Model\\' . $modelName;
        if (!class_exists($modelClass)) {
            $this->miniLog->alert($modelClass . ' not found.');
            return false;
        }

        $this->sections[$name] = [
            'icon' => $icon,
            'count' => 0,
            'cursor' => [],
            'group' => $group,
            'label' => $this->i18n->trans($label),
            'model' => new $modelClass(),
            'name' => $name,
            'template' => $templateName . '.html.twig',
        ];
        return true;
    }
}
