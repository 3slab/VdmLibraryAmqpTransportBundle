<?php

/**
 * @package    3slab/VdmLibraryAmqpTransportBundle
 * @copyright  2020 Suez Smart Solutions 3S.lab
 * @license    https://github.com/3slab/VdmLibraryAmqpTransportBundle/blob/master/LICENSE
 */

namespace Vdm\Bundle\LibraryAmqpTransportBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpReceivedStamp;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Vdm\Bundle\LibraryBundle\Model\Message;

// Didn't extend the original AmqpReceiver, since half is code it private.
class AmqpReceiver implements ReceiverInterface, MessageCountAwareInterface
{
    private $serializer;
    private $connection;
    private $nackFlag;

    public function __construct(
        Connection $connection,
        SerializerInterface $serializer = null,
        int $nackFlag = AMQP_NOPARAM
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
        $this->nackFlag = $nackFlag;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        foreach ($this->connection->getQueueNames() as $queueName) {
            yield from $this->getEnvelope($queueName);
        }
    }

    protected function getEnvelope(string $queueName): iterable
    {
        try {
            $amqpEnvelope = $this->connection->get($queueName);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        if (null === $amqpEnvelope) {
            return;
        }

        $body = $amqpEnvelope->getBody();

        try {
            // Allow this transport to handle retried messages
            $envelope = $this->serializer->decode([
                'body' => false === $body ? '' : $body, // workaround https://github.com/pdezwart/php-amqp/issues/351
                'headers' => $amqpEnvelope->getHeaders(),
            ]);
        } catch (MessageDecodingFailedException $exception) {
            $body = json_decode($body, true);

            if (\json_last_error()) {
                $this->rejectAmqpEnvelope($amqpEnvelope, $queueName);

                $msg = sprintf(
                    'Failed to decode json message: %s (err code %s). Original message content: %s',
                    \json_last_error(),
                    \json_last_error_msg(),
                    $amqpEnvelope->getBody()
                );

                throw new MessageDecodingFailedException($msg);
            }

            $message  = new Message($body);
            $envelope = new Envelope($message);
        }

        yield $envelope->with(new AmqpReceivedStamp($amqpEnvelope, $queueName));
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function ack(Envelope $envelope): void
    {
        try {
            $stamp = $this->findAmqpStamp($envelope);

            $this->connection->ack(
                $stamp->getAmqpEnvelope(),
                $stamp->getQueueName()
            );
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function reject(Envelope $envelope): void
    {
        $stamp = $this->findAmqpStamp($envelope);

        $this->rejectAmqpEnvelope(
            $stamp->getAmqpEnvelope(),
            $stamp->getQueueName()
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getMessageCount(): int
    {
        try {
            return $this->connection->countMessagesInQueues();
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function rejectAmqpEnvelope(\AMQPEnvelope $amqpEnvelope, string $queueName): void
    {
        try {
            $this->connection->nack($amqpEnvelope, $queueName, $this->nackFlag);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function findAmqpStamp(Envelope $envelope): AmqpReceivedStamp
    {
        $amqpReceivedStamp = $envelope->last(AmqpReceivedStamp::class);
        if (null === $amqpReceivedStamp) {
            throw new LogicException('No "AmqpReceivedStamp" stamp found on the Envelope.');
        }

        return $amqpReceivedStamp;
    }
}
