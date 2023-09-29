# STOMP

![CI](https://github.com/City-of-Helsinki/drupal-module-stomp/workflows/CI/badge.svg) [![codecov](https://codecov.io/gh/City-of-Helsinki/drupal-module-stomp/graph/badge.svg?token=0B2TNXYU14)](https://codecov.io/gh/City-of-Helsinki/drupal-module-stomp)

This module integrates Drupal with an external STOMP or AMQP message queue, such as ActiveMQ.

## Installation

You must configure STOMP queues as part of the `$settings` global variable. For example:

```php
# settings.php

$settings['stomp']['default'] = [
  'clientId' => 'artemis',
  'brokers' => 'tcp://artemis:61613',
  // Destination defaults to '/queue/default'.
  'destination' => '/queue/default',
];
```

## Configuration

Configure STOMP as the queuing system for the queues you want STOMP to maintain, either as the default queue service, default reliable queue service, or specifically for each queue.

If you want to set STOMP as the default queue manager, then add the following to your settings:

```php
$settings['queue_default'] = 'queue.stomp.default';
```

Alternatively, you can also set for each queue to use STOMP using one of these formats:

```php
$settings['queue_service_{queue_name}'] = 'queue.stomp.default';
$settings['queue_reliable_service_{queue_name}'] = 'queue.stomp.default';
```

## Customization

### Providing login credentials

```php
$settings['stomp']['default']['login'] = 'username';
$settings['stomp']['default']['passcode'] = 'password';
```

### Timeout

```php
$settings['stomp']['default']['timeout'] = [
  'read' => 1500, // Milliseconds.
  'write' => 1500, // Milliseconds.
]
```

### Heartbeat

To disable heartbeat, set `heartbeat` setting to an empty array:
```php
$settings['stomp']['default']['heartbeat'] = [];
```

Please note that the items *MUST* be processed faster than the heartbeat defined here.

To modify heartbeat timeouts:
```php
$settings['stomp']['default']['heartbeat'] = [
  // Signals the server that we're going to send
  // alive signals within an interval of 3000ms.
  'send' => 3000,
];
// We must assure that we'll send data within less
// than 3000ms so our read timeout must be lower
// as well (1500ms).
$settings['stomp']['default']['timeout'] = [
  'read' => 1500,
]
```


## Running tests

Tests can be run just like any other tests. For example:

```bash
vendor/bin/phpunit modules/contrib/stomp/tests
```

### Artemis

The host, username and password should be `artemis`.

You can use the default Docker image provided by Apache with something like this:

```yaml
# docker-compose.yml
services:
  artemis:
    image: apache/activemq-artemis:latest-alpine
    networks:
      - internal
```

Make sure Artemis queues are empty before running tests:

```bash
foreach item (first second third)
  docker compose exec artemis bin/artemis queue purge \
    --user artemis \
    --password artemis \
    --name test./queue/$item;
end
```


