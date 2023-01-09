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
     */
    public function testNetworkParameters(Network $net)
    {
        $netmask = $net->netmask();
        $this->assertInstanceOf(\IPCalc\Address::class, $netmask);
        $this->assertEquals('255.255.255.0', $netmask->getDq());

        $network = $net->network();
        $this->assertInstanceOf(\IPCalc\Address::class, $network);
        $this->assertEquals('192.168.1.0', $network->getDq());

        $broadcast = $net->broadcast();
        $this->assertInstanceOf(\IPCalc\Address::class, $broadcast);
        $this->assertEquals('192.168.1.255', $broadcast->getDq());

        $this->assertTrue($net->checkCollision('192.168.1.50'));

        $this->assertEquals('192.168.1.0/24', (string)$net);
    }

    public function testFirstLastHosts()
    {
        $net = new Network('172.16.1.1', 31);

        $host = $net->firstHost();
        $this->assertEquals('172.16.1.1', $host->getDq());

        $host = $net->lastHost();
        $this->assertEquals('172.16.1.2', $host->getDq());

        $net = new Network('172.16.1.1', 32);
        $host = $net->lastHost();
        $this->assertEquals('172.16.1.1', $host->getDq());
    }

    /**
     * @depends testCreateNetwork
     */
    public function testContains(Network $net)
    {
        $this->assertTrue($net->contains('192.168.1.15'));
        $this->assertFalse($net->contains('10.3.4.5'));
    }
    
    /**
     * @depends testCreateNetwork
     */
    public function testNetworkSize(Network $net)
    {
        $this->assertEquals(256, $net->size());
        $host = $net->firstHost();
        $this->assertTrue($host->eq('192.168.1.1'));
        
        $host = $net->lastHost();
        $this->assertTrue($host->eq('192.168.1.254'));        
    }

    public function testIterator()
    {
        $net = new Network('10.1.1.0', 30);
        $this->assertEquals(4, $net->size());

        $net->rewind();
        $this->assertEquals(1, $net->key());

        $network = $net->current();
        $this->assertEquals('10.1.1.1', $network->getDq());
        $this->assertEquals(1, $net->key());
        $this->assertTrue($net->valid());

        $net->next();
        $network = $net->current();
        $this->assertEquals('10.1.1.2', $network->getDq());
        $this->assertEquals(2, $net->key());
        $this->assertTrue($net->valid());

        $net->next();
        $network = $net->current();
        $this->assertEquals('10.1.1.3', $network->getDq());
        $this->assertEquals(3, $net->key());
        $this->assertTrue($net->valid());

        $net->next();
        $network = $net->current();
        $this->assertEquals('10.1.1.4', $network->getDq());
        $this->assertEquals(4, $net->key());
        $this->assertTrue($net->valid());

        $net->next();
        $network = $net->current();
        $this->assertEquals('10.1.1.5', $network->getDq());
        $this->assertEquals(5, $net->key());
        $this->assertFalse($net->valid());
    }
}