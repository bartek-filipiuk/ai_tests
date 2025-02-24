<?php

namespace Drupal\webform_score\Plugin\WebformElement;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_score\Plugin\WebformScoreManagerInterface;
use Drupal\webform_score\QuizInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'radios' quiz element.
 *
 * @WebformElement(
 *   id = "webform_score_checkboxes",
 *   label = @Translation("Quiz Checkboxes"),
 *   description = @Translation("Provides a form element for a set of checkboxes."),
 *   category = @Translation("Quiz"),
 * )
 */
class CheckboxesQuiz extends Checkboxes implements QuizInterface {

  use QuizTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->webformScoreManager = $container->get('plugin.manager.webform_score');
    $instance->typedDataManager = $container->get('typed_data_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerDataTypeId() {
    return 'list';
  }

}
