<?php

namespace Drupal\webform_score\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Radios;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_score\QuizInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'radios' quiz element.
 *
 * @WebformElement(
 *   id = "webform_score_radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons."),
 *   category = @Translation("Quiz"),
 * )
 */
class RadiosQuiz extends Radios implements QuizInterface {

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
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnswer(array $element, WebformSubmissionInterface $webform_submission) {
    $answer = $webform_submission->getElementData($element['#webform_key']);
    return $this->typedDataManager->create($this->typedDataManager->createDataDefinition($this->getAnswerDataTypeId()), $answer);
  }

}
