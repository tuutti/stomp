<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Stomp\Client;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;

/**
 * A factory to construct STOMP client objects.
 */
final class StompFactory {

  /**
   * Constructs a new STOMP client.
   *
   * @param \Drupal\stomp\Configuration $connection
   *   The connection parameters.
   *
   * @return \Stomp\Client
   *   The STOMP client.
   */
  public function create(Configuration $connection) : Client {
    $client = (new Client($connection->brokers))
      ->setClientId($connection->clientId);

    if ($connection->login) {
      $client->setLogin($connection->login, $connection->passcode);
    }
    $clientConnection = $client->getConnection();

    if ($connection->heartbeat) {
      $send = $connection->heartbeat['send'] ?? 0;
      $receive = $connection->heartbeat['receive'] ?? 0;

      $client->setHeartbeat($send, $receive);

      $observer = $send > 0 ?
        new HeartbeatEmitter($clientConnection) :
        new ServerAliveObserver();

      $clientConnection->getObservers()->addObserver($observer);
    }

    if (isset($connection->timeout['read'])) {
      $readTimeout = $connection->timeout['read'];

      $seconds = ($readTimeout - ($readTimeout % 1000)) / 1000;
      $microseconds = ($readTimeout % 1000) * 1000;
      $clientConnection->setReadTimeout($seconds, $microseconds);
    }

    if (isset($connection->timeout['write'])) {
      $clientConnection->setWriteTimeout($connection->timeout['write']);
    }
    return $client;
  }

}
