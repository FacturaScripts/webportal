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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DownloadTools;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Pais;

/**
 * Description of GeoLocation
 *
 * @author Carlos García Gómez
 */
class GeoLocation
{

    /**
     * Set geoIP details to contact.
     * 
     * @param Contacto $contact
     * @param string   $ipAddress
     */
    public function setGeoIpData(&$contact, $ipAddress)
    {
        $excludedIp = ['192.168.0.1', '::1'];
        if ($contact !== null && !\in_array($ipAddress, $excludedIp, true)) {
            $data = $this->getGeoIpData($ipAddress);
            if (empty($data)) {
                return;
            }

            $this->setContactField($contact, 'ciudad', $data['cityName']);
            $this->setContactField($contact, 'provincia', $data['regionName']);
            $contact->codpais = $this->getCodpais($data['countryCode'], $data['countryName']);
        }
    }

    private function getCodpais(string $codiso, string $name): string
    {
        $country = new Pais();
        if (!$country->loadFromCode('', [new DataBaseWhere('codiso', $codiso)])) {
            $country->codiso = $codiso;
            $country->codpais = $codiso;
            $country->nombre = $name;
            $country->save();
        }

        return $country->codpais;
    }

    /**
     * Returns location from IP address.
     * 
     * @param string $ip
     *
     * @return array
     */
    private function getGeoIpData($ip): array
    {
        $key = AppSettings::get('webportal', 'ipinfodbkey');
        if ($key === null) {
            return [];
        }

        $downloader = new DownloadTools();
        $reply = $downloader->getContents('http://api.ipinfodb.com/v3/ip-city/?key=' . $key . '&ip=' . $ip . '&format=json');
        if ($reply === 'ERROR') {
            return [];
        }

        return json_decode($reply, true);
    }

    /**
     * Set string to field, truncated to max field length.
     * 
     * @param Contact $contact
     * @param string $field
     * @param string $string
     */
    private function setContactField(&$contact, string $field, string $string)
    {
        $size = (int) preg_replace('/[^0-9]/', '', $contact->getModelFields()[$field]['type']);
        if (\property_exists(\get_class($contact), $field)) {
            $contact->{$field} = \mb_strlen($string) > $size ? \substr($string, 0, $size - 3) . '...' : $string;
        }
    }
}
