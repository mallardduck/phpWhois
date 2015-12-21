<?php
/**
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @license
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @link http://phpwhois.pw
 * @copyright Copyright (c) 2015 Dmitry Lukashin
 */

namespace phpWhois;


final class Query
{

    const QTYPE_UNKNOWN = -1;
    const QTYPE_DOMAIN  = 1;
    const QTYPE_IPV4    = 2;
    const QTYPE_IPV6    = 3;
    const QTYPE_AS      = 4;

    /**
     * @var int Query type (see constants)
     */
    private $type;

    /**
     * @var string  Original address received
     */
    private $addressOrig;

    /**
     * @var string  Address optimized for querying the whois server
     */
    private $address;

    /**
     * Query constructor.
     *
     * @param   null|string  $address
     */
    public function __construct($address = null)
    {
        if (!is_null($address)) {
            $this->setAddress($address);
        }
    }

    /**
     * Set address, make necessary checks and transformations
     *
     * @api
     *
     * @param   string  $address
     *
     * @return  $this
     *
     * @throws  \InvalidArgumentException    if address is not recognized
     */
    public function setAddress($address)
    {
        $type = $this->guessType($address);

        if ($type == self::QTYPE_UNKNOWN) {
            throw new \InvalidArgumentException('Address is not recognized, can\'t find whois server');
        } else {
            $this->setType($type);
        }

        $this->setAddressOrig($address);

        $address = $this->optimizeAddress($address);

        $this->address = $address;

        return $this;
    }

    /**
     * @return string   Address, optimized for querying whois server
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param   string  $address
     *
     * @return $this
     */
    private function setAddressOrig($address)
    {
        $this->addressOrig = $address;

        return $this;
    }

    /**
     * @return string   Original unoptimized address
     */
    public function getAddressOrig()
    {
        return $this->addressOrig;
    }

    /**
     * Check if class instance has valid address set
     *
     * @return bool
     */
    public function hasData()
    {
        return !is_null($this->getAddress());
    }

    /**
     * @param int    $type
     *
     * @return $this
     */
    private function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return !is_null($this->type) ? $this->type : self::QTYPE_UNKNOWN;
    }

    /**
     * Find the type of a given address and make some optimizations like removing www.
     *
     * @api
     *
     * @param   string  $address
     *
     * @return  string  optimized address
     */
    public static function optimizeAddress($address)
    {
        $type = self::guessType($address);
        // TODO: handle IDN
        if ($type == self::QTYPE_DOMAIN) {
            $address = strtoupper($address);

            $address_nowww = preg_replace('/^www./i', '', $address);
            if (QueryUtils::validDomain($address_nowww)) {
                $address = $address_nowww;
            }
        }
        return $address;
    }

    /**
     * Guess address type
     *
     * @api
     *
     * @param   string  $query
     *
     * @return  int Query type
     */
    public static function guessType($query)
    {
        if (QueryUtils::validIp($query, 'ipv4', false)) {
            return (QueryUtils::validIp($query, 'ipv4')) ? self::QTYPE_IPV4 : self::QTYPE_UNKNOWN;
        } elseif (QueryUtils::validIp($query, 'ipv6', false)) {
            return (QueryUtils::validIp($query, 'ipv6')) ? self::QTYPE_IPV6 : self::QTYPE_UNKNOWN;
        } elseif (QueryUtils::validDomain($query)) {
            return self::QTYPE_DOMAIN;
        // TODO: replace with AS validator
        } elseif ($query && is_string($query) && strpos($query, '.') === false) {
            return self::QTYPE_AS;
        } else {
            return self::QTYPE_UNKNOWN;
        }
    }
}