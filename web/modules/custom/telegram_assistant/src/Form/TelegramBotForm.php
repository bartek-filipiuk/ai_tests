<?php

namespace Drupal\telegram_assistant\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\telegram_assistant\Service\TelegramBotManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Form handler for the Telegram Bot add and edit forms.
 */
class TelegramBotForm extends EntityForm {

  /**
   * The Telegram bot manager.
   *
   * @var \Drupal\telegram_assistant\Service\TelegramBotManager
   */
  protected $botManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TelegramBotForm.
   *
   * @param \Drupal\telegram_assistant\Service\TelegramBotManager $bot_manager
   *   The Telegram bot manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    TelegramBotManager $bot_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->botManager = $bot_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('telegram_assistant.bot_manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $telegram_bot = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bot Name'),
      '#maxlength' => 255,
      '#default_value' => $telegram_bot->label(),
      '#description' => $this->t('Name for this bot.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $telegram_bot->id(),
      '#machine_name' => [
        'exists' => '\Drupal\telegram_assistant\Entity\TelegramBot::load',
      ],
      '#disabled' => !$telegram_bot->isNew(),
    ];

    $form['bot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bot Token'),
      '#default_value' => $telegram_bot->getBotToken(),
      '#description' => $this->t('The token received from @BotFather.'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $telegram_bot->isEnabled(),
      '#description' => $this->t('Whether this bot is active.'),
    ];

    // Get the current base URL
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $webhook_url = $base_url . '/telegram/webhook';

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook URL'),
      '#default_value' => $telegram_bot->getWebhookUrl() ?: $webhook_url,
      '#description' => $this->t('The webhook URL for this bot. Current site URL: @url', ['@url' => $webhook_url]),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $telegram_bot = $this->entity;
    $is_new = $telegram_bot->isNew();
    $status = $telegram_bot->save();

    if ($status) {
      // Try to register webhook with Telegram
      $webhook_success = $this->botManager->setWebhook(
        $telegram_bot->getBotToken(),
        $telegram_bot->getWebhookUrl()
      );

      if ($webhook_success) {
        $this->messenger()->addMessage($this->t('Webhook registered successfully for %label bot.', [
          '%label' => $telegram_bot->label(),
        ]));
      }
      else {
        $this->messenger()->addWarning($this->t('Failed to register webhook for %label bot. Please check the logs.', [
          '%label' => $telegram_bot->label(),
        ]));
      }

      $this->messenger()->addMessage($this->t('Saved the %label bot.', [
        '%label' => $telegram_bot->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label bot was not saved.', [
        '%label' => $telegram_bot->label(),
      ]), 'error');
    }

    $form_state->setRedirectUrl(new Url('entity.telegram_bot.collection'));
  }

}
