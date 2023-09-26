<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;
use Drupal\stomp\Queue\QueueFactory;
use Drupal\stomp\Queue\Stomp;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Dynamically register configured STOMP queue services.
 */
final class StompServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) : void {
    $settings = Settings::get('stomp', []);
    foreach ($settings as $key => $value) {
      // Definition's setArguments method doesn't support array
      // spreading, so we have to construct the connection object and
      // pass the arguments manually to utilize the default values.
      $connection = new Connection(...$value);
      $connectionService = new Definition(Connection::class, [
        $connection->clientId,
        $connection->user,
        $connection->pass,
        $connection->randomize,
        $connection->brokers,
      ]);
      $container->setDefinition('stomp.connection.' . $key, $connectionService);

      $stompFactory = new Definition(StompFactory::class, [
        new Reference('stomp.connection.' . $key),
      ]);
      $stompFactory->setFactory([new Reference('stomp.factory'), 'create']);

      $queue = new Definition(Stomp::class, [
        $stompFactory,
        new Reference('event_dispatcher'),
        new Reference('logger.channel.stomp'),
        $key,
        $connection->logLevel,
      ]);
      $container->setDefinition('stomp.queue.' . $key, $queue);

      $queueFactory = new Definition(QueueFactory::class, [
        new Reference('settings'),
      ]);
      $queueFactory->addMethodCall('setContainer', [new Reference('service_container')]);
      $container->setDefinition('queue.stomp.' . $key, $queueFactory);
    }

  }

}
