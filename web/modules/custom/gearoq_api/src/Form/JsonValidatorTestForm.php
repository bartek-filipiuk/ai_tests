<?php

namespace Drupal\gearoq_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a test form for the JSON validator endpoint.
 */
class JsonValidatorTestForm extends FormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gearoq_api_json_validator_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text with JSON'),
      '#description' => $this->t('Enter text containing JSON. The JSON can be wrapped in ```json markers.'),
      '#required' => TRUE,
      '#rows' => 10,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Validate JSON'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      // Create a new client with the API user credentials
      $client = \Drupal::httpClient();
      $url = Url::fromRoute('gearoq_api.json_validator_resource', [], ['absolute' => TRUE])->toString();
      
      $response = $client->request('POST', $url, [
        'json' => ['text' => $form_state->getValue('text')],
        'auth' => ['api', '123'],  // Explicitly use api user credentials
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

      $result = json_decode($response->getBody()->getContents(), TRUE);
      $this->messenger()->addStatus($this->t('Validation successful. Response: @response', [
        '@response' => json_encode($result, JSON_PRETTY_PRINT),
      ]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Validation failed: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }
}
