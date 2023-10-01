<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Webmozart\Assert\Assert;

/**
 * The configuration data object.
 */
final class Configuration {

  /**
   * Constructs a new instance.
   *
   * @param string $clientId
   *   The client id.
   * @param string $brokers
   *   The brokers.
   * @param string $destination
   *   The queue destination.
   * @param string|null $login
   *   The username.
   * @param string|null $passcode
   *   The password.
   * @param array $heartbeat
   *   The heartbeat configuration.
   * @param array $timeout
   *   The timeout configuration.
   */
  public function __construct(
    public readonly string $clientId,
    public readonly string $brokers,
    public readonly string $destination = '/queue/default',
    public readonly ?string $login = NULL,
    public readonly ?string $passcode = NULL,
    public readonly array $heartbeat = [],
    public readonly array $timeout = [
      'write' => 0,
      'read' => 1500,
    ]) {
    Assert::regex($this->destination, '/^(\/topic\/|\/queue\/)/');
    $this->assertHeartbeat();
  }

  /**
   * Asserts the heartbeat configuration.
   */
  private function assertHeartbeat() : void {
    if (!$this->heartbeat) {
      return;
    }

    if (empty($this->heartbeat['observers'])) {
      throw new \InvalidArgumentException('Missing required "observers" heartbeat setting.');
    }
    Assert::isArray($this->heartbeat['observers']);

    foreach ($this->heartbeat['observers'] as $observer) {
      Assert::keyExists($observer, 'class');
      Assert::classExists($observer['class']);

      if (isset($observer['callback'])) {
        Assert::isCallable($observer['callback']);
      }
    }
  }

}
