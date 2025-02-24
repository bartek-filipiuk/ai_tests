<?php

namespace Drupal\telegram_assistant\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\telegram_assistant\Service\TelegramBotManager;
use Drupal\openaai_assistant_api\Service\OpenAIAssistantService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\telegram_assistant\Service\ThreadManager;

/**
 * Processes Telegram messages through OpenAI.
 *
 * @QueueWorker(
 *   id = "telegram_message_processor",
 *   title = @Translation("Telegram Message Processor"),
 *   cron = {"time" = 60}
 * )
 */
class TelegramMessageProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Telegram bot manager.
   *
   * @var \Drupal\telegram_assistant\Service\TelegramBotManager
   */
  protected $botManager;

  /**
   * The OpenAI Assistant service.
   *
   * @var \Drupal\openaai_assistant_api\Service\OpenAIAssistantService
   */
  protected $assistantService;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The thread manager service.
   *
   * @var \Drupal\telegram_assistant\Service\ThreadManager
   */
  protected $threadManager;

  /**
   * Constructs a new TelegramMessageProcessor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\telegram_assistant\Service\TelegramBotManager $bot_manager
   *   The Telegram bot manager.
   * @param \Drupal\openaai_assistant_api\Service\OpenAIAssistantService $assistant_service
   *   The OpenAI Assistant service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\telegram_assistant\Service\ThreadManager $thread_manager
   *   The thread manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TelegramBotManager $bot_manager,
    OpenAIAssistantService $assistant_service,
    LoggerChannelFactoryInterface $logger_factory,
    ThreadManager $thread_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->botManager = $bot_manager;
    $this->assistantService = $assistant_service;
    $this->loggerFactory = $logger_factory;
    $this->threadManager = $thread_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('telegram_assistant.bot_manager'),
      $container->get('openaai_assistant_api.assistant_service'),
      $container->get('logger.factory'),
      $container->get('telegram_assistant.thread_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $logger = $this->loggerFactory->get('telegram_assistant');
    $logger->debug('Processing queue item: @data', [
      '@data' => print_r($data, TRUE),
    ]);

    // Get bot entity
    $bot = $this->botManager->getBotById($data['bot_id']);
    if (!$bot) {
      $logger->error('Bot @id not found', [
        '@id' => $data['bot_id'],
      ]);
      return;
    }

    try {
      // Get or create thread ID for this chat
      $thread_id = $this->threadManager->getThreadId($data['chat_id']);
      $logger->debug('Found thread ID: @thread_id for chat ID: @chat_id', [
        '@thread_id' => $thread_id,
        '@chat_id' => $data['chat_id'],
      ]);

      // Process message with OpenAI
      $response = $this->assistantService->processMessage(
        $data['message'],
        $thread_id
      );

      $logger->debug('OpenAI response: @response', [
        '@response' => print_r($response, TRUE),
      ]);

      // If we got a response and there was no thread ID, save the mapping
      if ($response && !$thread_id) {
        $thread_id = $this->assistantService->getLastThreadId();
        if ($thread_id) {
          $this->threadManager->createThreadMapping($data['chat_id'], $thread_id);
          $logger->debug('Created new thread mapping: @chat_id -> @thread_id', [
            '@chat_id' => $data['chat_id'],
            '@thread_id' => $thread_id,
          ]);
        }
      }

      // Send response back to Telegram
      if ($response) {
        $this->botManager->sendMessage(
          $bot->getBotToken(),
          $data['chat_id'],
          $response
        );
        $logger->info('Response sent to Telegram');
      }
    }
    catch (\Exception $e) {
      $logger->error('Error processing message: @message', [
        '@message' => $e->getMessage(),
      ]);
      // Send error message to user
      $this->botManager->sendMessage(
        $bot->getBotToken(),
        $data['chat_id'],
        'Sorry, I encountered an error while processing your message.'
      );
    }
  }

}
