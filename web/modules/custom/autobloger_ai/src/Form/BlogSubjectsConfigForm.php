<?php

namespace Drupal\autobloger_ai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configure AutoblogerAI blog subjects.
 */
class BlogSubjectsConfigForm extends ConfigFormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Constructs a new BlogSubjectsConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    StateInterface $state
  ) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autobloger_ai.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autobloger_ai_blog_subjects_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autobloger_ai.settings');

    // Blog Subjects section
    $form['subjects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Blog Subjects'),
    ];

    $subjects = $config->get('blog_subjects');
    // Ensure we have an array of subjects
    if (!is_array($subjects)) {
      $subjects = [];
    }
    $subjects_text = implode("\n", $subjects);

    $form['subjects']['blog_subjects'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blog Subjects'),
      '#description' => $this->t('Enter blog subjects, one per line. These will be used to generate blog posts during cron runs.'),
      '#default_value' => $subjects_text,
      '#rows' => 10,
    ];

    // Schedule section
    $form['schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Generation Schedule'),
    ];

    $form['schedule']['run_on_every_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run on every cron'),
      '#description' => $this->t('If checked, a blog post will be generated on every cron run, ignoring schedule settings.'),
      '#default_value' => $config->get('run_on_every_cron', FALSE),
    ];

    $form['schedule']['schedule_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#options' => [
        'daily' => $this->t('Daily'),
        'weekly' => $this->t('Weekly'),
        'monthly' => $this->t('Monthly'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $config->get('schedule_type', 'daily'),
      '#ajax' => [
        'callback' => '::updateScheduleForm',
        'wrapper' => 'schedule-settings-wrapper',
      ],
      '#states' => [
        'visible' => [
          ':input[name="run_on_every_cron"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['schedule']['settings'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'schedule-settings-wrapper'],
    ];

    $schedule_type = $form_state->getValue('schedule_type', $config->get('schedule_type', 'daily'));

    // Hour selection (always shown)
    $hours = range(0, 23);
    $hours = array_combine($hours, array_map(function($hour) {
      return sprintf('%02d:00', $hour);
    }, $hours));

    $form['schedule']['settings']['hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Hour'),
      '#options' => $hours,
      '#default_value' => $config->get('schedule_hour', 0),
    ];

    // Days of week (for weekly and custom)
    if (in_array($schedule_type, ['weekly', 'custom'])) {
      $form['schedule']['settings']['days_of_week'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Days of Week'),
        '#options' => [
          '1' => $this->t('Monday'),
          '2' => $this->t('Tuesday'),
          '3' => $this->t('Wednesday'),
          '4' => $this->t('Thursday'),
          '5' => $this->t('Friday'),
          '6' => $this->t('Saturday'),
          '7' => $this->t('Sunday'),
        ],
        '#default_value' => $config->get('schedule_days_week', ['1']),
      ];
    }

    // Days of month (for monthly and custom)
    if (in_array($schedule_type, ['monthly', 'custom'])) {
      $days = range(1, 31);
      $days = array_combine($days, $days);
      $form['schedule']['settings']['days_of_month'] = [
        '#type' => 'select',
        '#title' => $this->t('Day of Month'),
        '#options' => $days,
        '#default_value' => $config->get('schedule_days_month', 1),
      ];
    }

    // Advanced settings
    $form['schedule']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Settings'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="run_on_every_cron"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['schedule']['advanced']['min_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum hours between posts'),
      '#description' => $this->t('Minimum number of hours that must pass between generating posts.'),
      '#min' => 1,
      '#max' => 168, // One week
      '#default_value' => $config->get('schedule_min_interval', 24),
    ];

    $form['schedule']['advanced']['max_per_week'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum posts per week'),
      '#description' => $this->t('Maximum number of posts that can be generated in a week.'),
      '#min' => 1,
      '#max' => 21,
      '#default_value' => $config->get('schedule_max_per_week', 7),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to update schedule settings based on frequency selection.
   */
  public function updateScheduleForm(array &$form, FormStateInterface $form_state) {
    return $form['schedule']['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get and clean up subjects from textarea
    $subjects = array_values(array_filter(
      explode("\n", $form_state->getValue('blog_subjects')),
      function ($line) {
        return trim($line) !== '';
      }
    ));

    $config = $this->config('autobloger_ai.settings');

    // Get current processed subjects from state
    $processed_subjects = $this->state->get('autobloger_ai.processed_subjects', []);

    // Filter out processed subjects that no longer exist in the new configuration
    $filtered_processed_subjects = array_values(
      array_intersect($processed_subjects, $subjects)
    );

    // Update the state with filtered list
    $this->state->set('autobloger_ai.processed_subjects', $filtered_processed_subjects);

    // Save subjects
    $config->set('blog_subjects', $subjects);

    // Save run on every cron setting
    $config->set('run_on_every_cron', $form_state->getValue('run_on_every_cron'));

    // Save schedule settings
    $config
      ->set('schedule_type', $form_state->getValue('schedule_type'))
      ->set('schedule_hour', $form_state->getValue('hour'))
      ->set('schedule_min_interval', $form_state->getValue('min_interval'))
      ->set('schedule_max_per_week', $form_state->getValue('max_per_week'));

    // Save type-specific settings
    $schedule_type = $form_state->getValue('schedule_type');
    if (in_array($schedule_type, ['weekly', 'custom'])) {
      $config->set('schedule_days_week', array_filter($form_state->getValue('days_of_week')));
    }
    if (in_array($schedule_type, ['monthly', 'custom'])) {
      $config->set('schedule_days_month', $form_state->getValue('days_of_month'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
