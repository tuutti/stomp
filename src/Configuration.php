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
   *   The heartbeat configuration. Available settings are 'send' and
   *   'receive'. The value should be an integer in milliseconds.
   *
   *   For example ['send' => 3000, 'receive' => 0].
   * @param array $timeout
   *   The timeout. Available settings are 'read' and 'write'.
   *   The value should be an integer in milliseconds.
   *
   *   For example ['read' => 1500, 'write' => 1500].
   */
  public function __construct(
    public readonly string $clientId,
    public readonly string $brokers,
    public readonly string $destination = '/queue/default',
    public readonly ?string $login = NULL,
    public readonly ?string $passcode = NULL,
    public readonly array $heartbeat = [],
    public readonly array $timeout = [
      'write' => 1500,
      'read' => 1500,
    ]) {
    Assert::regex($this->destination, '/^(\/topic\/|\/queue\/)/');
  }

}
