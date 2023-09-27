# STOMP

![CI](https://github.com/City-of-Helsinki/drupal-module-stomp/workflows/CI/badge.svg)

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
$settings['queue_service_{queue_name}'] = 'queue.rabbitmq.default';
$settings['queue_reliable_service_{queue_name}'] = 'queue.rabbitmq.default';
```

## Customization

### Providing login credentials

```php
$settings['stomp']['default']['user'] = 'username';
$settings['stomp']['default']['pass'] = 'password';
```

### Heartbeat

To disable heartbeat, set `heartbeat` setting to an empty array:
```php
$settings['stomp']['default']['heartbeat'] = [];
```

To modify heartbeat timeouts:
```php
$settings['stomp']['default']['heartbeat'] = [
  // Signals the server that we're going to send alive signals within an interval of 500ms.
  'send' => 500,
  // We must assure that we'll send data within less than 500ms so our read timeout must be lower as well (250000  = 250ms).
  'readTimeout' => ['microseconds' => 250000],
];
```


