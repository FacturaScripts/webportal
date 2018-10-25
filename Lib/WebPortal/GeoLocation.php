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
     * Determines if an ip4 are within a range
     *
     * @param string $ipAddress
     * @param string $start
     * @param string $end
     * @return bool
     */
    private function ip4InRange($ipAddress, $start, $end): bool
    {
        return ( ip2long($start) <= ip2long($ipAddress) && ip2long($end) >= ip2long($ipAddress) );
    }

    /**
     * Determines if an ip4 are within a private network
     * 
     * @param string $ipAddress
     * @return bool
     */
    private function excludedIp($ipAddress): bool
    {
        return in_array($ipAddress, ['127.0.0.1', '::1'], true) || $this->ip4InRange($ipAddress, '192.168.0.0', '192.168.255.255') // Clase C
            || $this->ip4InRange($ipAddress, '172.16.0.0', '172.31.255.255') // Clase B
            || $this->ip4InRange($ipAddress, '169.254.0.0', '169.254.255.255') // Clase B simple
            || $this->ip4InRange($ipAddress, '10.0.0.0', '10.255.255.255'); // Clase A
    }

    /**
     * Set geoIP details to contact.
     *
     * @param Contacto $contact
     * @param string   $ipAddress
     */
    public function setGeoIpData(&$contact, $ipAddress)
    {
        if ($contact === null || $this->excludedIp($ipAddress)) {
            return;
        }

        $data = $this->getGeoIpData($ipAddress);
        if (empty($data)) {
            return;
        }

        $this->setContactField($contact, 'ciudad', $data['cityName']);
        $this->setContactField($contact, 'provincia', $data['regionName']);
        $contact->codpais = $this->getCodpais($data['countryCode'], $data['countryName']);
    }

    /**
     * Returns country code.
     *
     * @param string $codiso
     * @param string $name
     *
     * @return string|null
     */
    private function getCodpais(string $codiso, string $name)
    {
        if (empty($codiso) || '-' === $codiso) {
            return null;
        }

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
