<?php

namespace Drupal\make_scenarios\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MakeScenarioForm extends FormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new MakeScenarioForm object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
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
    return 'make_scenario_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit to Make.com'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'id' => 1,
      'user' => 'admin',
      'content' => 'this is strong content',
    ];

    try {
      $response = $this->httpClient->post('https://hook.eu2.make.com/3fi1y38ls9w58msyengjs3g4auij96jp', [
        'json' => $data,
      ]);

      $this->messenger()->addStatus($this->t('Data sent successfully to Make.com'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error sending data to Make.com: @error', ['@error' => $e->getMessage()]));
    }
  }
}