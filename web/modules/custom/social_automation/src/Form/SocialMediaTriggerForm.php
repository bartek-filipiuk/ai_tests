<?php

namespace Drupal\social_automation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_automation\SocialAutomationServiceInterface;

/**
 * Provides a Social Media Trigger form.
 */
class SocialMediaTriggerForm extends FormBase {

  /**
   * The social automation service.
   *
   * @var \Drupal\social_automation\SocialAutomationServiceInterface
   */
  protected $socialAutomationService;

  /**
   * Constructs a new SocialMediaTriggerForm.
   *
   * @param \Drupal\social_automation\SocialAutomationServiceInterface $social_automation_service
   *   The social automation service.
   */
  public function __construct(SocialAutomationServiceInterface $social_automation_service) {
    $this->socialAutomationService = $social_automation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('social_automation.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_media_trigger_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['social_media'] = [
      '#type' => 'radios',
      '#title' => $this->t('Social Media'),
      '#options' => [
        'twitter' => $this->t('Twitter'),
        'linkedin' => $this->t('LinkedIn'),
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
    $social_media = $form_state->getValue('social_media');
    $current_datetime = new DrupalDateTime('now');
    $formatted_datetime = $current_datetime->format('Y-m-d H:i:s');

    $result = $this->socialAutomationService->triggerWebhook($social_media, $formatted_datetime);

    if ($result) {
      $this->messenger()->addStatus($this->t('Webhook triggered successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to trigger webhook.'));
    }
  }

}