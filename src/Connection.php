<?php

declare(strict_types = 1);

namespace Drupal\stomp;

use Drupal\Core\Logger\RfcLogLevel;

final class Connection {

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
