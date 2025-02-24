<?php

namespace Drupal\autobloger_ai\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\autobloger_ai\Service\AutoblogerAiService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ai\AiProviderPluginManager;

/**
 * Process blog post generation queue.
 *
 * @QueueWorker(
 *   id = "autobloger_ai_post_generation",
 *   title = @Translation("Blog Post Generation Queue Worker"),
 *   cron = {"time" = 120}
 * )
 */
class BlogPostGenerationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The autobloger AI service.
   *
   * @var \Drupal\autobloger_ai\Service\AutoblogerAiService
   */
  protected $autoblogerAiService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * Constructs a new BlogPostGenerationQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\autobloger_ai\Service\AutoblogerAiService $autobloger_ai_service
   *   The autobloger AI service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ai\AiProviderPluginManager $ai_provider
   *   The AI provider plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AutoblogerAiService $autobloger_ai_service,
    ConfigFactoryInterface $config_factory,
    AiProviderPluginManager $ai_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->autoblogerAiService = $autobloger_ai_service;
    $this->configFactory = $config_factory;
    $this->aiProvider = $ai_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('autobloger_ai.service'),
      $container->get('config.factory'),
      $container->get('ai.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Get configuration
    $config = $this->configFactory->get('autobloger_ai.settings');
    $provider_id = $config->get('text_ai_provider');
    $model = $config->get('text_ai_model');

    if (empty($provider_id) || empty($model)) {
      throw new \Exception('No text generation provider or model configured.');
    }

    // Initialize the AI provider
    $text_provider = $this->aiProvider->createInstance($provider_id);
    if (!$text_provider) {
      throw new \Exception('Failed to initialize AI provider.');
    }

    // Initialize perplexity provider if needed
    $perplexity_provider = null;
    if ($config->get('use_perplexity_search')) {
      try {
        $perplexity_provider = $this->aiProvider->createInstance('perplexity');
      } catch (\Exception $e) {
        throw new \Exception('Failed to initialize Perplexity provider: ' . $e->getMessage());
      }
    }

    // Set any additional configuration
    $provider_config = $config->get('text_ai_configuration');
    if (!empty($provider_config)) {
      if (is_string($provider_config)) {
        $provider_config = json_decode($provider_config, TRUE);
      }
      if (is_array($provider_config)) {
        $text_provider->setConfiguration($provider_config);
      }
    }

    // Generate the blog post using the service
    $generated_content = $this->autoblogerAiService->generateBlogPost($data['prompt'], $text_provider, $perplexity_provider);

    // Create the blog post node with the generated content
    if (!empty($generated_content['title']) && !empty($generated_content['content'])) {
      $this->autoblogerAiService->createBlogPost(
        $generated_content['title'],
        $generated_content['content'],
        $generated_content['image_data'],
        $generated_content['links'] ?? []
      );
    } else {
      throw new \Exception('Failed to generate blog post content.');
    }
  }

}
