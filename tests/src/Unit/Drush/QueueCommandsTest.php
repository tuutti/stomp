<?php

declare(strict_types=1);

namespace Drupal\Tests\stomp\Unit\Drush;

use Drupal\Component\DependencyInjection\Container;
use Drupal\stomp\Consumer\ConsumerInterface;
use Drupal\stomp\Consumer\Options;
use Drupal\stomp\Drush\Commands\QueueCommands;
use Drupal\stomp\Exception\ConsumerException;
use Drupal\Tests\UnitTestCase;
use Drush\Commands\DrushCommands;
use Drush\TestTraits\DrushTestTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Unit test for queue commands.
 *
 * @group stomp
 */
class QueueCommandsTest extends UnitTestCase {

  use ProphecyTrait;
  use DrushTestTrait;

  /**
   * Constructs a new QueueCommands instance.
   *
   * @param \Drupal\stomp\Consumer\ConsumerInterface $consumer
   *   The consumer.
   *
   * @return \Drupal\stomp\Drush\Commands\QueueCommands
   *   The SUT.
   */
  public function getSut(ConsumerInterface $consumer) : QueueCommands {
    $container = new Container();
    $container->set('stomp.consumer', $consumer);
    return QueueCommands::create($container);
  }

  /**
   * Make sure the execution doesn't die to exception.
   */
  public function testWorkerException() : void {
    $consumer = $this->prophesize(ConsumerInterface::class);
    $consumer->process('test', new Options())
      ->willThrow(new ConsumerException('message'));
    $io = $this->prophesize(SymfonyStyle::class);
    $io->error('message')->shouldBeCalled();

    $sut = new QueueCommands($consumer->reveal(), $io->reveal());
    $this->assertEquals(DrushCommands::EXIT_FAILURE, $sut->worker('test'));
  }

  /**
   * Tests worker command.
   */
  public function testWorker() : void {
    $consumer = $this->prophesize(ConsumerInterface::class);
    $consumer->process('test', new Options(3600, 10));
    $sut = $this->getSut($consumer->reveal());

    // Make sure option values are converted to the correct type.
    $this->assertEquals(DrushCommands::EXIT_SUCCESS, $sut->worker('test', [
      'lease-time' => '3600',
      'items-limit' => '10',
    ]));
  }

}
