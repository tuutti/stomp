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

  /**
   * Constructs a corresponding message object for the given message type.
   *
   * @param mixed $body
   *   The body.
   *
   * @return self
   *   The self.
   *
   * @throws \InvalidArgumentException
   */
  public static function create(mixed $body) : self {
    if ($body instanceof Message) {
      return new self($body);
    }

    if (is_scalar($body)) {
      return new self(new Message((string) $body));
    }
    elseif (is_array($body)) {
      return new self(new Map($body));
    }
    throw new \InvalidArgumentException('Invalid message type.');
  }

}
