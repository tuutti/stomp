<?php

declare(strict_types = 1);

namespace Drupal\stomp\Queue;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Queue\ReliableQueueInterface;
use Drupal\stomp\Event\MessageEvent;
use Psr\Log\LoggerInterface;
use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Transport\Frame;
use Stomp\Transport\Map;
use Stomp\Transport\Message;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A service to interact with STOMP server.
 */
class Queue implements ReliableQueueInterface {

  /**
   * The item ID counter.
   *
   * @var int
   */
  private int $itemId = 1;

  /**
   * Constructs a new instance.
   *
   * @param \Stomp\Client $client
   *   The stomp client.
   * @param \Stomp\Broker\ActiveMq\Mode\DurableSubscription $durableSubscription
   *   The active mq mode.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly Client $client,
    private readonly DurableSubscription $durableSubscription,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Initializes a durable connection to STOMP service.
   *
   * @return \Stomp\Broker\ActiveMq\Mode\DurableSubscription
   *   The Durable subscription.
   *
   * @throws \Stomp\Exception\StompException
   */
  private function connect() : DurableSubscription {
    $this->durableSubscription->activate();

    return $this->durableSubscription;
  }

  /**
   * Gets the destination.
   *
   * @return string
   *   The destination.
   */
  private function getDestination() : string {
    return $this->durableSubscription->getSubscription()->getDestination();
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $data
   *   The data.
   */
  public function createItem($data) : false|int {
    try {
      /** @var \Drupal\stomp\Event\MessageEvent $event */
      $event = $this->eventDispatcher->dispatch(MessageEvent::create($data));
      $this->connect();

      $status = $this->client
        ->send($this->getDestination(), $event->message);

      return $status ? $this->itemId++ : FALSE;
    }
    catch (StompException | \InvalidArgumentException $e) {
      $this->logger->error((string) new FormattableMarkup('Failed to send item to @queue: @message', [
        '@queue' => $this->getDestination(),
        '@message' => $e->getMessage(),
      ]));
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @param int $lease_time
   *   The lease time.
   */
  public function claimItem($lease_time = 3600) : Item|false {
    try {
      $message = $this->connect()->read();

      if (!$message instanceof Frame) {
        return FALSE;
      }

      return $this->toItem($message);
    }
    catch (StompException $e) {
      $this->logger->error((string) new FormattableMarkup('Failed to read item from @queue: @message', [
        '@queue' => $this->getDestination(),
        '@message' => $e->getMessage(),
      ]));
    }
    return FALSE;
  }

  /**
   * Converts a frame object to Item.
   *
   * @param \Stomp\Transport\Frame $message
   *   The message to convert.
   *
   * @return \Drupal\stomp\Queue\Item
   *   The item object.
   */
  public function toItem(Frame $message) : Item {
    return new Item(
      id: $message->getMessageId(),
      data: $this->decodeMessage($message),
      message: $message,
    );
  }

  /**
   * Decodes the given message object.
   *
   * @param mixed $message
   *   The message to decode.
   *
   * @return mixed
   *   The decoded message.
   */
  private function decodeMessage(mixed $message) : mixed {
    if ($message instanceof Map) {
      return $message->getMap();
    }

    if ($message instanceof Message) {
      return $message->getBody();
    }
    return $message;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $item
   *   The item.
   */
  public function deleteItem($item) : void {
    if (!$item instanceof Item) {
      return;
    }
    try {
      $this->connect()->ack($item->message);
    }
    catch (StompException $e) {
      $this->logger->error((string) new FormattableMarkup('Failed to ACK message from @queue: @message', [
        '@queue' => $this->getDestination(),
        '@message' => $e->getMessage(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $item
   *   The item.
   */
  public function releaseItem($item) : bool {
    // STOMP does not support redelivering items. The item is redelivered
    // if it's not ACK'd.
    return TRUE;
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
  public function createQueue() : void {
    // STOMP queues are created on demand, so the first time an item is created
    // for a queue which does not exist, then it will be created within the
    // destination broker.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() : void {
    // STOMP does not provide a mechanism to delete queues from the source
    // broker.
  }

}
