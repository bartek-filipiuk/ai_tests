<?php

namespace Drupal\together_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\together_api\Service\Client;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\File\FileExists;

/**
 * Form for testing Together API endpoints.
 */
class TestEndpointForm extends FormBase {

  /**
   * The Together API client service.
   *
   * @var \Drupal\together_api\Service\Client
   */
  protected $client;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * TestEndpointForm constructor.
   *
   * @param \Drupal\together_api\Service\Client $client
   *   The Together API client service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(
    Client $client,
    FileSystemInterface $file_system,
    FileRepositoryInterface $file_repository
  ) {
    $this->client = $client;
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('together_api.client'),
      $container->get('file_system'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'together_api_test_endpoint_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $models = $this->client->listModels();
      $form['models'] = [
        '#type' => 'details',
        '#title' => $this->t('Available Models'),
        '#open' => TRUE,
        'table' => [
          '#type' => 'table',
          '#header' => ['ID', 'Name', 'Type', 'Context Length'],
          '#rows' => [],
        ],
      ];

      foreach ($models as $model) {
        $form['models']['table']['#rows'][] = [
          $model['id'],
          $model['display_name'] ?? $model['id'],
          $model['type'] ?? 'N/A',
          $model['context_length'] ?? 'N/A',
        ];
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error loading models: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form['test_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Test Type'),
      '#options' => [
        'chat' => $this->t('Chat Completion'),
        'completion' => $this->t('Completion'),
        'image' => $this->t('Image Generation'),
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateForm',
        'wrapper' => 'test-parameters',
      ],
    ];

    $form['parameters'] = [
      '#type' => 'container',
      '#prefix' => '<div id="test-parameters">',
      '#suffix' => '</div>',
    ];

    $test_type = $form_state->getValue('test_type', 'chat');

    if ($test_type !== 'image') {
      $form['parameters']['model'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Model'),
        '#required' => TRUE,
        '#description' => $this->t('Enter the model ID to use.'),
      ];
    }

    switch ($test_type) {
      case 'chat':
        $form['parameters']['messages'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Messages'),
          '#required' => TRUE,
          '#description' => $this->t('Enter messages in JSON format. Example: [{"role": "user", "content": "Hello, how are you?"}]'),
          '#default_value' => '[{"role": "user", "content": "Hello, how are you?"}]',
        ];
        break;

      case 'completion':
        $form['parameters']['prompt'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Prompt'),
          '#required' => TRUE,
          '#description' => $test_type === 'image'
            ? $this->t('Enter the image generation prompt.')
            : $this->t('Enter the prompt text.'),
        ];
        break;

      case 'image':
        $form['parameters']['helper_info'] = [
          '#type' => 'details',
          '#title' => $this->t('Helper Information'),
          '#open' => TRUE,
          'content' => [
            '#theme' => 'item_list',
            '#items' => [
              $this->t('Available Models:'),
              [
                '#theme' => 'item_list',
                '#items' => [
                  'black-forest-labs/FLUX.1-schnell-Free - ' . $this->t('Free version, good for general purpose images'),
                  'black-forest-labs/FLUX.1-schnell - ' . $this->t('Turbo version with faster processing'),
                  'black-forest-labs/FLUX.1-dev - ' . $this->t('Development version with latest features'),
                  'black-forest-labs/FLUX.1-canny - ' . $this->t('Specialized in edge detection and line art'),
                  'black-forest-labs/FLUX.1-depth - ' . $this->t('Enhanced depth perception and 3D understanding'),
                  'black-forest-labs/FLUX.1-redux - ' . $this->t('Optimized version for better performance'),
                  'black-forest-labs/FLUX.1.1-pro - ' . $this->t('PAID, good quality'),
                  'stabilityai/stable-diffusion-xl-base-1.0 - ' . $this->t('High quality, detailed images'),
                ],
              ],
              $this->t('Example Prompts:'),
              [
                '#theme' => 'item_list',
                '#items' => [
                  '"A magical forest at sunset, ethereal light filtering through ancient trees, photorealistic, detailed, 8k"',
                  '"Portrait of a wise wizard with long white beard, wearing ornate robes, magical atmosphere, professional photography"',
                  '"Majestic mountain landscape at dawn, snow-capped peaks, crystal clear lake reflection, ultra HD, cinematic lighting"',
                ],
              ],
              $this->t('Tips for Better Results:'),
              [
                '#theme' => 'item_list',
                '#items' => [
                  $this->t('Use 20-30 steps for optimal quality'),
                  $this->t('Include style descriptors like "photorealistic", "detailed", "8k", "cinematic"'),
                  $this->t('Use negative prompts to avoid unwanted elements'),
                  $this->t('Common aspect ratios: 1:1 (1024x1024), 16:9 (1024x576), 3:4 (768x1024)'),
                ],
              ],
              $this->t('Example Negative Prompts:'),
              [
                '#theme' => 'item_list',
                '#items' => [
                  '"blurry, low quality, distorted, deformed, bad anatomy, ugly, oversaturated"',
                ],
              ],
            ],
          ],
        ];

        $form['parameters']['model'] = [
          '#type' => 'select',
          '#title' => $this->t('Model'),
          '#required' => TRUE,
          '#options' => [
            'black-forest-labs/FLUX.1-schnell-Free' => $this->t('FLUX.1 Schnell (Free)'),
            'black-forest-labs/FLUX.1-schnell' => $this->t('FLUX.1 Schnell Turbo'),
            'black-forest-labs/FLUX.1-dev' => $this->t('FLUX.1 Dev (Latest Features)'),
            'black-forest-labs/FLUX.1-canny' => $this->t('FLUX.1 Canny (Line Art)'),
            'black-forest-labs/FLUX.1-depth' => $this->t('FLUX.1 Depth (3D Understanding)'),
            'black-forest-labs/FLUX.1-redux' => $this->t('FLUX.1 Redux (Optimized)'),
            'black-forest-labs/FLUX.1.1-pro' => $this->t('FLUX.1.1-pro (Paid)'),
            'stabilityai/stable-diffusion-xl-base-1.0' => $this->t('Stable Diffusion XL 1.0'),
          ],
          '#default_value' => 'black-forest-labs/FLUX.1-schnell-Free',
          '#description' => $this->t('Select the model to use for image generation. Some models may have usage costs.'),
        ];

        $form['parameters']['prompt'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Prompt'),
          '#required' => TRUE,
          '#description' => $this->t('Describe the image you want to generate. Include details about style, lighting, and quality.'),
          '#placeholder' => $this->t('Example: A magical forest at sunset, ethereal light filtering through ancient trees, photorealistic, detailed, 8k'),
        ];

        $form['parameters']['negative_prompt'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Negative Prompt'),
          '#description' => $this->t('Describe what you do not want in the image. Common examples: blurry, low quality, distorted, deformed'),
          '#placeholder' => $this->t('Example: blurry, low quality, distorted, deformed, bad anatomy, ugly, oversaturated'),
        ];

        $form['parameters']['advanced'] = [
          '#type' => 'details',
          '#title' => $this->t('Advanced Settings'),
          '#open' => FALSE,
          'steps' => [
            '#type' => 'number',
            '#title' => $this->t('Steps'),
            '#min' => 1,
            '#max' => 50,
            '#default_value' => 20,
            '#description' => $this->t('Number of inference steps. Higher values (20-30) give better quality but take longer.'),
          ],
          'dimensions' => [
            '#type' => 'fieldset',
            '#title' => $this->t('Image Dimensions'),
            'width' => [
              '#type' => 'select',
              '#title' => $this->t('Width'),
              '#options' => [
                '512' => '512px',
                '768' => '768px',
                '1024' => '1024px',
              ],
              '#default_value' => '1024',
              '#description' => $this->t('Image width in pixels.'),
            ],
            'height' => [
              '#type' => 'select',
              '#title' => $this->t('Height'),
              '#options' => [
                '512' => '512px',
                '768' => '768px',
                '1024' => '1024px',
              ],
              '#default_value' => '1024',
              '#description' => $this->t('Image height in pixels.'),
            ],
          ],
          'seed' => [
            '#type' => 'number',
            '#title' => $this->t('Seed'),
            '#description' => $this->t('Random seed for reproducible results. Leave empty for random.'),
            '#min' => 0,
          ],
        ];

        // Add common aspect ratio presets
        $form['parameters']['advanced']['dimensions']['preset'] = [
          '#type' => 'select',
          '#title' => $this->t('Aspect Ratio Preset'),
          '#options' => [
            '' => $this->t('Custom'),
            'square' => $this->t('Square (1:1) - 1024x1024'),
            'landscape' => $this->t('Landscape (16:9) - 1024x576'),
            'portrait' => $this->t('Portrait (3:4) - 768x1024'),
          ],
          '#description' => $this->t('Select a preset aspect ratio or customize dimensions above.'),
          '#attributes' => [
            'onchange' => 'if(this.value==="square"){document.getElementById("edit-parameters-advanced-dimensions-width").value="1024";document.getElementById("edit-parameters-advanced-dimensions-height").value="1024";}else if(this.value==="landscape"){document.getElementById("edit-parameters-advanced-dimensions-width").value="1024";document.getElementById("edit-parameters-advanced-dimensions-height").value="576";}else if(this.value==="portrait"){document.getElementById("edit-parameters-advanced-dimensions-width").value="768";document.getElementById("edit-parameters-advanced-dimensions-height").value="1024";}',
          ],
        ];
        break;
    }

    if ($test_type !== 'image') {
      $form['parameters']['temperature'] = [
        '#type' => 'number',
        '#title' => $this->t('Temperature'),
        '#min' => 0,
        '#max' => 2,
        '#step' => 0.1,
        '#default_value' => 0.7,
        '#description' => $this->t('Controls randomness in the output. Higher values make the output more random, lower values make it more focused and deterministic.'),
      ];

      $form['parameters']['max_tokens'] = [
        '#type' => 'number',
        '#title' => $this->t('Max Tokens'),
        '#min' => 1,
        '#max' => 4096,
        '#default_value' => 1024,
        '#description' => $this->t('The maximum number of tokens to generate.'),
      ];
    }

    $form['parameters']['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional Options'),
      '#description' => $this->t('Enter additional options in JSON format (optional).'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test Endpoint'),
    ];

    if ($form_state->get('api_result')) {
      $result = $form_state->get('api_result');
      $test_type = $form_state->getValue('test_type');

      $form['result'] = [
        '#type' => 'details',
        '#title' => $this->t('API Response'),
        '#open' => TRUE,
      ];

      // Format the response based on the test type
      if ($test_type === 'completion') {
        if (isset($result['choices'][0]['text'])) {
          $form['result']['response'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['api-response']],
            'prompt' => [
              '#type' => 'item',
              '#title' => $this->t('Your prompt:'),
              '#markup' => '<div class="prompt-text">' . nl2br(htmlspecialchars($form_state->getValue('prompt'))) . '</div>',
            ],
            'completion' => [
              '#type' => 'item',
              '#title' => $this->t('Response:'),
              '#markup' => '<div class="completion-text">' . nl2br(htmlspecialchars($result['choices'][0]['text'])) . '</div>',
            ],
          ];
        }
      }
      elseif ($test_type === 'image' && isset($result['saved_image_url'])) {
        $form['result']['image'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['api-response']],
          'prompt' => [
            '#type' => 'item',
            '#title' => $this->t('Your prompt:'),
            '#markup' => '<div class="prompt-text">' . nl2br(htmlspecialchars($form_state->getValue('prompt'))) . '</div>',
          ],
          'generated_image' => [
            '#type' => 'item',
            '#title' => $this->t('Generated Image:'),
            '#markup' => '<div class="generated-image"><img src="' . $result['saved_image_url'] . '" alt="Generated image"></div>',
          ],
        ];
      }

      // Add raw response data in collapsible details
      $form['result']['raw_data'] = [
        '#type' => 'details',
        '#title' => $this->t('Raw Response Data'),
        '#open' => FALSE,
        'content' => [
          '#markup' => '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>',
        ],
      ];

      // Add CSS for styling the response
      $form['#attached']['library'][] = 'together_api/together_api.form';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $test_type = $form_state->getValue('test_type');

    if ($test_type === 'chat') {
      $messages = $form_state->getValue('messages') ?? '';

      if (empty($messages)) {
        \Drupal::logger('together_api')->error('Messages is empty');
        $form_state->setErrorByName('parameters][messages', $this->t('Messages cannot be empty.'));
        return;
      }

      $decoded_messages = @json_decode($messages, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $error = json_last_error_msg();
        \Drupal::logger('together_api')->error('JSON decode error: @error', ['@error' => $error]);
        $form_state->setErrorByName('parameters][messages', $this->t('Invalid JSON format for messages. Error: @error', [
          '@error' => $error,
        ]));
        return;
      }

      if (!is_array($decoded_messages) || empty($decoded_messages)) {
        \Drupal::logger('together_api')->error('Decoded messages is not an array or is empty');
        $form_state->setErrorByName('parameters][messages', $this->t('Messages must be a non-empty array.'));
        return;
      }

      foreach ($decoded_messages as $index => $message) {
        if (!isset($message['role']) || !isset($message['content'])) {
          \Drupal::logger('together_api')->error('Message @index missing role or content', ['@index' => $index]);
          $form_state->setErrorByName('parameters][messages',
            $this->t('Each message must have a "role" and "content" field. Check message at index @index.', ['@index' => $index]));
          return;
        }
      }
    }
    elseif ($test_type === 'completion' || $test_type === 'image') {
      $prompt = $form_state->getValue('prompt') ?? '';

      if (empty($prompt)) {
        \Drupal::logger('together_api')->error('Prompt is empty');
        $form_state->setErrorByName('parameters][prompt', $this->t('Prompt cannot be empty.'));
        return;
      }

      if ($test_type === 'completion') {
        $model = $form_state->getValue('model') ?? '';

        if (empty($model)) {
          \Drupal::logger('together_api')->error('Model is empty');
          $form_state->setErrorByName('parameters][model', $this->t('Model is required.'));
          return;
        }
      }
    }
  }

