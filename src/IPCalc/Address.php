<?php declare(strict_types=1);

namespace IPCalc;

use IPCalc\Exception\ValueError;

class Address
{

    const IPv4 = 4;

    const IPv6 = 6;

    const MAX_IPV4 = (1 << 32) - 1;

    const MAX_IPV6 = (1 << 128) - 1;

    const BASE_6TO4 = (0x2002 << 112);

    protected $_range
        = [
            4 => [
                '00000000'                 => 'THIS HOST',
                '00001010'                 => 'PRIVATE',  /* 10/8 */
                '0110010001'               => 'SHARED ADDRESS SPACE',
                /* 100.64/10 */
                '011111111'                => 'LOOPBACK', /* 127/8 */
                '101011000001'             => 'PRIVATE', /* 172.16/12 */
                '110000000000000000000000' => 'IETF PROTOCOL', /* 192/24 */
                '110000000000000000000010' => 'TEST-NET-1', /* 192.0.2/24 */
                '110000000101100001100011' => '6TO4-RELAY ANYCAST',
                /* 192.88.99/24 */
                '1100000010101000'         => 'PRIVATE', /* 192.168/16 */
                '110001100001001'          => 'BENCHMARKING', /* 198.18/15 */
                '110001100011001'          => 'TEST-NET-2', /* 198.51.100/24 */
                '110010110000000'          => 'TEST-NET-3', /* 203.0.113/24 */
                '1111'                     => 'RESERVED', /* 240/4 */
            ],
            6 => [

            ],

        ];

    /**
     * IP address in integer format
     */
    protected int $ip;

    /**
     * Address in dotted-quad notation
     */
    protected string $dq;

    /**
     * CIDR mask length
     */
    protected ?int $mask;

    /**
     * IP address version
     */
    protected int $version;

    public function __construct($address, int $netmask = null,
        int $version = Address::IPv4
    ) {
        $this->mask = $netmask;
        $this->version = 0;

        if (is_null($address) || $address === '') {
            throw new Exception\ValueError('Invalid IP address specified');
        }

        if ($version != Address::IPv4 && $version != Address::IPv6) {
            throw new Exception\ValueError(
                'Invalid IP version specified: ' . $version
            );
        }

        if ($address instanceof Address) {
            /* Copy the existing object */
            $this->ip = $address->getAddress();
            $this->dq = $address->getDq();
            $this->mask = $address->getMask();
            $this->version = $address->getVersion();
        } else {
            if (is_int($address)) {
                $this->ip = intval($address);
                $this->dq = long2ip($this->ip);
                $this->version = $version;
            } else {
                /* If address specified in CIDR notation x.x.x.x/Y */
                if (strpos($address, '/')) {
                    $data = explode('/', $address);
                    $mask = $data[1];
                    if ($this->validNetmask($mask)) {
                        $this->mask = $this->maskToCIDR($mask);
                    } else {
                        $this->mask = intval($data[1]);
                    }
                    $address = $data[0];
                }
                $this->dq = $address;
                $this->ip = ip2long($this->dq);
                $this->version = $version;
            }
        }

        if (is_int($netmask)) {
            $this->mask = $netmask;
        }

        /* If no netmask specified, assume host address */
        if (is_null($this->mask)) {
            $this->mask = $this->version == Address::IPv4 ? 32 : 128;
        }

        if ($this->validNetmask($netmask)) {
            $this->mask = $this->maskToCIDR($netmask);
        }

        if ($this->getVersion() == Address::IPv4) {
            if ($this->mask < 0 || $this->mask > 32) {
                throw new ValueError(
                    'IPv4 subnet size must be between 0 and 32'
                );
            }
        } else {
            if ($this->getVersion() == Address::IPv6) {
                if ($this->mask < 0 || $this->mask > 128) {
                    throw new ValueError(
                        'IPv6 subnet size must be between 0 and 128'
                    );
                }
            }
        }
    }

    /**
     * Get the IP address as an integer
     */
    public function getAddress(): int
    {
        return $this->ip;
    }

    /**
     * Return the IP address in dotted-quad notation
     */
    public function getDq(): string
    {
        return $this->dq;
    }

    /**
     * Get the CIDR mask length
     */
    public function getMask(): ?int
    {
        return $this->mask;
    }

    /**
     * Get the IP address version
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    protected function validNetmask($netmask): bool
    {
        if (is_string($netmask) === false) {
            return false;
        }
        $netmask = ip2long($netmask);
        if ($netmask === false) {
            return false;
        }

        $neg = ((~(int)$netmask) & 0xffffffff);
        return (($neg + 1) & $neg) === 0;
    }

    protected function maskToCIDR(string $netmask): int
    {
        if ($this->validNetmask($netmask)) {
            return $this->countSetBits(ip2long($netmask));
        }
        throw new ValueError('Invalid netmask');
    }

    protected function countSetBits(int $int): int
    {
        $int = $int & 0xffffffff;
        $int = ($int & 0x55555555) + (($int >> 1) & 0x55555555);
        $int = ($int & 0x33333333) + (($int >> 2) & 0x33333333);
        $int = ($int & 0x0f0f0f0f) + (($int >> 4) & 0x0f0f0f0f);
        $int = ($int & 0x00ff00ff) + (($int >> 8) & 0x00ff00ff);
        $int = ($int & 0x0000ffff) + (($int >> 16) & 0x0000ffff);
        $int = $int & 0x0000003f;
        return $int;
    }

    /**
     * Is this address less than the passed address
     */
    public function lt(string $other): bool
    {
        $ip = new Address($other);
        return $this->getAddress() < $ip->getAddress();
    }

    /**
     * Is this address less-than-or-equal the passed address
     */
    public function le(string $other): bool
    {
        $ip = new Address($other);
        return $this->getAddress() <= $ip->getAddress();
    }

    /**
     * Is this address greater-than the passed address
     */
    public function gt(string $other): bool
    {
        $ip = new Address($other);
        return $this->getAddress() > $ip->getAddress();
    }

    /**
     * Is this address greater-than-or-equal the passed address
     */
    public function ge(string $other): bool
    {
        $ip = new Address($other);
        return $this->getAddress() >= $ip->getAddress();
    }

    /**
     * Is this address equal the passed address
     */
    public function eq(string $other): bool
    {
        $ip = new Address($other);
        return $this->getAddress() == $ip->getAddress();
    }

    public function info(): string
    {
        return 'UNDEF';
    }

    /**
     * Return the CIDR subnet size
     */
    public function subnet(): int
    {
        return $this->mask;
    }

    public function add(int $offset): Address
    {
        if (!is_int($offset)) {
            throw new ValueError('Offset is not numeric');
        }
        return new Address(
            $this->ip + $offset, $this->mask, $this->getVersion()
        );
    }

    public function sub(int $offset): Address
    {
        if (!is_int($offset)) {
            throw new ValueError('Offset is not numeric');
        }
        return new Address(
            $this->ip - $offset, $this->mask, $this->getVersion()
        );
    }

    public function __toString()
    {
        return (string)$this->getDq();
    }

    protected function CIDRToMask(int $int): string
    {
        return long2ip(-1 << (32 - (int)$int));
    }

    protected function validIP(string $ip): bool
    {
        return filter_var(
            $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 & FILTER_FLAG_IPV6
        );
    }

}