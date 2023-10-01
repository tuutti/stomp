<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\stomp\Configuration;
use Drupal\Tests\UnitTestCase;
use Stomp\Network\Observer\HeartbeatEmitter;

/**
 * Tests Connection data object.
 *
 * @group stomp
 */
class ConfigurationTest extends UnitTestCase {

  /**
   * Tests DTO props.
   *
   * @param \Drupal\stomp\Configuration $connection
   *   The connection object.
   */
  private function assertProps(Configuration $connection) : void {
    $props = get_object_vars($connection);

    foreach ($props as $prop => $value) {
      $this->assertEquals($value, $connection->{$prop});
    }
  }

  /**
   * Tests that connection DTO can be constructed.
   */
  public function testProperties() : void {
    $this->assertProps(new Configuration('client', 'tcp://127.0.0.1:61023'));
    $this->assertProps(new Configuration(
      'client',
      'tcp://127.0.0.1:1234',
      '/topic/test',
      login: 'user1',
      passcode: 'pass1',
    ));
    $this->assertProps(new Configuration(
      'client',
      'tcp://127.0.0.1:1234',
      '/queue/test',
      login: 'user1',
      passcode: 'pass1',
      heartbeat: [
        'send' => 1,
        'receive' => 1,
        'observers' => [
          [
            'class' => HeartbeatEmitter::class,
          ],
        ],
      ],
      timeout: ['write' => 1, 'read' => 1],
    ));
  }

  /**
   * Tests heartbeat configuration validation.
   *
   * @dataProvider heartbeatExceptionData
   */
  public function testHeartbeatException(array $heartbeat, string $exceptionMessage) : void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches($exceptionMessage);
    new Configuration(
      'client',
      'tcp://127.0.0.1:1234',
      '/queue/test',
      login: 'user1',
      passcode: 'pass1',
      heartbeat: $heartbeat,
      timeout: ['write' => 1, 'read' => 1],
    );
  }

  /**
   * A data provider.
   *
   * @return array[]
   *   The data.
   */
  public function heartbeatExceptionData() : array {
    return [
      [
        [
          'send' => 3000,
        ],
        '/Missing required "observers" heartbeat setting./',
      ],
      [
        [
          'observers' => 1,
        ],
        '/Expected an array. Got:/',
      ],
      [
        [
          'observers' => [
            [
              'dsa' => '',
            ],
          ],
        ],
        '/Expected the key "class" to exist./',
      ],
      [
        [
          'observers' => [
            [
              'class' => 'InvalidClass',
            ],
          ],
        ],
        '/Expected an existing class name. Got:/',
      ],
      [
        [
          'observers' => [
            [
              'class' => HeartbeatEmitter::class,
              'callback' => 'string',
            ],
          ],
        ],
        '/Expected a callable. Got:/',
      ],
    ];
  }

  /**
   * Tests destination validation.
   */
  public function testInvalidDestination() : void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/does not match the expected pattern./');
    new Configuration('client', 'tcp://127.0.0.1', destination: 'invalidType');
  }

}
