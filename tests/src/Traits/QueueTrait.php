<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Traits;

use Drupal\stomp\Queue\Queue;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Client;
use Stomp\States\Meta\Subscription;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Provides generic tools to test STOMP queue.
 */
trait QueueTrait {

  use ProphecyTrait;

  /**
   * Gets the SUT.
   *
   * @param \Stomp\Client $client
   *   The client.
   * @param \Stomp\Broker\ActiveMq\Mode\DurableSubscription $subscription
   *   The subscription.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   *
   * @return \Drupal\stomp\Queue\Queue
   *   The sut.
   */
  protected function getQueue(
    Client $client,
    DurableSubscription $subscription,
    LoggerInterface $logger = NULL,
  ) : Queue {
    return new Queue(
      $client,
      $subscription,
      new EventDispatcher(),
      $logger ?: $this->prophesize(LoggerInterface::class)->reveal(),
    );
  }

  /**
   * Gets the durable subscription prophecy.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The object prophecy.
   */
  protected function getDurableSubscription() : ObjectProphecy {
    $subscription = $this->prophesize(DurableSubscription::class);
    $subscription->getSubscription()->willReturn(
      new Subscription('/queue/test', NULL, 'client', '/queue/test')
    );
    return $subscription;
  }

}
