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
namespace FacturaScripts\Plugins\webportal\Model;

/**
 * A contact of FacturaScripts is a loggeable user.
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class Contacto extends \FacturaScripts\Core\Model\Contacto
{

    /**
     * Password hashed with password_hash()
     *
     * @var string
     */
    public $password;

    /**
     * Asigns the new password to the contact.
     *
     * @param string $pass
     */
    public function setPassword(string $pass): void
    {
        $this->password = password_hash($pass, PASSWORD_DEFAULT);
    }

    /**
     * Verifies password. It also rehash the password if needed.
     *
     * @param string $pass
     *
     * @return bool
     */
    public function verifyPassword(string $pass): bool
    {
        if (password_verify($pass, $this->password)) {
            if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                $this->setPassword($pass);
            }

            return true;
        }

        return false;
    }
}
