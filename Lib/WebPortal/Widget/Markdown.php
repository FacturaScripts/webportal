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
namespace FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget;

use FacturaScripts\Core\Base\Utils;
use Parsedown;

/**
 * Description of Markdown
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Markdown
{

    /**
     * 
     * @param string $markdown
     *
     * @return string
     */
    public static function render(string $markdown)
    {
        $parser = new Parsedown();
        $html = $parser->text(Utils::fixHtml($markdown));

        /// some html fixes
        return str_replace(
            ['<pre>', '<img ', '<h2>', '<h3>'],
            ['<pre class="code">', '<img class="img-responsive" ', '<h2 class="h3">', '<h3 class="h4">'],
            $html
        );
    }
}
