<?php

declare(strict_types = 1);

namespace Drupal\stomp\Consumer;

/**
 * A DTO to store options for Consumer.
 */
class Options {

  /**
   * Constructs a new instance.
   *
   * @param int $lease
   *   The lease.
   * @param int $itemLimit
   *   The item limit.
   */
  public function __construct(
    public readonly int $lease = 3600,
    public readonly int $itemLimit = 0,
  ) {
  }

}
