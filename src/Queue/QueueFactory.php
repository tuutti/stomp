<?php

declare(strict_types = 1);

namespace Drupal\stomp\Queue;

use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * A factory class to construct STOMP queues.
 */
final class QueueFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * A static cache to store initialized queue services.
   *
   * @var array
   */
  private array $queues = [];

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(
    private readonly Settings $settings,
  ) {
  }

  /**
   * Gets the queue service.
   *
   * @param string $name
   *   The queue name.
   *
   * @return \Drupal\stomp\Queue\Stomp
   *   The STOMP queue service.
   */
  public function get(string $name) : Stomp {
    if (!isset($this->queues[$name])) {
      $service = 'stomp.queue.' . $name;

      $defaultQueueService = $this->settings->get('queue_default');
      // Fallback to first available queue when 'queue_default' setting
      // is set to use stomp and no queue specific configuration is found.
      if (!$this->container->has($name) && str_starts_with($defaultQueueService, 'queue.stomp.')) {
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
