<?php

declare(strict_types = 1);

namespace Drupal\stomp\Queue;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\ReliableQueueInterface;
use Drupal\stomp\Event\MessageEvent;
use Psr\Log\LoggerInterface;
use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Transport\Frame;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A service to interact with STOMP server.
 */
final class Stomp implements ReliableQueueInterface {

  /**
   * The durable subscription service.
   *
   * @var \Stomp\Broker\ActiveMq\Mode\DurableSubscription|null
   */
  private ?DurableSubscription $durableSubscription = NULL;

  /**
   * Constructs a new instance.
   *
   * @param \Stomp\Client $stompClient
   *   The STOMP client.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param string $queue
   *   The queue name.
   * @param int $logLevel
   *   The desired minimum log level.
   */
  public function __construct(
    private readonly Client $stompClient,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly LoggerInterface $logger,
    private readonly string $queue,
    private readonly int $logLevel,
  ) {
  }

  /**
   * Gets the queue name.
   *
   * @return string
   *   The queue.
   */
  private function getQueue() : string {
    return sprintf('/queue/%s', $this->queue);
  }

  /**
   * Disconnect the client.
   */
  public function __destruct() {
    $this->stompClient->disconnect();
  }

  /**
   * Initializes a "durable" connection to STOMP service.
   *
   * @return \Stomp\Broker\ActiveMq\Mode\DurableSubscription
   *   The Durable subscription.
   *
   * @throws \Stomp\Exception\StompException
   */
  private function connect() : DurableSubscription {
    if (!$this->durableSubscription) {
      $this->durableSubscription = new DurableSubscription(
        $this->stompClient,
        $this->getQueue(),
        ack: 'client',
        subscriptionId: $this->getQueue(),
      );
    }
    $this->durableSubscription->activate();
    return $this->durableSubscription;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) : bool {
    /** @var \Drupal\stomp\Event\MessageEvent $event */
    $event = $this->eventDispatcher->dispatch(MessageEvent::create($data));

    try {
      $this->connect();
      $event->message->addHeaders(['persistent' => 'true']);

      return $this->stompClient->send($this->getQueue(), $event->message);
    }
    catch (StompException $e) {
      $this->logger->error('Failed to send item to queue %queue: @message', [
        '%queue' => $this->queue,
        '@message' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() : int {
    // Stomp does not provide a way to count the number of items
    // in a queue.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 3600) : object|false {
    try {
      $message = $this->connect()->read();

      return (object) [
        'item_id' => $message ? $message->getMessageId() : NULL,
        'data' => $message,
      ];
    }
    catch (StompException $e) {
      $this->log('Failed to read item from %queue: @message', [
        '%queue' => $this->queue,
        '@message' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

  /**
   * A logger wrapper to only log messages with certain log level.
   *
   * @param string $message
   *   The message.
   * @param array $context
   *   The context.
   * @param int $level
   *   The log level.
   */
  private function log(string $message, array $context = [], int $level = RfcLogLevel::ERROR) : void {
    if ($this->logLevel < $level) {
      return;
    }
    $this->logger->log($level, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) : void {
    if (!$item->data instanceof Frame) {
      return;
    }
    $this->connect()->ack($item->data);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) : bool {
    if (!$item->data instanceof Frame) {
      return FALSE;
    }
    $this->connect()->nack($item->data);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() : void {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() : void {
  }

}
