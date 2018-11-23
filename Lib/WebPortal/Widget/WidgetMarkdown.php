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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget;

use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Description of WidgetMarkdown
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetMarkdown extends WidgetTextarea
{

    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/Plugins/webportal/node_modules/easymde/dist/easymde.min.css');
        AssetManager::add('js', FS_ROUTE . '/Plugins/webportal/node_modules/easymde/dist/easymde.min.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetMarkdown.js');
    }

    protected function inputHtml($type = 'text', $extraClass = 'markdown-editor')
    {
        return parent::inputHtml($type, $extraClass);
    }
}
