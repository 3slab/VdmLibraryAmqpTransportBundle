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
use Vdm\Bundle\LibraryAmqpTransportBundle\Transport\AmqpTransport;

class AmqpTransportFactoryTest extends TestCase
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
     * @var AmqpTransport $amqpTransportFactory
     */
    private $amqpTransportFactory;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->amqpTransportFactory = new AmqpTransportFactory($this->logger);
    }

    public function testCreateTransport()
    {
        $dsn = "vdm+amqp://localhost:9200";
        $options = [];

        $transport = $this->amqpTransportFactory->createTransport($dsn, $options, $this->serializer);

        $this->assertInstanceOf(AmqpTransport::class, $transport);
    }    

    /**
     * @dataProvider dataProviderTestSupport
     */
    public function testSupports($dsn, $value)
    {
        $bool = $this->amqpTransportFactory->supports($dsn, []);

        $this->assertEquals($bool, $value);
    }

    public function dataProviderTestSupport()
    {
        yield [
            "vdm+amqp://localhost:9200",
            true
        ];
        yield [
            "https://ipconfig.io/json",
            false
        ];

    }
}
