<?php

declare(strict_types=1);

namespace Drupal\ai_api_explorer\Plugin\AiApiExplorer;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\AiProviderInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\AudioToAudio\AudioToAudioInput;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\ai_api_explorer\AiApiExplorerPluginBase;
use Drupal\ai_api_explorer\Attribute\AiApiExplorer;
use Drupal\ai_api_explorer\ExplorerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the ai_api_explorer.
 */
#[AiApiExplorer(
  id: 'audio_to_audio_generator',
  title: new TranslatableMarkup('Audio-To-Audio Explorer'),
  description: new TranslatableMarkup('Contains a form where you can experiment and test the AI audio to audio tool with prompts.'),
)]
final class AudioToAudioGenerator extends AiApiExplorerPluginBase {

  /**
   * Constructs the base plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\ai\Service\AiProviderFormHelper $aiProviderHelper
   *   The AI Provider Helper.
   * @param \Drupal\ai_api_explorer\ExplorerHelper $explorerHelper
   *   The Explorer helper.
   * @param \Drupal\ai\AiProviderPluginManager $providerManager
   *   The Provider Manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The File Url Generator.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The File System.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack, AiProviderFormHelper $aiProviderHelper, ExplorerHelper $explorerHelper, AiProviderPluginManager $providerManager, protected FileUrlGeneratorInterface $fileUrlGenerator, protected FileSystemInterface $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $requestStack, $aiProviderHelper, $explorerHelper, $providerManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('ai.form_helper'),
      $container->get('ai_api_explorer.helper'),
      $container->get('ai.provider'),
      $container->get('file_url_generator'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function isActive(): bool {
    return $this->providerManager->hasProvidersForOperationType('audio_to_audio');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Get the query string for provider_id, model_id.
    $request = $this->getRequest();
    if ($request->query->get('provider_id')) {
      $form_state->setValue('ata_ai_provider', $request->query->get('provider_id'));
    }
    if ($request->query->get('model_id')) {
      $form_state->setValue('ata_ai_model', $request->query->get('model_id'));
    }

    $form = $this->getFormTemplate($form, 'ai-audio-response');

    $form['left']['file'] = [
      '#type' => 'file',
      // Only mp3 files are allowed in this case, since that covers most models.
      '#accept' => '.mp3',
      '#title' => $this->t('Upload your file here. When submitted, your provider will generate a response. Please note that each query counts against your API usage if your provider is a paid provider.'),
      '#description' => $this->t('Based on the complexity of your prompt, traffic, and other factors, a response can take time to complete. Please allow the operation to finish.'),
      '#required' => TRUE,
    ];

    // Load the LLM configurations.
    $this->aiProviderHelper->generateAiProvidersForm($form['left'], $form_state, 'audio_to_audio', 'ata', AiProviderFormHelper::FORM_CONFIGURATION_FULL);
    $form['left']['ata_ai_provider']['#ajax']['callback'] = $this::class . '::loadModelsAjaxCallback';

    $form['left']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate an Audio File'),
      '#ajax' => [
        'callback' => $this->getAjaxResponseId(),
        'wrapper' => 'ai-audio-response',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getResponse(array &$form, FormStateInterface $form_state): array {
    $provider = $this->aiProviderHelper->generateAiProviderFromFormSubmit($form, $form_state, 'audio_to_audio', 'ata');

    // Normalize the input.
    if ($audio_file = $this->generateFile()) {

      /** @var \Drupal\ai\OperationType\GenericType\AudioFile $input */
      $input = new AudioToAudioInput($audio_file);

      try {
        $audio_normalized = $provider->audioToAudio($input, $form_state->getValue('ata_ai_model'), ['ai_api_explorer'])->getNormalized();
      }
      catch (\Exception $e) {
        $form['right']['response']['#context']['ai_response']['response']['#markup'] = $this->explorerHelper->renderException($e);

        // Return early if we have an error.
        return $form['right'];
      }

      // Save the binary data to a file.
      $file_url = $this->fileSystem->saveData($audio_normalized[0]->getBinary(), 'public://audio-to-audio-test.mp3', FileExists::Replace);
      $form['right']['response']['#context']['ai_response']['response'] = [
        '#type' => 'inline_template',
        '#template' => '{{ player|raw }}',
        '#context' => [
          'player' => '<audio controls><source src="' . $this->fileUrlGenerator->generateAbsoluteString($file_url) . '" type="audio/mpeg"></audio>',
        ],
      ];

      $form['right']['response']['#context']['ai_response']['code'] = $this->normalizeCodeExample($provider, $form_state, $audio_file->getFilename());
    }

    return $form['right'];
  }

  /**
   * Gets the normalized code example.
   *
   * @param \Drupal\ai\AiProviderInterface|\Drupal\ai\Plugin\ProviderProxy $provider
   *   The provider.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $filename
   *   The filename.
   *
   * @return array
   *   A render array for the code.
   */
  public function normalizeCodeExample(AiProviderInterface|ProviderProxy $provider, FormStateInterface $form_state, string $filename): array {
    $code = $this->getCodeExampleTemplate();
    $code['code']['#value'] .= '$binary = file_get_contents("' . $filename . '");<br>';

    if (count($provider->getConfiguration())) {
      $code['code']['#value'] .= $this->addProviderCodeExample($provider);
    }

    $code['code']['#value'] .= "\$ai_provider = \Drupal::service('ai.provider')->createInstance('" . $form_state->getValue('ata_ai_provider') . '\');<br>';
    $code['code']['#value'] .= "\$ai_provider->setConfiguration(\$config);<br>";
    $code['code']['#value'] .= "// Normalize the input.<br>";
    $code['code']['#value'] .= "\$audio_file = new \Drupal\ai\OperationType\GenericType\AudioFile(\$binary, 'audio/mp3', '" . $filename . "');<br>";
    $code['code']['#value'] .= "\$input = new \Drupal\ai\OperationType\AudioToAudio\AudioToAudioInput(\$audio_file);<br>";
    $code['code']['#value'] .= "// \$response will be a AudioFile with the text.<br>";
    $code['code']['#value'] .= "\$response = \$ai_provider->audioToAudio(\$input, '" . $form_state->getValue('ata_ai_model') . '\', ["your_module_name"]);<br><br>';
    $code['code']['#value'] .= "// This gets an array of \Drupal\ai\OperationType\GenericType\AudioFile.<br>";
    $code['code']['#value'] .= "\$normalized = \$response->getNormalized();<br><br>";
    $code['code']['#value'] .= "// Examples Possibility #1 - get binary from the first audio.<br>";
    $code['code']['#value'] .= '$binaries = $normalized[0]->getAsBinary();<br>';
    $code['code']['#value'] .= "// Examples Possibility #2 - get as base 64 encoded string from the first audio.<br>";
    $code['code']['#value'] .= '$base64 = $normalized[0]->getAsBase64EncodedString();<br>';
    $code['code']['#value'] .= "// Examples Possibility #3 - get as generated media from the first audio.<br>";
    $code['code']['#value'] .= '$media = $normalized[0]->getAsMediaEntity("audio", "public://", "audio.mp3");<br>';
    $code['code']['#value'] .= "// Examples Possibility #4 - get as file entity from the first audio.<br>";
    $code['code']['#value'] .= '$file = $normalized[0]->getAsFileEntity("public://", "audio.mp3");<br><br>';
    $code['code']['#value'] .= "// Another possibility is to get the raw response from the provider.<br>";
    $code['code']['#value'] .= '$raw = $response->getRaw();<br>';

    return $code;
  }

}
