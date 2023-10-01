<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\stomp\Configuration;
use Drupal\stomp\StompFactory;
use Drupal\Tests\UnitTestCase;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;

/**
 * Tests service provider.
 *
 * @group stomp
 */
class StompFactoryTest extends UnitTestCase {

  /**
   * Tests factory callback.
   */
  public function testCreate() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', login: 'user1',
      passcode: 'pass1');
    $client = (new StompFactory())
      ->create($connection);

    $this->assertEquals('client', $client->getClientId());

    // Make sure login credentials are set.
    $reflection = new \ReflectionClass($client);
    $this->assertEquals('user1', $reflection->getProperty('login')->getValue($client));
    $this->assertEquals('pass1', $reflection->getProperty('passcode')->getValue($client));

    $observers = $client->getConnection()->getObservers()->getObservers();
    $this->assertEmpty($observers);
  }

  /**
   * Tests client side heartbeat observer.
   */
  public function testHeartbeatEmitter() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', heartbeat: [
      'send' => 1500,
      'observers' => [
        [
          'class' => HeartbeatEmitter::class,
        ],
      ],
    ]);
    $client = (new StompFactory())
      ->create($connection);
    $observers = $client->getConnection()->getObservers()->getObservers();
    $this->assertInstanceOf(HeartbeatEmitter::class, $observers[0]);
  }

  /**
   * Tests server side heartbeat observer.
   */
  public function testHeartbeatServerAliveObserver() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', heartbeat: [
      'receive' => 1500,
      'observers' => [
        [
          'class' => ServerAliveObserver::class,
        ],
      ],
    ]);
    $client = (new StompFactory())
      ->create($connection);
    $observers = $client->getConnection()->getObservers()->getObservers();
    $this->assertInstanceOf(ServerAliveObserver::class, $observers[0]);
  }

  /**
   * Tests heartbeat observer without known default callback.
   */
  public function testHeartbeatObserverNoDefaultCallback() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', heartbeat: [
      'receive' => 1500,
      'observers' => [
        [
          'class' => Configuration::class,
        ],
      ],
    ]);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No default callback found.');
    (new StompFactory())->create($connection);
  }

  /**
   * Tests heartbeat observer callback with invalid return value.
   */
  public function testHeartbeatObserverInvalidInstanceOf() : void {
    $connection = new Configuration('client', 'tcp://127.0.0.1:1234', heartbeat: [
      'receive' => 1500,
      'observers' => [
        [
          'class' => Configuration::class,
          'callback' => fn() => new \stdClass(),
        ],
      ],
    ]);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageMatches('/The observer must be an instance of/');
    (new StompFactory())->create($connection);
  }

}
