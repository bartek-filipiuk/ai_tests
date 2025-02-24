<?php

namespace Drupal\autobloger_ai\Service;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Service for managing blog post generation queue.
 */
class BlogPostQueueService {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new BlogPostQueueService.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    QueueFactory $queue_factory,
    StateInterface $state,
    ConfigFactoryInterface $config_factory
  ) {
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->configFactory = $config_factory;
  }

  /**
   * Checks if a post should be generated based on schedule settings.
   *
   * Evaluates various scheduling conditions including:
   * - Run on every cron setting
   * - Minimum interval between posts
   * - Maximum posts per week
   * - Scheduled hour
   * - Schedule type (daily, weekly, monthly, custom)
   *
   * @return bool
   *   TRUE if a post should be generated, FALSE otherwise.
   */
  protected function shouldGeneratePost(): bool {
    $config = $this->configFactory->get('autobloger_ai.settings');

    // If run on every cron is enabled, always return true
    if ($config->get('run_on_every_cron', FALSE)) {
      return TRUE;
    }

    $last_run = $this->state->get('autobloger_ai.last_cron_run', 0);
    $now = new DrupalDateTime();
    $current_hour = (int) $now->format('G');
    $current_day_of_week = (int) $now->format('N');
    $current_day_of_month = (int) $now->format('j');

    // Check minimum interval
    $min_interval = (int) $config->get('schedule_min_interval', 24);
    if ((time() - $last_run) < ($min_interval * 3600)) {
      return FALSE;
    }

    // Check maximum posts per week
    $posts_this_week = $this->state->get('autobloger_ai.posts_this_week', 0);
    $max_per_week = $config->get('schedule_max_per_week', 7);
    if ($posts_this_week >= $max_per_week) {
      // Reset counter if it's a new week
      $last_reset = $this->state->get('autobloger_ai.weekly_counter_reset', 0);
      if ((time() - $last_reset) >= 604800) { // 7 days
        $this->state->set('autobloger_ai.posts_this_week', 0);
        $this->state->set('autobloger_ai.weekly_counter_reset', time());
      } else {
        return FALSE;
      }
    }

    // Check if current hour matches configured hour
    $scheduled_hour = $config->get('schedule_hour', 0);
    if ($current_hour !== $scheduled_hour) {
      return FALSE;
    }

    // Check schedule type specific conditions
    $schedule_type = $config->get('schedule_type', 'daily');
    switch ($schedule_type) {
      case 'daily':
        return TRUE;

      case 'weekly':
        $days_of_week = $config->get('schedule_days_week', ['1']);
        return in_array($current_day_of_week, $days_of_week);

      case 'monthly':
        $day_of_month = $config->get('schedule_days_month', 1);
        return $current_day_of_month === $day_of_month;

      case 'custom':
        $days_of_week = $config->get('schedule_days_week', ['1']);
        $day_of_month = $config->get('schedule_days_month', 1);
        return in_array($current_day_of_week, $days_of_week) ||
               $current_day_of_month === $day_of_month;
    }

    return FALSE;
  }

  /**
   * Adds a blog post generation task to the queue.
   *
   * Creates a new queue item for blog post generation and updates
   * the weekly post counter.
   *
   * @param string $prompt
   *   The blog post subject/prompt to be processed.
   */
  public function queueBlogPostGeneration(string $prompt): void {
    $queue = $this->queueFactory->get('autobloger_ai_post_generation');
    $queue->createItem(['prompt' => $prompt]);

    // Increment posts this week counter
    $posts_this_week = $this->state->get('autobloger_ai.posts_this_week', 0);
    $this->state->set('autobloger_ai.posts_this_week', $posts_this_week + 1);
  }

  /**
   * Gets the next unprocessed subject from the state.
   *
   * Manages the rotation of blog subjects by:
   * - Tracking processed subjects in state
   * - Resetting the list when all subjects are processed
   * - Ensuring each subject is used exactly once per cycle
   *
   * @return string|null
   *   The next subject to process, or NULL if no subjects are available.
   */
  protected function getNextSubject(): ?string {
    // Get all subjects from config
    $config = $this->configFactory->get('autobloger_ai.settings');
    $all_subjects = $config->get('blog_subjects', []);
    
    if (empty($all_subjects)) {
      return NULL;
    }

    // Get processed subjects from state
    $processed_subjects = $this->state->get('autobloger_ai.processed_subjects', []);
    
    // If all subjects have been processed, clear the list and start over
    if (count($processed_subjects) >= count($all_subjects)) {
      $this->state->set('autobloger_ai.processed_subjects', []);
      $processed_subjects = [];
    }

    // Find first subject that hasn't been processed
    foreach ($all_subjects as $subject) {
      if (!in_array($subject, $processed_subjects)) {
        // Add to processed subjects list
        $processed_subjects[] = $subject;
        $this->state->set('autobloger_ai.processed_subjects', $processed_subjects);
        return $subject;
      }
    }
    
    return NULL;
  }

  /**
   * Adds blog post generation tasks during cron.
   *
   * This method:
   * 1. Checks if it's time to generate a new post
   * 2. Gets the next subject to process
   * 3. Queues the blog post generation task
   * 4. Updates the last run timestamp
   */
  public function addCronTasks(): void {
    // Check if we should generate a post based on schedule
    if (!$this->shouldGeneratePost()) {
      return;
    }

    // Get the next unprocessed subject
    $subject = $this->getNextSubject();
    
    if ($subject) {
      // Add to queue
      $this->queueBlogPostGeneration($subject);

      // Update last run time
      $this->state->set('autobloger_ai.last_cron_run', time());
    }
  }

}
