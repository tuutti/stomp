<?php

declare(strict_types = 1);

namespace Drupal\stomp\Queue;

use Stomp\Transport\Frame;

/**
 * A DTO to store queue items.
 */
final class Item {

  /**
   * Constructs a new instance.
   *
   * @param int|string $id
   *   The message id.
   * @param mixed $data
   *   The decoded data.
   * @param \Stomp\Transport\Frame $message
   *   The message.
   */
  public function __construct(
    public readonly int|string $id,
    public readonly mixed $data,
    public readonly Frame $message,
  ) {
  }

}
