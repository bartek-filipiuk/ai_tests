<?php

namespace Drupal\telegram_assistant\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Telegram Bot entity.
 *
 * @ConfigEntityType(
 *   id = "telegram_bot",
 *   label = @Translation("Telegram Bot"),
 *   handlers = {
 *     "list_builder" = "Drupal\telegram_assistant\TelegramBotListBuilder",
 *     "form" = {
 *       "add" = "Drupal\telegram_assistant\Form\TelegramBotForm",
 *       "edit" = "Drupal\telegram_assistant\Form\TelegramBotForm",
 *       "delete" = "Drupal\telegram_assistant\Form\TelegramBotDeleteForm"
 *     }
 *   },
 *   config_prefix = "telegram_bot",
 *   admin_permission = "administer telegram_assistant",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "bot_token",
 *     "status",
 *     "webhook_url",
 *     "created",
 *     "changed"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/telegram-assistant/bot/add",
 *     "edit-form" = "/admin/config/telegram-assistant/bot/{telegram_bot}",
 *     "delete-form" = "/admin/config/telegram-assistant/bot/{telegram_bot}/delete",
 *     "collection" = "/admin/config/telegram-assistant/bots"
 *   }
 * )
 */
class TelegramBot extends ConfigEntityBase implements TelegramBotInterface {

  /**
   * The Telegram Bot ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Telegram Bot label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Telegram Bot token.
   *
   * @var string
   */
  protected $bot_token;

  /**
   * The Telegram Bot webhook URL.
   *
   * @var string
   */
  protected $webhook_url;

  /**
   * The Telegram Bot status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The Telegram Bot creation timestamp.
   *
   * @var int
   */
  protected $created;

  /**
   * The Telegram Bot update timestamp.
   *
   * @var int
   */
  protected $changed;

  /**
   * {@inheritdoc}
   */
  public function getBotToken() {
    return $this->bot_token;
  }

  /**
   * {@inheritdoc}
   */
  public function setBotToken($token) {
    $this->bot_token = $token;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookUrl() {
    return $this->webhook_url;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebhookUrl($url) {
    $this->webhook_url = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->created;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->isNew()) {
      $this->created = time();
    }
    $this->changed = time();
  }

}
