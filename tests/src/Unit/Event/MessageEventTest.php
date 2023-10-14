<?php

declare(strict_types = 1);

namespace Drupal\Tests\stomp\Unit;

use Drupal\stomp\Event\MessageEvent;
use Drupal\Tests\UnitTestCase;
use Stomp\Transport\Bytes;
use Stomp\Transport\Map;
use Stomp\Transport\Message;

/**
 * Tests message event.
 */
class MessageEventTest extends UnitTestCase {

  /**
   * Tests ::create().
   *
   * @param mixed $body
   *   The body.
   * @param class-string<object> $expectedClass
   *   The expected class.
   *
   * @dataProvider createData
   */
  public function testCreate(mixed $body, string $expectedClass) : void {
    $sut = MessageEvent::create($body);
    $this->assertInstanceOf($expectedClass, $sut->message);
  }

  /**
   * Tests an invalid message type.
   */
  public function testException() : void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid message type.');
    $sut = MessageEvent::create(new \stdClass());
  }

  /**
   * Test data for message event.
   *
   * @return array
   *   The data.
   */
  public function createData() : array {
    return [
      ['body', Message::class],
      [['body'], Map::class],
      [new Message('123'), Message::class],
      [new Bytes('123'), Bytes::class],
    ];
  }

}
