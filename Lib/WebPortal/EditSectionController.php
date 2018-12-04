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

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditSectionController
 *
 * @author Carlos Garcia Gomez
 */
abstract class EditSectionController extends SectionController
{

    abstract public function contactCanEdit();

    abstract public function contactCanSee();

    abstract public function getMainModel($reload = false);

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
     * Returns true if we can safely edit this model object.
     *
     * @param object $model
     *
     * @return bool
     */
    protected function checkModelSecurity($model)
    {
        return true;
    }

    /**
     * Action to delete data.
     *
     * @return bool
     */
    protected function deleteAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

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
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

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

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                return true;

            case 'edit':
                $this->editAction();
                return true;

            case 'insert':
                $this->insertAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Runs data insert action.
     */
    protected function insertAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

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
}
