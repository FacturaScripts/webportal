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
     * New password.
     *
     * @var string
     */
    public $newPassword;

    /**
     * Repeated new password.
     *
     * @var string
     */
    public $newPassword2;

    /**
     * Returns an user by email.
     *
     * @param string $email
     *
     * @return self|null
     */
    public function getByEmail(string $email): ?self
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE email = '
            . self::$dataBase->var2str($email) . ' ORDER BY idcontacto DESC;';
        $data = self::$dataBase->select($sql);

        foreach ($data as $d) {
            return new self($d);
        }
        return null;
    }

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

    /**
     * Returns True if there is no errors on properties values.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        $status = parent::test();

        if (isset($this->newPassword, $this->newPassword2) && $this->newPassword !== '' && $this->newPassword2 !== '') {
            if ($this->newPassword !== $this->newPassword2) {
                self::$miniLog->alert(self::$i18n->trans('different-passwords', ['%userNick%' => $this->email]));
                $status = false;
            }

            $this->setPassword($this->newPassword);
        }
        if (!isset($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-email', ['%email%' => $this->email]));
            $status = false;
        }

        return $status;
    }
}
