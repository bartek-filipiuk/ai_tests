<?php

namespace Drupal\webform_score\Plugin\WebformElement;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\webform\Plugin\WebformElement\Select;
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
 *   id = "webform_score_select",
 *   label = @Translation("Quiz Select"),
 *   description = @Translation("Provides a form element for a drop-down menu or scrolling selection box."),
 *   category = @Translation("Quiz"),
 * )
 */
class SelectQuiz extends Select implements QuizInterface {

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

  /**
   * A method from WebformElementInterface::prepare().
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#type'] = str_replace('webform_score_', '', $element['#type']);
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnswer($element, WebformSubmissionInterface $webform_submission) {
    $answer = $webform_submission->getElementData($element['#webform_key']);
    if (!(is_array($answer))) {
      // If the answers is not an array, convert it into an array format.
      $answer = [$answer];
    }
    return $this->typedDataManager->create($this->typedDataManager->createDataDefinition($this->getAnswerDataTypeId()), $answer);
  }

}
