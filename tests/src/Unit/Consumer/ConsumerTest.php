<?php

declare(strict_types=1);

namespace Drupal\Tests\stomp\Unit\Consumer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\stomp\Consumer\Consumer;
use Drupal\stomp\Consumer\Options;
use Drupal\stomp\Exception\ConsumerException;
use Drupal\stomp\Queue\Queue;
use Drupal\Tests\stomp\Traits\QueueTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Stomp\Client;
use Stomp\Transport\Message;

/**
 * Unit test for queue.
 *
 * @group stomp
 */
class ConsumerTest extends UnitTestCase {

  use QueueTrait;

  /**
   * Constructs a new Consumer instance.
   *
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $workerManager
   *   The queue worker manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   * @param int $logLevel
   *   The log level.
   *
   * @return \Drupal\stomp\Consumer\Consumer
   *   The consumer.
   */
  private function getSut(
    QueueWorkerManagerInterface $workerManager,
    QueueFactory $queueFactory,
    LoggerInterface $logger = NULL,
    int $logLevel = 6,
  ) : Consumer {
    return new Consumer(
      $workerManager,
      $queueFactory,
      $logger ?: $this->prophesize(LoggerInterface::class)->reveal(),
      [
        'read_interval' => 1000000,
        'log_level' => $logLevel,
      ]
    );
  }

  /**
   * Make sure we only process STOMP queues.
   */
  public function testProcessInvalidQueue() : void {
    $this->expectException(ConsumerException::class);
    $this->expectExceptionMessage('The given queue is not configured to use STOMP: test');
    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $queue = $this->prophesize(QueueWorkerInterface::class);
    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue->reveal());

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal());
    $sut->process('test', new Options());
  }

  /**
   * Tests with invalid queue worker.
   */
  public function testInvalidQueueWorkerException() : void {
    $this->expectException(ConsumerException::class);
    $this->expectExceptionMessage('Failed to initialize queue worker: test');
    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $workerManager->createInstance('test')
      ->willThrow(new PluginNotFoundException(''));

    $queue = $this->getQueue($this->prophesize(Client::class)->reveal(), $this->getDurableSubscription()->reveal());
    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue);

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal());
    $sut->process('test', new Options());
  }

  /**
   * Tests process() with a log level set to warning.
   */
  public function testLogLevelWarning() : void {
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(RfcLogLevel::INFO, 'Processed item 1 from test queue.')->shouldNotBeCalled();
    $logger->log(RfcLogLevel::INFO, 'Processed 1 items from the test queue.')->shouldNotBeCalled();
    $this->assertProcess($logger->reveal(), 4);
  }

  /**
   * Tests process() with a log level set to info.
   */
  public function testLogLevelAll() : void {
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(RfcLogLevel::INFO, 'Processed item 1 from test queue.')->shouldBeCalled();
    $logger->log(RfcLogLevel::INFO, 'Processed 1 items from the test queue.')->shouldBeCalled();
    $this->assertProcess($logger->reveal(), 6);
    // Make sure setting log level to 0 logs all messages.
    $this->assertProcess($logger->reveal(), 0);
  }

  /**
   * Tests process().
   */
  public function assertProcess(LoggerInterface $logger, int $logLevel) : void {
    $client = $this->prophesize(Client::class);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()->shouldBeCalled();
    // Simulate five unsuccessful read attempts and make sure we sleep
    // between each read cycle.
    $subscription->read()
      ->shouldBeCalled()
      ->willReturn(
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      new Message('123', ['message-id' => 1]),
    );
    $subscription->ack(Argument::any())->shouldBeCalled();

    $queue = $this->getQueue($client->reveal(), $subscription->reveal());

    $worker = $this->prophesize(QueueWorkerInterface::class);
    $worker->processItem('123')
      ->shouldBeCalled();

    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $workerManager->createInstance('test')
      ->willReturn($worker->reveal());
    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue);

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal(), $logger, $logLevel);
    $start = floor(microtime(TRUE) * 1000);

    // Limit to one item, so we have an exit condition.
    $sut->process('test', new Options(itemLimit: 1));
    $end = floor(microtime(TRUE) * 1000);

    // Make sure process() slept for at least 500 milliseconds.
    $this->assertTrue(($end - $start) >= 5);
  }

  /**
   * Make sure item can be re-queued.
   */
  public function testRequeueException() : void {
    $item = (object) ['item_id' => 1, 'data' => []];
    $queue = $this->prophesize(Queue::class);
    $queue->claimItem(3600)
      ->willReturn($item);
    $queue->deleteItem($item)
      ->shouldNotBeCalled();
    $queue->releaseItem($item)
      ->shouldBeCalled();

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue->reveal());

    $worker = $this->prophesize(QueueWorkerInterface::class);
    $worker->processItem($item->data)
      ->shouldBeCalled()
      ->willThrow(new RequeueException('message'));

    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $workerManager->createInstance('test')
      ->willReturn($worker->reveal());

    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(RfcLogLevel::DEBUG, 'Item 1 put back on test queue.');
    $logger->log(RfcLogLevel::INFO, 'Processed 0 items from the test queue.')
      ->shouldBeCalled();

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal(), $logger->reveal());
    $sut->process('test', new Options(itemLimit: 1));
  }

  /**
   * Tests suspend queue exception.
   */
  public function testSuspendQueueException() : void {
    $item = (object) ['item_id' => 1, 'data' => []];
    $queue = $this->prophesize(Queue::class);
    $queue->claimItem(3600)
      ->willReturn($item);
    $queue->deleteItem($item)
      ->shouldNotBeCalled();
    $queue->releaseItem($item)
      ->shouldBeCalled();

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue->reveal());

    $worker = $this->prophesize(QueueWorkerInterface::class);
    $worker->processItem($item->data)
      ->shouldBeCalled()
      ->willThrow(new SuspendQueueException('message'));

    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $workerManager->createInstance('test')
      ->willReturn($worker->reveal());

    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(RfcLogLevel::ERROR, 'message')
      ->shouldBeCalled();
    $logger->log(RfcLogLevel::INFO, 'Processed 0 items from the test queue.')
      ->shouldBeCalled();

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal(), $logger->reveal());
    $sut->process('test', new Options(itemLimit: 1));
  }

  /**
   * Make sure items are not released if a generic exception is thrown.
   */
  public function testQueueException() : void {
    $item = (object) ['item_id' => 1, 'data' => []];
    $queue = $this->prophesize(Queue::class);
    $queue->claimItem(3600)
      ->willReturn($item);
    $queue->deleteItem($item)
      ->shouldNotBeCalled();
    $queue->releaseItem($item)
      ->shouldNotBeCalled();

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test')
      ->willReturn($queue->reveal());

    $worker = $this->prophesize(QueueWorkerInterface::class);
    $worker->processItem($item->data)
      ->shouldBeCalled()
      ->willThrow(new \Exception('message'));

    $workerManager = $this->prophesize(QueueWorkerManagerInterface::class);
    $workerManager->createInstance('test')
      ->willReturn($worker->reveal());

    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(Argument::any(), Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    $sut = $this->getSut($workerManager->reveal(), $queueFactory->reveal(), $logger->reveal());

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('message');
    $sut->process('test', new Options(itemLimit: 1));
  }

}
