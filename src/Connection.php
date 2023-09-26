<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * The connection data object.
 */
final class Connection {

  /**
   * Constructs a new instance.
   *
   * @param string $clientId
   *   The client id.
   * @param string|null $user
   *   The username.
   * @param string|null $pass
   *   The password.
   * @param bool $randomize
   *   The 'randomize' setting.
   * @param array $brokers
   *   The brokers.
   * @param int $logLevel
   *   The minimum log level.
   */
  public function __construct(
    public readonly string $clientId,
    public readonly ?string $user = NULL,
    public readonly ?string $pass = NULL,
    public readonly bool $randomize = FALSE,
    public readonly array $brokers = ['tcp://localhost:61613'],
    public readonly int $logLevel = RfcLogLevel::WARNING,
  ) {
  }

}
