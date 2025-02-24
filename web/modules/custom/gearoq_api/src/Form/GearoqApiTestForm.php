<?php

namespace Drupal\gearoq_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Gearoq API test form.
 */
class GearoqApiTestForm extends FormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new GearoqApiTestForm object.
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
    return 'gearoq_api_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
    ];

    $form['field_social_media'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Social Media'),
      '#required' => TRUE,
    ];

    $form['field_update_in_airtable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update in Airtable'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test API'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $data = [
      'body' => $values['body'],
      'field_social_media' => $values['field_social_media'],
      'field_update_in_airtable' => $values['field_update_in_airtable'],
    ];

    try {
      $url = Url::fromRoute('gearoq_api.social_proposition_resource', [], ['absolute' => TRUE])->toString();
      $response = $this->httpClient->request('POST', $url, [
        'json' => $data,
        'auth' => ['api', '123'],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

      $this->messenger()->addStatus($this->t('API request successful. Response: @response', [
        '@response' => $response->getBody()->getContents(),
      ]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('API request failed: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }

}
