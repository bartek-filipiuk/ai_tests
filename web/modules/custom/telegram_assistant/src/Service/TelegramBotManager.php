<?php

namespace Drupal\telegram_assistant\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for managing Telegram bots.
 */
class TelegramBotManager {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TelegramBotManager object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets a bot by ID.
   *
   * @param string $bot_id
   *   The bot ID.
   *
   * @return \Drupal\telegram_assistant\Entity\TelegramBotInterface|null
   *   The bot entity, or NULL if not found.
   */
  public function getBotById($bot_id) {
    try {
      return $this->entityTypeManager
        ->getStorage('telegram_bot')
        ->load($bot_id);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('telegram_assistant')
        ->error('Error loading bot @id: @message', [
          '@id' => $bot_id,
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }
  }

  /**
   * Gets all active bots.
   *
   * @return \Drupal\telegram_assistant\Entity\TelegramBotInterface[]
   *   Array of active bot entities.
   */
  public function getActiveBots() {
    try {
      return $this->entityTypeManager
        ->getStorage('telegram_bot')
        ->loadByProperties(['status' => TRUE]);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('telegram_assistant')
        ->error('Error loading active bots: @message', [
          '@message' => $e->getMessage(),
        ]);
      return [];
    }
  }

  /**
   * Sends a message to a Telegram chat.
   *
   * @param string $bot_token
   *   The bot token.
   * @param int $chat_id
   *   The chat ID.
   * @param string $text
   *   The message text.
   *
   * @return bool
   *   TRUE if the message was sent successfully, FALSE otherwise.
   */
  public function sendMessage($bot_token, $chat_id, $text) {
    $logger = $this->loggerFactory->get('telegram_assistant');
    $logger->debug('Sending message to chat @chat_id: @text', [
      '@chat_id' => $chat_id,
      '@text' => $text,
    ]);

    try {
      $response = $this->httpClient->post(
        "https://api.telegram.org/bot{$bot_token}/sendMessage",
        [
          'json' => [
            'chat_id' => $chat_id,
            'text' => $text,
          ],
        ]
      );

      $result = json_decode((string) $response->getBody(), TRUE);
      $logger->debug('Telegram API response: @response', [
        '@response' => print_r($result, TRUE),
      ]);

      return $result['ok'] ?? FALSE;
    }
    catch (\Exception $e) {
      $logger->error('Error sending message: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Sets the webhook URL for a bot.
   *
   * @param string $bot_token
   *   The bot token.
   * @param string $webhook_url
   *   The webhook URL.
   *
   * @return bool
   *   TRUE if the webhook was set successfully, FALSE otherwise.
   */
  public function setWebhook($bot_token, $webhook_url) {
    $logger = $this->loggerFactory->get('telegram_assistant');
    $logger->debug('Setting webhook for bot to: @url', [
      '@url' => $webhook_url,
    ]);

    try {
      $response = $this->httpClient->post(
        "https://api.telegram.org/bot{$bot_token}/setWebhook",
        [
          'json' => [
            'url' => $webhook_url,
          ],
        ]
      );

      $result = json_decode((string) $response->getBody(), TRUE);
      $logger->debug('Telegram API response: @response', [
        '@response' => print_r($result, TRUE),
      ]);

      return $result['ok'] ?? FALSE;
    }
    catch (\Exception $e) {
      $logger->error('Error setting webhook: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
