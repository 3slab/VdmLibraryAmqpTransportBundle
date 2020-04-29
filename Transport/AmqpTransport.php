<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpSender;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Vdm\Bundle\LibraryAmqpTransportBundle\Transport\AmqpReceiver;

// Didn't extend the original AmqpTransport, since half is code it private.
class AmqpTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ReceiverInterface
     */
    protected $receiver;

    /**
     * @var SenderInterface
     */
    protected $sender;

    /**
     * @var int
     */
    protected $nackFlag;

    /**
     * @param LoggerInterface $logger
     * @param Connection $connection
     * @param SerializerInterface|null $serializer
     * @param int $nackFLag
     */
    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        SerializerInterface $serializer = null,
        int $nackFLag = 0
    ) {
        $this->logger     = $logger ?? new NullLogger();
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
        $this->nackFlag   = $nackFLag;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        $this->logger->info("AmqpTransport simple json starts running");

        $receiver = $this->receiver ?? $this->getReceiver();

        return $receiver->get();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function ack(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->ack($envelope);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function reject(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->reject($envelope);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function send(Envelope $envelope): Envelope
    {
        return ($this->sender ?? $this->getSender())->send($envelope);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setup(): void
    {
        $this->connection->setup();
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCount(): int
    {
        return ($this->receiver ?? $this->getReceiver())->getMessageCount();
    }

    protected function getReceiver(): AmqpReceiver
    {
        return $this->receiver = new AmqpReceiver($this->connection, $this->serializer, $this->nackFlag);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getSender(): AmqpSender
    {
        return $this->sender = new AmqpSender($this->connection, $this->serializer);
    }
}
