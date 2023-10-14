<?php

declare(strict_types = 1);

namespace Drupal\stomp\Consumer;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\stomp\Exception\ConsumerException;
use Drupal\stomp\Queue\Queue;
use Psr\Log\LoggerInterface;

/**
 * The main consumer service.
 */
final class Consumer implements ConsumerInterface {

  /**
   * Whether to continue processing.
   *
   * @var bool
   */
  private bool $continueProcessing = TRUE;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $workerManager
   *   The worker manager.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param array $parameters
   *   The parameters.
   */
  public function __construct(
    private readonly QueueWorkerManagerInterface $workerManager,
    private readonly QueueFactory $queueFactory,
    private readonly LoggerInterface $logger,
    private readonly array $parameters,
  ) {
  }

  /**
   * Request processing to be stopped.
   */
  private function stopProcessing() : void {
    $this->continueProcessing = FALSE;
  }

  /**
   * A wrapper to only log messages below the configured log level.
   *
   * @param int $logLevel
   *   The log level.
   * @param string $message
   *   The log message.
   * @param array $arguments
   *   The log message arguments.
   */
  private function log(int $logLevel, string $message, array $arguments = []) : void {
    if ($logLevel > $this->parameters['log_level']) {
      return;
    }
    $this->logger->log($logLevel, (string) new FormattableMarkup($message, $arguments));
  }

  /**
   * {@inheritdoc}
   */
  public function process(string $name, Options $options) : void {
    $queue = $this->queueFactory->get($name);

    if (!$queue instanceof Queue) {
      throw new ConsumerException(
        sprintf('The given queue is not configured to use STOMP: %s', $name)
      );
    }
    $worker = $this->createQueueWorker($name);

    $count = 0;
    while ($this->continueProcessing) {
      try {
        $item = $queue->claimItem($options->lease);

        if (!$item) {
          time_nanosleep(0, $this->parameters['read_interval']);

          continue;
        }
        $worker->processItem($item->data);
        $queue->deleteItem($item);

        $this->log(RfcLogLevel::INFO, 'Processed item @id from @name queue.', [
          '@name' => $name,
          '@id' => $item->item_id,
        ]);
        $count++;
      }
      catch (RequeueException) {
        // The worker requested the task to be immediately re-queued.
        $queue->releaseItem($item);

        $this->log(RfcLogLevel::DEBUG, 'Item @id put back on @name queue.', [
          '@name' => $name,
          '@id' => $item->item_id,
        ]);
      }
      // @todo Support delay.
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and stop processing the queue.
        $queue->releaseItem($item);

        $this->log(RfcLogLevel::ERROR, $e->getMessage());
        $this->stopProcessing();
      }

      if ($options->itemLimit && $options->itemLimit >= $count) {
        $this->stopProcessing();
      }
    }
    $this->log(RfcLogLevel::INFO, 'Processed @count items from the @name queue.', [
      '@count' => $count,
      '@name' => $name,
    ]);
  }

  /**
   * Initialized the queue worker.
   *
   * @param string $name
   *   The queue plugin name.
   *
   * @return \Drupal\Core\Queue\QueueWorkerInterface
   *   The queue worker.
   *
   * @throws \Drupal\stomp\Exception\ConsumerException
   */
  private function createQueueWorker(string $name) : QueueWorkerInterface {
    try {
      return $this->workerManager->createInstance($name);
    }
    catch (PluginException) {
    }
    throw new ConsumerException(
      sprintf('Failed to initialize queue worker: %s', $name)
    );
  }

}
