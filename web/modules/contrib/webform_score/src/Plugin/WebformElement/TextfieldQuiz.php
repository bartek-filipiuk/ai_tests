<?php

namespace Drupal\webform_score\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform_score\QuizInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'textfield' quiz element.
 *
 * @WebformElement(
 *   id = "webform_score_textfield",
 *   label = @Translation("Textfield"),
 *   description = @Translation("Provides a text field element with pre-defined correct answer."),
 *   category = @Translation("Quiz"),
 * )
 */
class TextfieldQuiz extends TextField implements QuizInterface {

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
  protected function getAnswerDataTypeId() {
    return 'string';
  }

}
