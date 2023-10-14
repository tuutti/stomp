<?php

declare(strict_types = 1);

namespace Drupal\stomp\Consumer;

/**
 * Consumer interface.
 */
interface ConsumerInterface {

  /**
   * Consume messages from STOMP service.
   *
   * @param string $name
   *   The queue.
   * @param \Drupal\stomp\Consumer\Options $options
   *   The consumer options.
   *
   * @throws \Drupal\stomp\Exception\ConsumerException
   */
  public function process(string $name, Options $options) : void;

}
