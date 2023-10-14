<?php

declare(strict_types = 1);

namespace Drupal\stomp\Drush\Commands;

use Drupal\stomp\Consumer\ConsumerInterface;
use Drupal\stomp\Consumer\Options;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A drush commands file to provide STOMP queue commands.
 */
final class QueueCommands extends DrushCommands {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\stomp\Consumer\ConsumerInterface $consumer
   *   The consumer service.
   * @param \Symfony\Component\Console\Style\SymfonyStyle|null $io
   *   The IO.
   */
  public function __construct(
    private readonly ConsumerInterface $consumer,
    SymfonyStyle $io = NULL,
  ) {
    parent::__construct();

    if ($io) {
      $this->io = $io;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('stomp.consumer'),
    );
  }

  /**
   * Runs a specific queue by name.
   *
   * @param string $queue
   *   The name of the queue.
   * @param array $options
   *   The options.
   *
   * @return int
   *   The exit code.
   */
  #[CLI\Command(name: 'stomp:worker')]
  #[CLI\Argument(name: 'queue', description: 'The name of the queue to run.')]
  #[CLI\Option(name: 'lease-time', description: 'The maximum number of seconds that an item remains claimed.')]
  #[CLI\Option(name: 'items-limit', description: 'The maximum number of items allowed to run the queue.')]
  public function worker(string $queue, array $options = [
    'lease-time' => 3600,
    'items-limit' => 0,
  ]) : int {

    try {
      $this->consumer
        ->process($queue,
          new Options(
            lease: (int) $options['lease-time'],
            itemLimit: (int) $options['items-limit']
          )
        );
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());

      return DrushCommands::EXIT_FAILURE;
    }
    return DrushCommands::EXIT_SUCCESS;
  }

}
