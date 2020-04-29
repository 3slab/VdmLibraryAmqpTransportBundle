<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\Transport;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Vdm\Bundle\LibraryAmqpTransportBundle\Transport\AmqpTransport;

class AmqpTransportTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $logger
     */
    private $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $serializer
     */
    private $serializer;


    /**
     * @var AmqpTransport $amqpTransport
     */
    private $amqpTransport;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->amqpTransport = $this
                            ->getMockBuilder(AmqpTransport::class)
                            ->setConstructorArgs([$this->logger, $this->connection, $this->serializer])
                            ->setMethods(null)
                            ->getMock();
    }

    public function testGet()
    {
        $iterable = $this->amqpTransport->get();

        $this->assertNull($iterable->current());
    }

    public function testGetMessageCount()
    {
        $int = $this->amqpTransport->getMessageCount();

        $this->assertEquals(0, $int);
    }
}
