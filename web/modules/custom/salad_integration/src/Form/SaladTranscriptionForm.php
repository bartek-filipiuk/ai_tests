<?php

namespace Drupal\salad_integration\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\salad_integration\SaladApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides a form for transcribing audio/video files using Salad API.
 */
class SaladTranscriptionForm extends FormBase {

  protected $saladApiClient;
  protected $fileSystem;
  protected $renderer;
  protected $fileRepository;

  /**
   * Constructs a new SaladTranscriptionForm object.
   *
   * @param \Drupal\salad_integration\SaladApiClient $salad_api_client
   *   The Salad API client service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(SaladApiClient $salad_api_client, FileSystemInterface $file_system, RendererInterface $renderer, FileRepositoryInterface $file_repository) {
    $this->saladApiClient = $salad_api_client;
    $this->fileSystem = $file_system;
    $this->renderer = $renderer;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('salad_integration.api_client'),
      $container->get('file_system'),
      $container->get('renderer'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salad_integration_transcription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['file_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload file'),
      '#description' => $this->t('Allowed extensions: aiff, flac, m4a, mp3, wav, mkv, mov, webm, wma, mp4, avi'),
      '#upload_validators' => [
        'file_validate_extensions' => ['aiff flac m4a mp3 wav mkv mov webm wma mp4 avi'],
      ],
      '#required' => TRUE,
    ];

    $form['upload_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Upload File'),
      '#ajax' => [
        'callback' => '::uploadFile',
        'wrapper' => 'file-url-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Uploading file...'),
        ],
      ],
    ];

    $form['file_url'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'file-url-field'],
    ];

    $form['file_url_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File URL'),
      '#description' => $this->t('The absolute URL of the uploaded file.'),
      '#disabled' => TRUE,
      '#prefix' => '<div id="file-url-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['return_as_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Return as file'),
      '#default_value' => FALSE,
    ];

    $form['language_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Language'),
      '#options' => [
        'en' => $this->t('English'),
        'pl' => $this->t('Polish'),
        'de' => $this->t('German'),
        // Add more language options as needed
      ],
      '#default_value' => 'en',
      '#required' => TRUE,
    ];

    $form['translate'] = [
      '#type' => 'select',
      '#title' => $this->t('Translate To'),
      '#options' => [
        'to_eng' => $this->t('English'),
      ],
      '#empty_option' => $this->t('- None -'),
    ];

    $form['sentence_level_timestamps'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sentence-level timestamps'),
      '#default_value' => TRUE,
    ];

    $form['word_level_timestamps'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Word-level timestamps'),
      '#default_value' => TRUE,
    ];

    $form['diarization'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Diarization'),
      '#default_value' => TRUE,
    ];

    $form['sentence_diarization'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sentence diarization'),
      '#default_value' => TRUE,
    ];

    $form['srt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate SRT'),
      '#default_value' => TRUE,
    ];

    $form['summarize'] = [
      '#type' => 'number',
      '#title' => $this->t('Summarize (word count)'),
      '#default_value' => 100,
      '#min' => 1,
    ];

    $languages = [
      'german' => $this->t('German'),
      'italian' => $this->t('Italian'),
      'french' => $this->t('French'),
      'spanish' => $this->t('Spanish'),
      'english' => $this->t('English'),
      'portuguese' => $this->t('Portuguese'),
      'hindi' => $this->t('Hindi'),
      'thai' => $this->t('Thai'),
    ];

    $form['llm_translation'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('LLM Translation'),
      '#options' => $languages,
    ];

    $form['srt_translation'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('SRT Translation'),
      '#options' => $languages,
    ];

    $form['custom_vocabulary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom Vocabulary'),
      '#description' => $this->t('Enter custom terms separated by commas.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Transcribe'),
      '#attributes' => ['id' => 'edit-submit'],
      '#states' => [
        'disabled' => [
          ':input[name="file_url"]' => ['value' => ''],
        ],
      ],
    ];

    $form['#attached']['library'][] = 'salad_integration/transcription-form';

    return $form;
  }

  /**
   * Ajax callback for the upload button.
   */
  public function uploadFile(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $files = $this->getRequest()->files->get('files', []);
    if (!empty($files['file_upload'])) {
      $file = $files['file_upload'];
      $directory = 'public://salad_transcription';

      // Ensure the directory exists
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      try {
        // Save the file
        $file = $this->fileRepository->writeData(
          file_get_contents($file->getRealPath()),
          $directory . '/' . $file->getClientOriginalName(),
          FileExists::Replace
        );

        // Make the file permanent
        $file->setPermanent();
        $file->save();

        $file_url = $file->createFileUrl(FALSE);
        $form['file_url']['#value'] = $file_url;
        $form['file_url_display']['#value'] = $file_url;
        $response->addCommand(new HtmlCommand('#file-url-wrapper', $this->renderer->render($form['file_url_display'])));
        $response->addCommand(new InvokeCommand('#file-url-field', 'val', [$file_url]));
        $response->addCommand(new InvokeCommand('input[name="file_url"]', 'trigger', ['change']));
      } catch (\Exception $e) {
        $this->logger('salad_integration')->error('File upload failed: @error', ['@error' => $e->getMessage()]);
        $response->addCommand(new HtmlCommand('#file-url-wrapper', $this->t('File upload failed: @error', ['@error' => $e->getMessage()])));
      }
    } else {
      $response->addCommand(new HtmlCommand('#file-url-wrapper', $this->t('No file uploaded.')));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $files = $this->getRequest()->files->get('files', []);
    if (empty($files['file_upload'])) {
      $form_state->setErrorByName('file_upload', $this->t('No file was uploaded.'));
    }
    else {
      $file = $files['file_upload'];
      // Validate file size (2GB limit)
      $max_size = 2 * 1024 * 1024 * 1024; // 2GB in bytes
      if ($file->getSize() > $max_size) {
        $form_state->setErrorByName('file_upload', $this->t('The file is too large. Maximum allowed size is 2GB.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_url = $form_state->getValue('file_url');

    if ($file_url) {
      $input = [
        'url' => $file_url,
        //'language_code' => $form_state->getValue('language_code'),
      ];

      // Only add optional parameters if they're set to true or have a value
      if ($form_state->getValue('return_as_file')) {
        $input['return_as_file'] = FALSE;
      }
      if ($form_state->getValue('sentence_level_timestamps')) {
        $input['sentence_level_timestamps'] = true;
      }
      if ($form_state->getValue('word_level_timestamps')) {
        $input['word_level_timestamps'] = true;
      }
      if ($form_state->getValue('diarization')) {
        $input['diarization'] = true;
      }
      if ($form_state->getValue('sentence_diarization')) {
        $input['sentence_diarization'] = true;
      }
      if ($form_state->getValue('srt')) {
        $input['srt'] = true;
      }
      if ($form_state->getValue('summarize')) {
        $input['summarize'] = (int) $form_state->getValue('summarize');
      }

      // Only add translate if it's selected
      if ($form_state->getValue('translate')) {
        $input['translate'] = $form_state->getValue('translate');
      }

      // Only add llm_translation and srt_translation if they're not empty
      $llm_translation = array_filter($form_state->getValue('llm_translation'));
      if (!empty($llm_translation)) {
        $input['llm_translation'] = implode(', ', array_keys($llm_translation));
      }

      $srt_translation = array_filter($form_state->getValue('srt_translation'));
      if (!empty($srt_translation)) {
        $input['srt_translation'] = implode(', ', array_keys($srt_translation));
      }

      // Only add custom_vocabulary if it's not empty
      $custom_vocabulary = $form_state->getValue('custom_vocabulary');
      if (!empty($custom_vocabulary)) {
        $input['custom_vocabulary'] = $custom_vocabulary;
      }

      try {
        $response = $this->saladApiClient->transcribe($input);

        if (isset($response['id'])) {
          // Save the new Transcription entity
          $transcription = \Drupal::entityTypeManager()->getStorage('transcription')->create([
            'job_id' => $response['id'],
            'status' => 'pending', // Initial status
            'result_data' => json_encode($input), // Store the input data for now
          ]);
          $transcription->save();

          $this->messenger()->addStatus($this->t('Transcription job started. Job ID: @id', ['@id' => $response['id']]));
        } else {
          $this->messenger()->addError($this->t('Failed to start transcription job.'));
          // Add debugging information
          $this->messenger()->addWarning($this->t('API Response: @response', ['@response' => print_r($response, TRUE)]));
        }
      } catch (\Exception $e) {
        $this->messenger()->addError($this->t('An error occurred: @message', ['@message' => $e->getMessage()]));
        // Add more detailed error information
        $this->messenger()->addWarning($this->t('Error details: @details', ['@details' => $e->getTraceAsString()]));
        // Log the input that caused the error
        $this->logger('salad_integration')->error('Error input: @input', ['@input' => print_r($input, TRUE)]);
      }
    } else {
      $this->messenger()->addError($this->t('No file URL provided. Please upload a file first.'));
    }
  }
}