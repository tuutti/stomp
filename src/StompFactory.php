<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Stomp\Client;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;
use Stomp\StatefulStomp;

final class StompFactory {

  public function create(Connection $connection) : Client {
    $key = array_key_first($connection->brokers);

    if (!isset($connection->brokers[$key])) {
      throw new \InvalidArgumentException('Invalid "brokers" configuration.');
    }
    $brokers = $connection->brokers[$key];

    if (count($connection->brokers) > 1) {
      $brokers = sprintf('failover://(%s)', implode(',', $connection->brokers));

      if ($connection->randomize) {
        $brokers = sprintf('%s?randomize=true', $brokers);
      }
    }
    $client = new Client($brokers);
    $client->setClientId($connection->clientId);

    if ($connection->user) {
      $client->setLogin($connection->user, $connection->pass);
    }
    /*$client->setHeartbeat(0, 500);
    $observer = new ServerAliveObserver();
    $client->getConnection()->getObservers()->addObserver($observer);*/
    /*$client->setHeartbeat(500);
    $client->getConnection()->setReadTimeout(0, 250000);
    // We add a HeartBeatEmitter and attach it to the connection to
    // automatically send these signals.
    $emitter = new HeartbeatEmitter($client->getConnection());
    $client->getConnection()->getObservers()->addObserver($emitter);*/

    return $client;
  }

}
