<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Stomp\Client;
use Stomp\Network\Observer\HeartbeatEmitter;

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

    if ($connection->heartbeat) {
      $client->setHeartbeat($connection->heartbeat['send']);

      $clientConnection = $client->getConnection();
      $clientConnection
        ->getObservers()
        ->addObserver(new HeartbeatEmitter($clientConnection));
      $clientConnection->setReadTimeout(0, $connection->heartbeat['readTimeout']['microseconds']);
    }

    return $client;
  }

}
