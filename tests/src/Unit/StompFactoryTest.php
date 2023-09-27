<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\stomp\Configuration;
use Drupal\stomp\StompFactory;
use Drupal\Tests\UnitTestCase;
use Stomp\Network\Observer\HeartbeatEmitter;

/**
 * Tests service provider.
 */
class StompFactoryTest extends UnitTestCase {

  /**
   * Tests factory callback.
   */
  public function testCreate() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', user: 'user1', pass: 'pass1');
    $client = (new StompFactory())
      ->create($connection);

    $this->assertEquals('client', $client->getClientId());

    // Make sure login credentials are set.
    $reflection = new \ReflectionClass($client);
    $this->assertEquals('user1', $reflection->getProperty('login')->getValue($client));
    $this->assertEquals('pass1', $reflection->getProperty('passcode')->getValue($client));

    $observers = $client->getConnection()->getObservers()->getObservers();
    $this->assertInstanceOf(HeartbeatEmitter::class, $observers[0]);
  }

  /**
   * Tests that heartbeat can be disabled.
   */
  public function testNoHeartbeat() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', heartbeat: []);
    $client = (new StompFactory())
      ->create($connection);
    $observers = $client->getConnection()->getObservers()->getObservers();
    $this->assertEmpty($observers);
  }

}