  /**
   * Ajax callback to update form based on test type selection.
   */
  public function updateForm(array &$form, FormStateInterface $form_state) {
    return $form['parameters'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $test_type = $form_state->getValue('test_type');
      $options_json = $form_state->getValue('options');
      $options = [];

      if (!empty($options_json)) {
        $decoded_options = @json_decode($options_json, TRUE);
        if (json_last_error() === JSON_ERROR_NONE) {
          $options = $decoded_options;
        }
      }

      switch ($test_type) {
        case 'chat':
          $messages = $form_state->getValue('messages') ?? '';

          if (empty($messages)) {
            \Drupal::logger('together_api')->error('Messages is empty');
            throw new \RuntimeException($this->t('Messages cannot be empty.'));
          }

          $decoded_messages = @json_decode($messages, TRUE);

          if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded_messages)) {
            $error = json_last_error_msg();
            \Drupal::logger('together_api')->error('JSON decode error: @error', ['@error' => $error]);
            throw new \RuntimeException($this->t('Invalid message format.'));
          }

          // Ensure messages are properly formatted
          $formatted_messages = array_map(function ($message) {
            return [
              'role' => $message['role'],
              'content' => (string) $message['content'],
            ];
          }, $decoded_messages);

          $model = $form_state->getValue('model') ?? '';

          if (empty($model)) {
            \Drupal::logger('together_api')->error('Model is empty');
            throw new \RuntimeException($this->t('Model is required.'));
          }

          // Format options
          $request_options = array_filter([
            'temperature' => (float) ($form_state->getValue('temperature') ?? 0),
            'max_tokens' => (int) ($form_state->getValue('max_tokens') ?? 0),
          ] + $options);

          $result = $this->client->createChatCompletion($formatted_messages, $model, $request_options);
          break;

        case 'completion':
          $prompt = $form_state->getValue('prompt') ?? '';

          if (empty($prompt)) {
            \Drupal::logger('together_api')->error('Prompt is empty');
            throw new \RuntimeException($this->t('Prompt cannot be empty.'));
          }

          $model = $form_state->getValue('model') ?? '';

          if (empty($model)) {
            \Drupal::logger('together_api')->error('Model is empty');
            throw new \RuntimeException($this->t('Model is required.'));
          }

          // Format options
          $request_options = array_filter([
            'temperature' => (float) ($form_state->getValue('temperature') ?? 0),
            'max_tokens' => (int) ($form_state->getValue('max_tokens') ?? 0),
          ] + $options);

          $result = $this->client->createCompletion($prompt, $model, $request_options);
          break;

        case 'image':
          $prompt = $form_state->getValue('prompt') ?? '';

          if (empty($prompt)) {
            \Drupal::logger('together_api')->error('Prompt is empty');
            throw new \RuntimeException($this->t('Prompt cannot be empty.'));
          }

          // Prepare image generation options
          $options = [
            'model' => $form_state->getValue('model'),
            'steps' => (int) $form_state->getValue('steps'),
            'width' => (int) $form_state->getValue('width'),
            'height' => (int) $form_state->getValue('height'),
          ];

          $negative_prompt = $form_state->getValue('negative_prompt');
          if (!empty($negative_prompt)) {
            $options['negative_prompt'] = $negative_prompt;
          }

          $result = $this->client->createImage($prompt, $options);

          // Save the generated image
          if (isset($result['data'][0]['url'])) {
            $image_url = $result['data'][0]['url'];

            try {
              // Download the image content
              $image_data = file_get_contents($image_url);

              if ($image_data === false) {
                throw new \Exception('Failed to download image from URL');
              }

              // Prepare directory
              $directory = 'public://together_api/images';
              $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

              // Generate filename
              $filename = 'image_' . time() . '_' . substr(md5($prompt), 0, 8) . '.png';
              $uri = $directory . '/' . $filename;

              // Save file using FileRepository service
              $file = $this->fileRepository->writeData(
                $image_data,
                $uri,
                FileExists::Replace
              );

              if ($file) {
                $result['saved_image_url'] = $file->createFileUrl();
                $result['saved_image_uri'] = $file->getFileUri();
              }
            }
            catch (\Exception $e) {
              $this->messenger()->addError($this->t('Failed to save image: @error', [
                '@error' => $e->getMessage(),
              ]));
            }
          }
          break;
      }

      $form_state->set('api_result', $result);
      $this->messenger()->addStatus($this->t('API request successful.'));
    }
    catch (\Exception $e) {
      \Drupal::logger('together_api')->error('API request failed: @error', ['@error' => $e->getMessage()]);
      $this->messenger()->addError($this->t('API request failed: @error', [
        '@error' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }
}
