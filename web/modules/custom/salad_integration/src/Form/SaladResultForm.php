<?php

namespace Drupal\salad_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\salad_integration\SaladApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for retrieving transcription results from Salad API.
 */
class SaladResultForm extends FormBase {

  protected $saladApiClient;

  /**
   * Constructs a new SaladResultForm object.
   *
   * @param \Drupal\salad_integration\SaladApiClient $salad_api_client
   *   The Salad API client service.
   */
  public function __construct(SaladApiClient $salad_api_client) {
    $this->saladApiClient = $salad_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('salad_integration.api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'salad_integration_result_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['job_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Job ID'),
      '#description' => $this->t('Enter the Job ID of the transcription to retrieve results.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Results'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $job_id = $form_state->getValue('job_id');
    
    $response = $this->saladApiClient->getTranscriptionStatus($job_id);
    
    if (isset($response['status'])) {
      switch ($response['status']) {
        case 'succeeded':
          $this->messenger()->addStatus($this->t('Transcription completed successfully.'));
          // Display the transcription results
          $this->displayTranscriptionResults($response);
          break;
        case 'running':
        case 'pending':
          $this->messenger()->addWarning($this->t('Transcription is still in progress. Please check again later.'));
          break;
        case 'failed':
          $this->messenger()->addError($this->t('Transcription failed. Please try again or contact support.'));
          break;
        default:
          $this->messenger()->addWarning($this->t('Unknown status: @status', ['@status' => $response['status']]));
      }
    } else {
      $this->messenger()->addError($this->t('Failed to retrieve transcription status.'));
    }
  }

  /**
   * Helper function to display transcription results.
   *
   * @param array $response
   *   The API response containing transcription results.
   */
  protected function displayTranscriptionResults(array $response) {
    if (isset($response['output']['text'])) {
      $this->messenger()->addMessage($this->t('Transcription:'));
      $this->messenger()->addMessage($response['output']['text']);
    }

    if (isset($response['output']['summary'])) {
      $this->messenger()->addMessage($this->t('Summary:'));
      $this->messenger()->addMessage($response['output']['summary']);
    }

    // You can add more result displays here based on the API response structure
  }
}
