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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of ListWebPage
 *
 * @author Carlos García Gómez
 */
class ListWebPage extends ExtendedController\ListController
{

    protected function createViews()
    {
        /// Web pages
        $this->addView('\FacturaScripts\Dinamic\Model\WebPage', 'ListWebPage', 'pages', 'fa-globe');
        $this->addSearchFields('ListWebPage', ['title', 'description']);
        $this->addOrderBy('ListWebPage', 'title');
        $this->addOrderBy('ListWebPage', 'posnumber');

        /// Web blocks
        $this->addView('\FacturaScripts\Dinamic\Model\WebBlock', 'ListWebBlock', 'blocks', 'fa-code');
        $this->addSearchFields('ListWebBlock', ['column1', 'column2', 'column3', 'column4']);
        $this->addOrderBy('ListWebBlock', 'idblock');
        $this->addOrderBy('ListWebBlock', 'idpage');
        $this->addOrderBy('ListWebBlock', 'posnumber');
    }

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'web-pages';
        $pageData['menu'] = 'admin';
        $pageData['icon'] = 'fa-globe';

        return $pageData;
    }

    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'htaccess':
                if ($this->regenHtaccess()) {
                    $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
                } else {
                    $this->miniLog->alert($this->i18n->trans('error'));
                }
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    private function regenHtaccess()
    {
        $htaccess = file_get_contents(FS_FOLDER . '/htaccess-sample');
        $htaccess .= "\n\n<IfModule mod_rewrite.c>\n   RewriteEngine On\n\n";

        $langcodes = [];
        $webPageModel = new WebPage();
        foreach ($webPageModel->all([], ['posnumber' => 'ASC'], 0, 1000) as $webPage) {
            $htaccess .= "   RewriteRule ^" . $webPage->langcode . '/' . $webPage->permalink . "$ "
                . $webPage->internalLink() . "&%{QUERY_STRING} [L]\n";

            if (!in_array($webPage->langcode, $langcodes)) {
                $langcodes[] = $webPage->langcode;
            }
        }

        foreach ($langcodes as $lang) {
            $htaccess .= "   RewriteRule ^" . $lang . "$ index.php?page="
                . $webPageModel::DEFAULT_CONTROLLER . "&langcode=" . $lang . "&%{QUERY_STRING} [L]\n";

            $htaccess .= "   RewriteRule ^" . $lang . "/$ index.php?page="
                . $webPageModel::DEFAULT_CONTROLLER . "&langcode=" . $lang . "&%{QUERY_STRING} [L]\n";
        }

        $htaccess .= "</IfModule>\n";
        return file_put_contents('.htaccess', $htaccess);
    }
}
