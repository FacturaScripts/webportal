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
namespace FacturaScripts\Plugins\webportal\Controller;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditProfile extends SectionController
{

    /**
     *
     * @var CodeModel
     */
    public $codeModel;

    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->codeModel = new CodeModel();
    }

    /**
     * 
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('profile', 'profile', 'Section/EditProfile');
    }

    /**
     * 
     * @return bool
     */
    protected function customDeleteAction()
    {
        if ('DELETE' === $this->request->get('security', '') && $this->contact->delete()) {
            $this->response->headers->clearCookie('fsIdcontacto');
            $this->response->headers->clearCookie('fsLogkey');
            $this->contact = null;

            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            $this->miniLog->notice($this->i18n->trans('reloading'));
            $this->redirect(AppSettings::get('webportal', 'url'), 3);
            return true;
        }

        $this->miniLog->warning($this->i18n->trans('record-deleted-error'));
        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function customEditAction()
    {
        if (!$this->contact->exists()) {
            /// we must prevent from unauthorized contact creation
            return true;
        }

        $fields = [
            'nombre', 'apellidos', 'tipoidfiscal', 'cifnif', 'direccion',
            'apartado', 'codpostal', 'ciudad', 'provincia', 'codpais',
            'newPassword', 'newPassword2'
        ];
        foreach ($fields as $field) {
            $this->contact->{$field} = $this->request->get($field, '');
        }

        if ($this->contact->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }

        return true;
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'delete':
                return $this->customDeleteAction();

            case 'edit':
                return $this->customEditAction();

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * 
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            default:
                if (empty($this->contact) || !$this->contact->exists()) {
                    $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
                    $this->webPage->noindex = true;
                    $this->setTemplate('Master/Portal404');
                }
                break;
        }
    }
}
