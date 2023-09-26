<?php

declare(strict_types = 1);

namespace Drupal\stomp\Event;

use Drupal\Component\EventDispatcher\Event;
use Stomp\Transport\Map;
use Stomp\Transport\Message;

/**
 * Message event.
 */
final class MessageEvent extends Event {

  /**
   * Constructs a new instance.
   *
   * @param \Stomp\Transport\Message $message
   *   The message.
   */
  public function __construct(
    public Message $message,
  ) {
  }

  public static function create(mixed $body) : self {
    if ($body instanceof Message) {
      return new self($body);
    }
    $message = is_array($body) ? new Map($body) : new Message($body);

    return new self($message);
  }

}
