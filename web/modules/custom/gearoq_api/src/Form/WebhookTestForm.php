<?php

namespace Drupal\gearoq_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides a webhook test form.
 */
class WebhookTestForm extends FormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WebhookTestForm.
   */
  public function __construct(
    ClientInterface $http_client,
    MessengerInterface $messenger
  ) {
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gearoq_api_webhook_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['webhook_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Webhook URL'),
      '#description' => $this->t('Enter the full URL where the webhook data should be sent'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Enter the message to be sent'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        'pending' => $this->t('Pending'),
        'in_progress' => $this->t('In Progress'),
        'completed' => $this->t('Completed'),
      ],
      '#required' => TRUE,
      '#default_value' => 'pending',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Webhook'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    $data = [
      'message' => $values['message'],
      'status' => $values['status'],
      'timestamp' => \Drupal::time()->getCurrentTime(),
    ];

    try {
      $response = $this->httpClient->request('POST', $values['webhook_url'], [
        'json' => $data,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

      $this->messenger->addStatus($this->t('Webhook sent successfully. Response code: @code', [
        '@code' => $response->getStatusCode(),
      ]));
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Failed to send webhook: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }

} 