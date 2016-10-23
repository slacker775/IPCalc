<?php
namespace IPCalc;

use IPCalc\Address;

class Network extends Address implements \Iterator
{
    protected $postition;
    
    public function __construct($address, $netmask = null, $version = Address::IPv4)
    {
        parent::__construct($address, $netmask, $version);
        $this->position = 1;
    }
    
    /**
     * Return the network Netmask as an Address object
     * 
     * @return 
     */
    public function netmask()
    {
        return new Address($this->netmaskLong(), null, $this->getVersion());
    }
    
    /**
     * Get the netmask as an integer
     * @return number
     */
    public function netmaskLong()
    {
        if($this->getVersion() == Address::IPv4)
            return (Address::MAX_IPV4 >> (32 - $this->mask)) << (32 - $this->mask);
        else
            return (Address::MAX_IPV6 >> (128 - $this->mask)) << (128 - $this->mask);
    }
    
    /**
     * Return this network as an Address object
     * 
     * @return \IP\Address
     */
    public function network()
    {
        return new Address($this->networkLong(), null, $this->getVersion());
    }
    
    /**
     * Return the network address as a longint
     * 
     * @return number
     */
    public function networkLong()
    {
        return $this->ip & $this->netmaskLong();
    }
    
    /**
     * Get the network broadcast address
     * 
     * @return \IP\Address
     */
    public function broadcast()
    {
        return new Address($this->broadcastLong(), null, $this->getVersion());
    }
    
    /**
     * Return the broadcast address for this network as a longint
     * 
     * @return number
     */
    public function broadcastLong()
    {
        if($this->getVersion() == Address::IPv4)
            return $this->networkLong() | (Address::MAX_IPV4 - $this->netmaskLong());
        else
            return $this->networkLong() | (Address::MAX_IPV6 - $this->netmaskLong());
    }

    /**
     * Determine if passed network is within this network
     * 
     * @param string|Address $other
     * @return boolean
     */
    public function checkCollision($other)
    {
        $othernet = new Network($other);
        
        return ((($this->networkLong() <= $othernet->networkLong()) && ($othernet->networkLong() <= $this->broadcastLong())) || 
        (($othernet->networkLong() <= $this->networkLong()) && ($this->networkLong() <= $othernet->broadcastLong())));
    }
    
    /**
     * 
     * @param string|Address $address
     * @return boolean
     */
    public function contains($address)
    {
        return $this->checkCollision($address);
    }
      
    /**
     * Return the number of host addresses available within this network
     * 
     * @return number
     */
    public function size()
    {
        $base = $this->getVersion() == Address::IPv4 ? 32 : 128;
        $base -= $this->mask;
        return pow(2,$base);
    }
    
    /**
     * Return the first available host address in this network
     * 
     * @return \IP\Network|\IP\Address
     */
    public function firstHost()
    {
        if(($this->getVersion() == Address::IPv4 && $this->mask > 30) || ($this->version == Address::IPv6 && $this->mask > 126))
            return $this;
        else 
            return new Address($this->networkLong() + 1, null, $this->getVersion());     
    }

    /**
     * Return the last available host address in this network
     * 
     * @return \IP\Network|\IP\Address
     */
    public function lastHost()
    {
        if (($this->getVersion() == Address::IPv4 && $this->mask == 32) || ($this->version == Address::IPv6 && $this->mask == 128)) {
            return $this;
        } else 
            if (($this->getVersion() == Address::IPv4 && $this->mask == 31) || ($this->getVersion() == Address::IPv6 && $this->mask == 127)) {
                return new Address($this + 1, null, $this->getVersion());
            } else {
                return new Address($this->broadcastLong() - 1, null, $this->getVersion());
            }
    }    
       
    public function __toString()
    {
        return sprintf("%s/%s", $this->dq, $this->mask);
    }
    
    /* Iterator methods */
    /**
     * 
     * {@inheritDoc}
     * @see Iterator::current()
     */
    public function current()
    {
        return new Address($this->networkLong() + $this->position, $this->mask, $this->getVersion());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Iterator::next()
     */
    public function next()
    {
        return new Address($this->networkLong() + $this->position++, $this->mask, $this->getVersion());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->position = 1;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Iterator::valid()
     */
    public function valid()
    {
        if($this->position >= $this->size() - 1 || $this->position <= 0)
            return false;
        return true;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Iterator::key()
     */
    public function key()
    {
        return $this->position;
    }   
    
}