<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit\Queue;

use Drupal\stomp\Queue\Item;
use Drupal\Tests\stomp\Traits\QueueTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Transport\Map;
use Stomp\Transport\Message;

/**
 * Unit test for queue.
 *
 * @group stomp
 */
class QueueTest extends UnitTestCase {

  use QueueTrait;

  /**
   * Tests delete() with invalid type.
   */
  public function testDeleteItemInvalidType() : void {
    $client = $this->prophesize(Client::class);
    $subscription = $this->getDurableSubscription();
    $sut = $this->getQueue($client->reveal(), $subscription->reveal());
    $sut->deleteItem(NULL);
    $sut->deleteItem((object) ['message' => NULL]);
  }

  /**
   * Make sure delete errors are logged.
   */
  public function testDeleteItemStompException() : void {
    $client = $this->prophesize(Client::class);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()->shouldBeCalled();
    $message = new Message('test', ['message-id' => 1]);
    $subscription->ack($message)->willThrow(new StompException('message'));
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Failed to ACK message from /queue/test: message')
      ->shouldBeCalled();
    $sut = $this->getQueue($client->reveal(), $subscription->reveal(), $logger->reveal());
    $sut->deleteItem($sut->toItem($message));
  }

  /**
   * Make sure write errors are logged.
   */
  public function testCreateItemStompException() : void {
    $client = $this->prophesize(Client::class);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()
      ->shouldBeCalled()
      ->willThrow(new StompException('message'));
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Failed to send item to /queue/test: message', Argument::any())
      ->shouldBeCalled();
    $sut = $this->getQueue($client->reveal(), $subscription->reveal(), $logger->reveal());
    $this->assertFalse($sut->createItem('123'));
  }

  /**
   * Tests successful createItem() call.
   */
  public function testCreateItem() : void {
    $client = $this->prophesize(Client::class);
    $client->send(Argument::any(), Argument::any())->willReturn(TRUE);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()->shouldBeCalled();

    $sut = $this->getQueue($client->reveal(), $subscription->reveal());
    $this->assertEquals(1, $sut->createItem('123'));
  }

  /**
   * Make sure read errors are logged.
   */
  public function testClaimItemStompException() : void {
    $client = $this->prophesize(Client::class);
    $client->send(Argument::any(), Argument::any())->willReturn(TRUE);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()->shouldBeCalled();
    $subscription->read()->willThrow(
      new StompException('message')
    );
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->error('Failed to read item from /queue/test: message')
      ->shouldBeCalled();
    $sut = $this->getQueue($client->reveal(), $subscription->reveal(), $logger->reveal());
    $this->assertFalse($sut->claimItem());
  }

  /**
   * Tests message decoding.
   *
   * @dataProvider messageDecodeData
   */
  public function testMessageDecoding(mixed $expectedMessage, mixed $messageObject) : void {
    $client = $this->prophesize(Client::class);
    $client->send(Argument::any(), Argument::any())->willReturn(TRUE);
    $subscription = $this->getDurableSubscription();
    $subscription->activate()->shouldBeCalled();

    $subscription->read()->willReturn($messageObject);
    $sut = $this->getQueue($client->reveal(), $subscription->reveal());

    $message = $sut->claimItem();
    $this->assertInstanceOf(Item::class, $message);
    $this->assertEquals($expectedMessage, $message->data);
  }

  /**
   * A data provider.
   *
   * @return array[]
   *   The data.
   */
  public function messageDecodeData() : array {
    return [
      [
        ['value' => 1],
        new Map(['value' => 1], ['message-id' => 1]),
      ],
      [
        'string',
        new Message('string', ['message-id' => 1]),
      ],
    ];
  }

}
