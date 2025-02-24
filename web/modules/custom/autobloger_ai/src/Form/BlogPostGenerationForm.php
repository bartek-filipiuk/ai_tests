<?php

namespace Drupal\autobloger_ai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\autobloger_ai\Service\AutoblogerAiService;

/**
 * Provides a form for generating blog posts using AI.
 */
class BlogPostGenerationForm extends FormBase {

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
   * The autobloger AI service.
   *
   * @var \Drupal\autobloger_ai\Service\AutoblogerAiService
   */
  protected $autoblogerAiService;

  /**
   * Constructs a new BlogPostGenerationForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ai\AiProviderPluginManager $ai_provider
   *   The AI provider plugin manager.
   * @param \Drupal\autobloger_ai\Service\AutoblogerAiService $autobloger_ai_service
   *   The autobloger AI service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AiProviderPluginManager $ai_provider,
    AutoblogerAiService $autobloger_ai_service
  ) {
    $this->configFactory = $config_factory;
    $this->aiProvider = $ai_provider;
    $this->autoblogerAiService = $autobloger_ai_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ai.provider'),
      $container->get('autobloger_ai.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autobloger_ai_blog_post_generation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('autobloger_ai.settings');

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blog Post Instructions'),
      '#description' => $this->t('Enter instructions for the AI about what kind of blog post to write.'),
      '#required' => TRUE,
      '#rows' => 5,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Blog Post'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $config = $this->configFactory->get('autobloger_ai.settings');
      $prompt = $form_state->getValue('prompt');

      // Get the selected provider and model for text generation.
      $provider_id = $config->get('text_ai_provider');
      $model = $config->get('text_ai_model');

      if (empty($provider_id) || empty($model)) {
        $this->messenger()->addError($this->t('No text generation provider or model configured. Please configure the module first.'));
        return;
      }

      // Initialize the AI provider
      try {
        $text_provider = $this->aiProvider->createInstance($provider_id);
      } catch (\Exception $e) {
        $this->messenger()->addError($this->t('Failed to initialize AI provider: @error', ['@error' => $e->getMessage()]));
        return;
      }

      // Initialize perplexity provider if needed
      $perplexity_provider = null;
      if ($config->get('use_perplexity_search')) {
        try {
          $perplexity_provider = $this->aiProvider->createInstance('perplexity');
        } catch (\Exception $e) {
          $this->messenger()->addError($this->t('Failed to initialize Perplexity provider: @error', ['@error' => $e->getMessage()]));
          return;
        }
      }

      // Set any additional configuration
      $provider_config = $config->get('text_ai_configuration');
      if (!empty($provider_config)) {
        try {
          if (is_string($provider_config)) {
            $provider_config = json_decode($provider_config, TRUE);
          }
          if (is_array($provider_config)) {
            $text_provider->setConfiguration($provider_config);
          }
        } catch (\Exception $e) {
          $this->messenger()->addError($this->t('Invalid provider configuration: @error', ['@error' => $e->getMessage()]));
          return;
        }
      }

      // Generate blog post using the service
      $generated_content = $this->autoblogerAiService->generateBlogPost($prompt, $text_provider, $perplexity_provider);

      if (!empty($generated_content['title']) && !empty($generated_content['content'])) {
        // Create the blog post node with the generated content and image
        $this->autoblogerAiService->createBlogPost(
          $generated_content['title'],
          $generated_content['content'],
          $generated_content['image_data'],
          $generated_content['links'] ?? []
        );

        $this->messenger()->addStatus($this->t('Blog post has been generated and created successfully.'));
      } else {
        throw new \Exception('Failed to generate blog post content.');
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error generating blog post: @error', ['@error' => $e->getMessage()]));
      return;
    }
  }

}
