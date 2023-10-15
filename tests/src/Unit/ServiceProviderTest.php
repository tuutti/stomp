<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Site\Settings;
use Drupal\stomp\StompServiceProvider;
use Drupal\Tests\UnitTestCase;

/**
 * Tests service provider.
 *
 * @group stomp
 */
class ServiceProviderTest extends UnitTestCase {

  /**
   * Asserts expected services.
   *
   * @param bool $expected
   *   Whether service should be enabled or not.
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container to test.
   */
  private function assertServices(bool $expected, ContainerBuilder $container) : void {
    $services = [
      'queue.stomp.first',
      'queue.stomp.second',
    ];

    foreach ($services as $service) {
      $this->assertEquals($expected, $container->has($service));
    }
  }

  /**
   * Tests dynamic service creation with empty settings.
   */
  public function testEmptySettingsRegister() : void {
    new Settings([]);
    $container = new ContainerBuilder();
    $sut = new StompServiceProvider();
    $sut->register($container);
    $this->assertServices(FALSE, $container);
  }

  /**
   * Make sure stomp configuration provides a string key.
   */
  public function testInvalidSettingKey() : void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/Expected a string./');
    new Settings([
      'stomp' => [0 => []],
    ]);
    $container = new ContainerBuilder();
    $sut = new StompServiceProvider();
    $sut->register($container);
  }

  /**
   * Tests dynamic service registration with proper settings.
   */
  public function testRegister() : void {
    new Settings([
      'stomp' => [
        'first' => [
          'clientId' => 'client1',
          'brokers' => 'tcp://127.0.0.1:12345',
        ],
        'second' => [
          'clientId' => 'client2',
          'brokers' => 'tcp://127.0.0.1:321',
        ],
      ],
    ]);
    $container = new ContainerBuilder();

    $sut = new StompServiceProvider();
    $sut->register($container);
    $this->assertServices(TRUE, $container);
  }

}
