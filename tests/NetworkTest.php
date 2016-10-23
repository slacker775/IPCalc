<?php
use PHPUnit\Framework\TestCase;

use IPCalc\Network;

class NetworkTest extends TestCase
{
    public function testCreateNetwork()
    {
        $net = new Network('192.168.1.0/24');
        $this->assertEquals(24, $net->getMask());
        return $net;
    }
    
    /**
     * @depends testCreateNetwork
     * @covers \IPCalc\Network::contains
     * @param Network $net
     */
    public function testContains(Network $net)
    {
        $this->assertTrue($net->contains('192.168.1.15'));
        $this->assertFalse($net->contains('10.3.4.5'));
    }
    
    /**
     * @depends testCreateNetwork
     * @covers \IPCalc\Network::size
     * @covers \IPCalc\Network::firstHost
     * @covers \IPCalc\Network::lastHost
     * @param Network $net
     */
    public function testNetworkSize(Network $net)
    {
        $this->assertEquals(256, $net->size());
        $host = $net->firstHost();
        $this->assertTrue($host->eq('192.168.1.1'));
        
        $host = $net->lastHost();
        $this->assertTrue($host->eq('192.168.1.254'));        
    }
}