<?php

declare(strict_types = 1);

namespace Drupal\stomp\Queue;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class QueueFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  private array $queues = [];

  public function __construct(
    private readonly Settings $settings,
  ) {
  }

  public function get(string $name) : Stomp {
    if (!isset($this->queues[$name])) {
      $service = 'stomp.queue.' . $name;

      if (!$this->container->has($name)) {
        $stompServices = $this->settings->get('stomp', []);

        if (!empty($stompServices)) {
          $service = 'stomp.queue.' . array_key_first($stompServices);
        }
      }

      $this->queues[$name] = $this->container->get($service);
    }

    return $this->queues[$name];
  }

}
