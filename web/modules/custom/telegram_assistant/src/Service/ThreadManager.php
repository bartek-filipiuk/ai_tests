<?php

namespace Drupal\telegram_assistant\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing Telegram chat to OpenAI thread mappings.
 */
class ThreadManager {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new ThreadManager.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Gets the OpenAI thread ID for a Telegram chat ID.
   *
   * @param string $chat_id
   *   The Telegram chat ID.
   *
   * @return string|null
   *   The OpenAI thread ID, or NULL if not found.
   */
  public function getThreadId($chat_id) {
    $result = $this->database->select('telegram_assistant_threads', 't')
      ->fields('t', ['thread_id'])
      ->condition('chat_id', $chat_id)
      ->execute()
      ->fetchField();

    return $result ?: NULL;
  }

  /**
   * Creates a new thread mapping.
   *
   * @param string $chat_id
   *   The Telegram chat ID.
   * @param string $thread_id
   *   The OpenAI thread ID.
   */
  public function createThreadMapping($chat_id, $thread_id) {
    $this->database->insert('telegram_assistant_threads')
      ->fields([
        'chat_id' => $chat_id,
        'thread_id' => $thread_id,
        'created' => time(),
      ])
      ->execute();

    $this->loggerFactory->get('telegram_assistant')
      ->info('Created new thread mapping: chat_id=@chat_id, thread_id=@thread_id', [
        '@chat_id' => $chat_id,
        '@thread_id' => $thread_id,
      ]);
  }

}
