<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Vdm\Bundle\LibraryAmqpTransportBundle\Transport\AmqpTransport;

class AmqpTransportFactory implements TransportFactoryInterface
{
    protected const DSN_PROTOCOL_AMQP = 'vdm+amqp://';

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        $nackFlag = $options['nack_flag'] ?? 0;
        unset($options['nack_flag']);

        return new AmqpTransport($this->logger, Connection::fromDsn($dsn, $options), $serializer, $nackFlag);
    }

    public function supports(string $dsn, array $options): bool
    {
        return (0 === strpos($dsn, static::DSN_PROTOCOL_AMQP));
    }
}
