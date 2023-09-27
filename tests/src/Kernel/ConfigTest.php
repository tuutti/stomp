<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests default configuration.
 */
class ConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'stomp',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setSetting('stomp', [
      'default' => [
        'user' => 'user1',
        'pass' => 'pass1',
      ],
    ]);
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Tests the default configuration.
   */
  public function testConfig() : void {
  }

}
