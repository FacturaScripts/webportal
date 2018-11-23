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
namespace FacturaScripts\Plugins\webportal;

require_once __DIR__ . '/vendor/autoload.php';

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\UpdateRoutes;

/**
 * Description of Init
 *
 * @author Carlos García Gómez
 */
class Init extends InitClass
{

    public function init()
    {
        ;
    }

    public function update()
    {
        /// set PortalHome as app homepage
        $appSettings = new AppSettings();
        if ($appSettings->get('default', 'homepage') !== 'PortalHome') {
            $appSettings->set('default', 'homepage', 'PortalHome');
            $appSettings->save();
        }

        /// set portal home page
        if ($appSettings->get('webportal', 'homepage') === null) {
            $appSettings->set('webportal', 'homepage', 1);
            $appSettings->save();
        }

        $updater = new UpdateRoutes();
        $updater->setRoutes();

        $this->createContact();
    }

    /**
     * Create contact of all users that contains email.
     */
    private function createContact()
    {
        $user = new User();
        $where = [new DataBaseWhere('email', 'NULL', 'IS NOT')];
        foreach ($user->all($where, [], 0, 0) as $user) {
            $where2 = [new DataBaseWhere('email', $user->email)];
            $contact = new Contacto();
            if ($contact->loadFromCode('', $where2)) {
                continue;
            }

            $contact->descripcion = $user->nick;
            $contact->email = $user->email;
            $contact->nombre = $user->nick;
            $contact->save();
        }
    }
}
