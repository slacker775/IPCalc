<?php
use PHPUnit\Framework\TestCase;

use IPCalc\Address;

class AddressTest extends TestCase
{
    
    /**
     * @expectedException IPCalc\Exception\ValueError
     */
    public function testIPv4Exception()
    {
        $a = new Address('1.1.1.1', 56);
    }
    
    public function testCreateWithCIDRMask()
    {
        $a = new Address('192.168.1.1', 23);
        $this->assertEquals(23, $a->getMask());
        $this->assertEquals(Address::IPv4, $a->getVersion());
    }
    
    public function testCreateWithDQMask()
    {
        $a = new Address('10.10.0.0/255.255.240.0');
        $this->assertEquals('10.10.0.0', $a->getDq());
        $this->assertEquals(20, $a->getMask());
    }
    
    /**
     * @covers \IPCalc\Address::__construct
     * @covers \IPCalc\Address::getDq
     * @covers \IPCalc\Address::getMask
     * @covers \IPCalc\Address::getVersion
     * @covers \IPCalc\Address::validNetmask
     * @return \IPCalc\Address
     */
    public function testIPv4()
    {
        $a = new Address('192.168.1.10/24');
        $this->assertEquals('192.168.1.10', $a->getDq());
        $this->assertEquals(24, $a->getMask());
        return $a;
    }
    
    /**
     * @depends testIPv4
     * @covers \IPCalc\Address::lt
     * @covers \IPCalc\Address::eq
     * @covers \IPCalc\Address::le
     * @covers \IPCalc\Address::gt
     * @param Address $a
     */
    public function testAddressEquality(Address $a)
    {
        $this->assertTrue($a->lt('192.168.100.254'));
        $this->assertFalse($a->lt('10.1.1.1'));
        
        $this->assertTrue($a->eq('192.168.1.10'));
        $this->assertFalse($a->eq('140.2.15.5'));
        
        $this->assertFalse($a->le('192.168.1.1'));
        $this->assertTrue($a->le('192.168.1.10'));
        
        $this->assertTrue($a->gt('172.15.2.1'));
        $this->assertFalse($a->gt('192.168.254.254'));
    }
    
    /**
     * @depends testIPv4
     * @covers \IPCalc\Address::add
     * @covers \IPCalc\Address::sub
     * @param Address $a
     */
    public function testAddSub(Address $a)
    {
        $b = $a->add(1);
        $this->assertEquals('192.168.1.11', (string)$b);
        
        $b = $a->sub(5);
        $this->assertEquals('192.168.1.5', (string)$b);
    }
}