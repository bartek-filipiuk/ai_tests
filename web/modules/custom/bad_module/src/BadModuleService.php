<?php

declare(strict_types=1);

namespace Drupal\bad_module;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Bad Module Service with proper dependency injection.
 */
class BadModuleService {

  use StringTranslationTrait;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger factory.
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The messenger service.
   */
  protected MessengerInterface $messenger;

  /**
   * The cache service.
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a BadModuleService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
    CacheBackendInterface $cache
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->cache = $cache;
  }

  /**
   * Saves data in configuration.
   *
   * @param string $data
   *   The data to save.
   *
   * @return string
   *   Success message.
   */
  public function saveData(string $data): string {
    $this->configFactory
      ->getEditable('bad_module.settings')
      ->set('saved_data', $data)
      ->save();

    $this->loggerFactory->get('bad_module')->notice('Zapisano dane: @data', ['@data' => $data]);

    $this->messenger->addStatus($this->t('Dane zostały zapisane.'));

    return (string) $this->t('Operacja zakończona sukcesem!');
  }

  /**
   * Gets data from configuration.
   *
   * @return array<string>
   *   Array of saved data.
   */
  public function getData(): array {
    $data = $this->configFactory->get('bad_module.settings')->get('saved_data');

    return $data ? [$data] : [];
  }

  /**
   * Processes input data.
   *
   * @param string $input
   *   The input data to process.
   *
   * @return string
   *   The processed data.
   */
  public function processData(string $input): string {
    $processed = $input . ' - przetworzono';

    $this->cache->set('bad_module_last_processed', $processed, time() + 3600);

    return $processed;
  }

}
