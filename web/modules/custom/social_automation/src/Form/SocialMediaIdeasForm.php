<?php

namespace Drupal\social_automation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a Social Automation form.
 */
class SocialMediaIdeasForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new SocialMediaIdeasForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_automation_ideas_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#rows' => 20,
      '#required' => TRUE,
    ];

    $form['external_link'] = [
      '#type' => 'url',
      '#title' => $this->t('External Link'),
    ];

    $form['youtube_link'] = [
      '#type' => 'url',
      '#title' => $this->t('YouTube Link'),
    ];

    $form['platforms'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Platforms'),
      '#options' => [
        'twitter' => $this->t('X (Twitter)'),
        'linkedin' => $this->t('LinkedIn'),
        'youtube_script' => $this->t('YouTube Script'),
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('social_automation.settings');
    $webhook_url = $config->get('webhook_url');

    if (!$webhook_url) {
      $this->messenger()->addError($this->t('Webhook URL is not configured. Please set it in the module settings.'));
      return;
    }

    $platforms = $form_state->getValue('platforms');
    $platforms_data = [
      'twitter' => isset($platforms['twitter']) && $platforms['twitter'] !== 0 ? 1 : 0,
      'linkedin' => isset($platforms['linkedin']) && $platforms['linkedin'] !== 0 ? 1 : 0,
      'youtube_script' => isset($platforms['youtube_script']) && $platforms['youtube_script'] !== 0 ? 1 : 0,
    ];

    $data = [
      'text' => $form_state->getValue('text'),
      'external_link' => $form_state->getValue('external_link'),
      'youtube_link' => $form_state->getValue('youtube_link'),
      'platforms' => $platforms_data,
    ];

    $content_text = $form_state->getValue('text');
    $title_content = substr($content_text, 0, 50);
    $node_title = 'Social Idea Starter - ' . $title_content;

    // Create a new social_idea_starter node
    $node = Node::create([
      'type' => 'social_idea_starter',
      'title' => $node_title,
      'body' => [
        'value' => $content_text,
        'format' => 'basic_html',
      ],
    ]);

    // Set the field_social_media taxonomy terms
    $selected_platforms = array_filter($platforms, function($value) {
      return $value !== 0;
    });

    if (!empty($selected_platforms)) {
      $term_references = [];
      foreach ($selected_platforms as $platform => $value) {
        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $platform, 'vid' => 'social_media']);
        if (!empty($terms)) {
          $term_references[] = ['target_id' => reset($terms)->id()];
        }
        else {
          // Create a new term if it doesn't exist
          $new_term = Term::create([
            'name' => $platform,
            'vid' => 'social_media',
          ]);
          $new_term->save();
          $term_references[] = ['target_id' => $new_term->id()];
        }
      }
      $node->set('field_social_media', $term_references);
    }

    // Save the node
    $node->save();

    $this->messenger()->addStatus($this->t('Social idea content has been created.'));

    // Send data to webhook
    try {
      $response = $this->httpClient->request('POST', $webhook_url, [
        'json' => $data,
      ]);

      if ($response->getStatusCode() == 200) {
        $this->messenger()->addStatus($this->t('Data sent successfully to the webhook.'));
      }
      else {
        $this->messenger()->addError($this->t('Failed to send data to the webhook. Status code: @code', ['@code' => $response->getStatusCode()]));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred while sending data to the webhook: @error', ['@error' => $e->getMessage()]));
    }
  }

}
