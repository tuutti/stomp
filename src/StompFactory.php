<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Stomp\Client;
use Stomp\Network\Observer\ConnectionObserver;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;

/**
 * A factory to construct STOMP client objects.
 */
final class StompFactory {

  /**
   * Constructs a new STOMP client.
   *
   * @param \Drupal\stomp\Configuration $configuration
   *   The connection parameters.
   *
   * @return \Stomp\Client
   *   The STOMP client.
   */
  public function create(Configuration $configuration) : Client {
    $client = (new Client($configuration->brokers))
      ->setClientId($configuration->clientId);

    if ($configuration->login && $configuration->passcode) {
      $client->setLogin($configuration->login, $configuration->passcode);
    }
    $clientConnection = $client->getConnection();

    if (isset($configuration->timeout['read'])) {
      $readTimeout = $configuration->timeout['read'];

      $seconds = ($readTimeout - ($readTimeout % 1000)) / 1000;
      $microseconds = ($readTimeout % 1000) * 1000;
      $clientConnection->setReadTimeout($seconds, $microseconds);
    }

    if (isset($configuration->timeout['write'])) {
      $clientConnection->setWriteTimeout($configuration->timeout['write']);
    }

    if ($configuration->heartbeat) {
      $this->setHeartbeat($client, $configuration);
    }

    return $client;
  }

  /**
   * Configures the heartbeat.
   *
   * @param \Stomp\Client $client
   *   The client.
   * @param \Drupal\stomp\Configuration $configuration
   *   The configuration.
   */
  private function setHeartbeat(Client $client, Configuration $configuration) : void {
    $client->setHeartbeat(
      $configuration->heartbeat['send'] ?? 0,
      $configuration->heartbeat['receive'] ?? 0,
    );

    $defaultCallbacks = [
      HeartbeatEmitter::class => fn(Client $client) : HeartbeatEmitter => new HeartbeatEmitter($client->getConnection()),
      ServerAliveObserver::class => fn() : ServerAliveObserver => new ServerAliveObserver(),
    ];

    foreach ($configuration->heartbeat['observers'] as $settings) {
      $settings['callback'] ??= NULL;

      [
        'callback' => $callback,
        'class' => $class,
      ] = $settings;

      $class = ltrim($class, '\\');

      if (!is_callable($callback)) {
        if (!isset($defaultCallbacks[$class])) {
          throw new \LogicException('No default callback found.');
        }
        $callback = $defaultCallbacks[$class];
      }
      $observer = $callback($client, $configuration, $settings);

      if (!$observer instanceof ConnectionObserver) {
        throw new \LogicException(
          sprintf('The observer must be an instance of "%s".', ConnectionObserver::class)
        );
      }
      $client->getConnection()->getObservers()->addObserver($observer);
    }
  }

}
