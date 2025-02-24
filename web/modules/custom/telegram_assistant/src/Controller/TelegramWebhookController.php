<?php

namespace Drupal\telegram_assistant\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\telegram_assistant\Service\TelegramBotManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for handling Telegram webhook requests.
 */
class TelegramWebhookController extends ControllerBase {

  /**
   * The Telegram bot manager.
   *
   * @var \Drupal\telegram_assistant\Service\TelegramBotManager
   */
  protected $botManager;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a TelegramWebhookController object.
   *
   * @param \Drupal\telegram_assistant\Service\TelegramBotManager $bot_manager
   *   The Telegram bot manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    TelegramBotManager $bot_manager,
    QueueFactory $queue_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->botManager = $bot_manager;
    $this->queueFactory = $queue_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('telegram_assistant.bot_manager'),
      $container->get('queue'),
      $container->get('logger.factory')
    );
  }

  /**
   * Handles incoming webhook requests from Telegram.
   */
  public function handle(Request $request) {
    $logger = $this->loggerFactory->get('telegram_assistant');
    
    // Log request headers
    $logger->debug('Webhook request headers: @headers', [
      '@headers' => print_r($request->headers->all(), TRUE),
    ]);
    
    // Log raw request content
    $raw_content = $request->getContent();
    $logger->debug('Raw webhook request content: @content', [
      '@content' => $raw_content,
    ]);

    $data = json_decode($request->getContent(), TRUE);
    
    // Log decoded data
    $logger->debug('Decoded webhook data: @data', [
      '@data' => print_r($data, TRUE),
    ]);

    if (empty($data)) {
      $logger->error('Empty or invalid JSON data received');
      return new JsonResponse(['status' => 'error', 'message' => 'Invalid data']);
    }

    if (!isset($data['message']['chat']['id']) || !isset($data['message']['text'])) {
      $logger->error('Required message fields missing: @data', [
        '@data' => print_r($data, TRUE),
      ]);
      return new JsonResponse(['status' => 'error', 'message' => 'Invalid message format']);
    }

    // Get chat ID and message text
    $chat_id = $data['message']['chat']['id'];
    $message_text = $data['message']['text'];

    $logger->debug('Processing message from chat @chat_id: @text', [
      '@chat_id' => $chat_id,
      '@text' => $message_text,
    ]);

    // Find active bot by token from request URL
    $bots = $this->botManager->getActiveBots();
    $current_bot = NULL;

    foreach ($bots as $bot) {
      // Check if this request is for this bot
      if ($this->validateBotRequest($request, $bot->getBotToken())) {
        $current_bot = $bot;
        break;
      }
    }

    if (!$current_bot) {
      $logger->error('No matching bot found for request');
      return new JsonResponse(['status' => 'error', 'message' => 'Bot not found']);
    }

    $logger->debug('Found matching bot: @bot_id', [
      '@bot_id' => $current_bot->id(),
    ]);

    // Add message to queue
    $queue = $this->queueFactory->get('telegram_message_processor');
    $item = [
      'bot_id' => $current_bot->id(),
      'chat_id' => $chat_id,
      'message' => $message_text,
      'timestamp' => time(),
    ];

    $logger->debug('Adding message to queue: @item', [
      '@item' => print_r($item, TRUE),
    ]);

    try {
      $queue->createItem($item);
      $logger->info('Message successfully added to queue');
    }
    catch (\Exception $e) {
      $logger->error('Error adding message to queue: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse(['status' => 'error', 'message' => 'Queue error']);
    }

    // Send immediate response to Telegram
    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Validates if the request is from a specific bot.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $bot_token
   *   The bot token to validate against.
   *
   * @return bool
   *   TRUE if the request is valid for this bot.
   */
  protected function validateBotRequest(Request $request, $bot_token) {
    $logger = $this->loggerFactory->get('telegram_assistant');
    
    // Log validation attempt
    $logger->debug('Validating request for bot token: @token', [
      '@token' => substr($bot_token, 0, 6) . '...',
    ]);

    // For now, we'll assume all requests are valid
    // In production, you should implement proper validation
    return TRUE;
  }

}
