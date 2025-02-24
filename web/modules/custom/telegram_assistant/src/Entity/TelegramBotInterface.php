<?php

namespace Drupal\telegram_assistant\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Telegram Bot entity.
 */
interface TelegramBotInterface extends ConfigEntityInterface {

  /**
   * Gets the Telegram Bot token.
   *
   * @return string
   *   The bot token.
   */
  public function getBotToken();

  /**
   * Sets the Telegram Bot token.
   *
   * @param string $token
   *   The bot token.
   *
   * @return \Drupal\telegram_assistant\Entity\TelegramBotInterface
   *   The called Telegram Bot entity.
   */
  public function setBotToken($token);

  /**
   * Gets the Telegram Bot webhook URL.
   *
   * @return string
   *   The webhook URL.
   */
  public function getWebhookUrl();

  /**
   * Sets the Telegram Bot webhook URL.
   *
   * @param string $url
   *   The webhook URL.
   *
   * @return \Drupal\telegram_assistant\Entity\TelegramBotInterface
   *   The called Telegram Bot entity.
   */
  public function setWebhookUrl($url);

  /**
   * Returns whether the Telegram Bot is enabled.
   *
   * @return bool
   *   TRUE if the bot is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the Telegram Bot status.
   *
   * @param bool $status
   *   TRUE to enable the bot, FALSE to disable.
   *
   * @return \Drupal\telegram_assistant\Entity\TelegramBotInterface
   *   The called Telegram Bot entity.
   */
  public function setStatus($status);

  /**
   * Gets the Telegram Bot creation timestamp.
   *
   * @return int
   *   Creation timestamp of the bot.
   */
  public function getCreatedTime();

  /**
   * Gets the Telegram Bot changed timestamp.
   *
   * @return int
   *   The timestamp when the bot was last updated.
   */
  public function getChangedTime();

}
