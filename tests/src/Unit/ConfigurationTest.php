<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\stomp\Configuration;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Connection data object.
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
      heartbeat: ['send' => 1, 'receive' => 1],
      timeout: ['write' => 1, 'read' => 1],
    ));
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
