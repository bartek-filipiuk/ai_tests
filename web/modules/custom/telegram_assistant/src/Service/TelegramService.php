<?php

namespace Drupal\telegram_assistant\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for interacting with Telegram Bot API.
 */
class TelegramService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TelegramService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Sends a message to a Telegram chat.
   *
   * @param string $chat_id
   *   The chat ID to send the message to.
   * @param string $text
   *   The text of the message.
   * @param array $options
   *   Additional options for the message (parse_mode, reply_markup, etc.).
   *
   * @return bool
   *   TRUE if the message was sent successfully, FALSE otherwise.
   */
  public function sendMessage($chat_id, $text, array $options = []) {
    $token = $this->configFactory->get('telegram_assistant.settings')->get('telegram_bot_token');
    $telegram_endpoint = "https://api.telegram.org/bot{$token}/sendMessage";

    $data = [
      'chat_id' => $chat_id,
      'text' => $text,
    ] + $options;

    try {
      $response = $this->httpClient->request('POST', $telegram_endpoint, [
        'json' => $data,
      ]);

      $result = json_decode((string) $response->getBody(), TRUE);

      if (!empty($result['ok'])) {
        $this->loggerFactory->get('telegram_assistant')
          ->info('Message sent to chat @chat_id: @text', [
            '@chat_id' => $chat_id,
            '@text' => $text,
          ]);
        return TRUE;
      }
      
      $this->loggerFactory->get('telegram_assistant')
        ->error('Failed to send message: @error', [
          '@error' => $result['description'] ?? 'Unknown error',
        ]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('telegram_assistant')
        ->error('Error sending message: @error', [
          '@error' => $e->getMessage(),
        ]);
      return FALSE;
    }
  }

  /**
   * Sends a message with Markdown formatting.
   *
   * @param string $chat_id
   *   The chat ID to send the message to.
   * @param string $text
   *   The text of the message (with Markdown formatting).
   * @param array $options
   *   Additional options for the message.
   *
   * @return bool
   *   TRUE if the message was sent successfully, FALSE otherwise.
   */
  public function sendMarkdownMessage($chat_id, $text, array $options = []) {
    return $this->sendMessage($chat_id, $text, ['parse_mode' => 'MarkdownV2'] + $options);
  }

  /**
   * Sends a message with HTML formatting.
   *
   * @param string $chat_id
   *   The chat ID to send the message to.
   * @param string $text
   *   The text of the message (with HTML formatting).
   * @param array $options
   *   Additional options for the message.
   *
   * @return bool
   *   TRUE if the message was sent successfully, FALSE otherwise.
   */
  public function sendHtmlMessage($chat_id, $text, array $options = []) {
    return $this->sendMessage($chat_id, $text, ['parse_mode' => 'HTML'] + $options);
  }
}
