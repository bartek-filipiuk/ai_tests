<?php

namespace Drupal\autobloger_ai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ai\Service\AiProviderFormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AutoblogerAI settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AI provider form helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $aiProviderFormHelper;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ai\Service\AiProviderFormHelper $ai_provider_form_helper
   *   The AI provider form helper.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    AiProviderFormHelper $ai_provider_form_helper
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->aiProviderFormHelper = $ai_provider_form_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('ai.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autobloger_ai_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autobloger_ai.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autobloger_ai.settings');

    // Add perplexity search checkbox
    $form['use_perplexity_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Perplexity Search'),
      '#description' => $this->t('Enable to use Perplexity model for web search before generating blog post content.'),
      '#default_value' => $config->get('use_perplexity_search') ?? FALSE,
    ];

    // Add perplexity model selection
    $form['perplexity_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Perplexity Model'),
      '#description' => $this->t('Select the Perplexity model to use for web search.'),
      '#options' => [
        'llama-3.1-sonar-small-128k-online' => 'Llama 3.1 Sonar Small (8B)',
        'llama-3.1-sonar-large-128k-online' => 'Llama 3.1 Sonar Large (70B)',
        'llama-3.1-sonar-huge-128k-online' => 'Llama 3.1 Sonar Huge (405B)',
      ],
      '#default_value' => $config->get('perplexity_model') ?? 'llama-3.1-sonar-small-128k-online',
      '#states' => [
        'visible' => [
          ':input[name="use_perplexity_search"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add warning markup about perplexity and GPT models
    $form['perplexity_warning'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' .
        $this->t('Note: For text generation model, we suggest using GPT models or other models that can output JSON format. Perplexity cannot output JSON directly, and other output formats may cause errors in blog post generation.') .
        '</div>',
      '#states' => [
        'visible' => [
          ':input[name="use_perplexity_search"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add text generation model selection using AI Provider Form Helper
    $this->aiProviderFormHelper->generateAiProvidersForm(
      $form,
      $form_state,
      'chat',
      'text_',
      AiProviderFormHelper::FORM_CONFIGURATION_FULL,
      0,
      '',
      $this->t('Text Generation Model'),
      $this->t('Select the AI model to use for generating blog post content.')
    );

    // Add prompts configuration
    $form['prompts'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Prompts Configuration'),
      '#description' => $this->t('Configure prompts for different AI models. Use [User Subject] as a placeholder for the blog topic.'),
      '#open' => TRUE,
    ];

    // Perplexity prompt
    $default_perplexity_prompt = '[Instruction]
Provide a concise and informative summary of the topic: "[User Subject]".

[Context]
I need the most recent and relevant information on this topic to assist in creating a detailed blog post. Focus on key points, statistics, and new developments related to the subject.

[Input]
- Topic: "[User Subject]"

[Keywords]
- Latest trends
- Key insights
- Important data
- Recent news
- Citations from reputable sources

[Output Format]
- Bullet points summarizing the main information
- Include citations with sources for each point';

    $form['prompts']['perplexity_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Perplexity Search Prompt'),
      '#description' => $this->t('Prompt template for Perplexity search. Use [User Subject] as placeholder for the topic.'),
      '#default_value' => $config->get('perplexity_prompt') ?? $default_perplexity_prompt,
      '#rows' => 15,
      '#states' => [
        'visible' => [
          ':input[name="use_perplexity_search"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // OpenAI prompt
    $default_openai_prompt = 'You are a professional writer tasked with creating a high-quality blog post on the topic: "[User Subject]".

[Context]
Utilize the following up-to-date information to enrich the blog post:

[Perplexity Information]
[Insert bullet points and citations from Perplexity here.]

[Instructions]
- Combine the provided information with your existing knowledge
- Write an engaging and informative blog post suitable for a knowledgeable audience
- Include detailed explanations, examples, and insights
- Organize the content with clear headings and subheadings
- Ensure the content reflects the latest developments and is accurate
- Maintain a formal and informative tone
- Conclude with a summary and potential future perspectives

Your response must be a valid JSON array with exactly this structure:
[{
  "title": "The blog post title in the target language",
  "title_english": "The blog post title in English (for image generation)",
  "content": "The main blog post content",
  "links": []
}]

Content requirements:
1. Write 3-4 sections, each with:
   - An engaging <h2> header for each section
   - A detailed paragraph under each header
2. Format content in HTML:
   - Use <h2> tags for section headers
   - Use <p> tags for paragraphs
   - Use <strong> or <em> for emphasis
3. At the end of the content, add a "Sources" section with:
   - <h2>Sources</h2>
   - <ul> list containing all referenced links
4. Add all referenced URLs to the links array in the JSON
5. Make the content engaging and easy to read
6. If the target language is not English, provide both:
   - title: in the target language for display
   - title_english: in English for image generation';

    $form['prompts']['openai_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Generation Prompt'),
      '#description' => $this->t('Prompt template for text generation. Use [User Subject] for topic and [Perplexity Information] for search results.'),
      '#default_value' => $config->get('openai_prompt') ?? $default_openai_prompt,
      '#rows' => 15,
    ];

    // Image generation prompt
    $default_image_prompt = 'Generate an ultra-realistic image that represents the theme: "[User Subject]".

[Instructions]
- Focus on visual elements symbolizing the main concepts without including human figures
- Avoid sci-fi or futuristic aesthetics; aim for a realistic and contemporary look
- Incorporate relevant objects, environments, or symbols associated with the topic
- Ensure the image is high-quality and visually engaging

[Specifications]
- Style: Photorealistic
- Exclude: Sci-fi elements, human figures
- Emphasize: [Insert specific elements related to the subject]';

    $form['prompts']['image_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Image Generation Prompt'),
      '#description' => $this->t('Prompt template for image generation. Use [User Subject] as placeholder for the topic.'),
      '#default_value' => $config->get('image_prompt') ?? $default_image_prompt,
      '#rows' => 12,
    ];

    // Add image generation model selection.
    $form['image_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Generation Model'),
      '#description' => $this->t('Select the model to use for generating images.'),
      '#options' => [
        'black-forest-labs/FLUX.1-schnell-Free' => 'FLUX.1 Schnell (Free)',
        'black-forest-labs/FLUX.1.1-pro' => 'FLUX.1.1 Pro',
        'black-forest-labs/FLUX.1-pro' => 'FLUX.1 Pro',
      ],
      '#default_value' => $config->get('image_model'),
      '#required' => TRUE,
    ];

    $form['together_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Together API Key'),
      '#description' => $this->t('API key for accessing Together AI and FLUX services.'),
      '#default_value' => $config->get('together_api_key'),
      '#required' => TRUE,
    ];

    $form['blog_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Blog Post Language'),
      '#description' => $this->t('Select the language for generated blog posts.'),
      '#options' => [
        'en' => $this->t('English'),
        'pl' => $this->t('Polish'),
      ],
      '#default_value' => $config->get('blog_language') ?? 'en',
      '#required' => TRUE,
    ];

    // Get content types with image and text fields.
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $field_definitions = [];
    $content_type_options = [];

    foreach ($content_types as $content_type) {
      $fields = $this->entityTypeManager
        ->getStorage('field_config')
        ->loadByProperties(['bundle' => $content_type->id(), 'entity_type' => 'node']);

      $has_image = FALSE;
      $has_text = FALSE;
      $field_options = [
        'image_fields' => [],
        'text_fields' => [],
      ];

      foreach ($fields as $field) {
        if ($field->getType() === 'image') {
          $has_image = TRUE;
          $field_options['image_fields'][$field->getName()] = $field->getLabel();
        }
        elseif (in_array($field->getType(), ['text', 'text_long', 'text_with_summary'])) {
          $has_text = TRUE;
          $field_options['text_fields'][$field->getName()] = $field->getLabel();
        }
      }

      if ($has_image && $has_text) {
        $content_type_options[$content_type->id()] = $content_type->label();
        $field_definitions[$content_type->id()] = $field_options;
      }
    }

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the content type to use for blog posts.'),
      '#options' => $content_type_options,
      '#default_value' => $config->get('content_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-options-wrapper',
      ],
    ];

    $selected_type = $form_state->getValue('content_type') ?: $config->get('content_type');
    if ($selected_type && isset($field_definitions[$selected_type])) {
      $form['image_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Image Field'),
        '#description' => $this->t('Select the field to store generated images.'),
        '#options' => $field_definitions[$selected_type]['image_fields'],
        '#default_value' => $config->get('image_field'),
        '#required' => TRUE,
        '#prefix' => '<div id="field-options-wrapper">',
      ];

      $form['text_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Text Field'),
        '#description' => $this->t('Select the field to store generated content.'),
        '#options' => $field_definitions[$selected_type]['text_fields'],
        '#default_value' => $config->get('text_field'),
        '#required' => TRUE,
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['field_options'] = [
        '#type' => 'container',
        '#prefix' => '<div id="field-options-wrapper">',
        '#suffix' => '</div>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to update field options when content type changes.
   */
  public function updateFieldOptions(array &$form, FormStateInterface $form_state) {
    $selected_type = $form_state->getValue('content_type');
    if ($selected_type && isset($form['image_field']) && isset($form['text_field'])) {
      return [
        'image_field' => $form['image_field'],
        'text_field' => $form['text_field'],
      ];
    }
    return $form['field_options'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('autobloger_ai.settings');

    // Save the AI provider configuration
    $llmInstance = $this->aiProviderFormHelper->generateAiProviderFromFormSubmit(
      $form,
      $form_state,
      'chat',
      'text_'
    );

    if ($llmInstance) {
      $provider_id = $form_state->getValue('text_ai_provider');
      $provider_config = $llmInstance->getConfiguration();
      $provider_model = $form_state->getValue('text_ai_model');

      $config
        ->set('text_ai_provider', $provider_id)
        ->set('text_ai_model', $provider_model)
        ->set('text_ai_configuration', $provider_config);
    }

    // Save other settings
    $config
      ->set('image_model', $form_state->getValue('image_model'))
      ->set('together_api_key', $form_state->getValue('together_api_key'))
      ->set('content_type', $form_state->getValue('content_type'))
      ->set('image_field', $form_state->getValue('image_field'))
      ->set('text_field', $form_state->getValue('text_field'))
      ->set('blog_language', $form_state->getValue('blog_language'))
      ->set('use_perplexity_search', $form_state->getValue('use_perplexity_search'))
      ->set('perplexity_model', $form_state->getValue('perplexity_model'))
      ->set('perplexity_prompt', $form_state->getValue('perplexity_prompt'))
      ->set('openai_prompt', $form_state->getValue('openai_prompt'))
      ->set('image_prompt', $form_state->getValue('image_prompt'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
