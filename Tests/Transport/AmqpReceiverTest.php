<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\Test\Transport;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Vdm\Bundle\LibraryAmqpTransportBundle\Transport\AmqpReceiver;

class AmqpReceiverTest extends TestCase
{
    /**
     * @dataProvider dataProviderTestGet
     */
    public function testGet($ExceptionGet, $getReturn, $jsonValide)
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        // Test case we receive a message not produced by messenger
        $serializer->method('decode')->willThrowException(new MessageDecodingFailedException('decoding exception'));

        $amqpException = new \AMQPException('');
        
        $connection = $this
                            ->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['getQueueNames', 'get'])
                            ->getMock();
        $connection->method('getQueueNames')->willReturn(['test']);

        if ($ExceptionGet) {
            $connection->method('get')->willThrowException($amqpException);
            $this->expectException(TransportException::class);
        } else {
            if ($getReturn === null) {
                $connection->method('get')->willReturn($getReturn);
            } else {
                $amqpEnvelope = $this->getMockBuilder(\AMQPEnvelope::class)->disableOriginalConstructor()->setMethods(['getBody', 'getHeaders'])->getMock();
                $amqpEnvelope->method('getBody')->willReturn($getReturn);
                $amqpEnvelope->method('getHeaders')->willReturn([]);

                $connection->method('get')->willReturn($amqpEnvelope);
                if (!$jsonValide) {
                    $this->expectException(MessageDecodingFailedException::class);
                }
            }
        }

        $amqpReceiver = $this
                            ->getMockBuilder(AmqpReceiver::class)
                            ->setConstructorArgs([$connection, $serializer])
                            ->setMethods(['rejectAmqpEnvelope'])
                            ->getMock();
        $generator = $amqpReceiver->get();
        $result = $generator->current();

        if ($jsonValide) {
            $this->assertInstanceOf(Envelope::class, $result);
        } else {
            $this->assertNull($result);
        }
    }

    public function dataProviderTestGet()
    {
        yield [
            true,
            null,
            false
        ];
        yield [
            false,
            null,
            false
        ];
        yield [
            false,
            "{message:\"test\"}",
            false
        ];
        yield [
            false,
            "{\"message\":\"test\"}",
            true
        ];
    }
}
