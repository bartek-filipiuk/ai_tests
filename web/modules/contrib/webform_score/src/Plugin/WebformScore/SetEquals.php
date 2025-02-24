<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Test that every value in the set is the same.
 *
 * @WebformScore(
 *   id="set_equals",
 *   label=@Translation("Every value in the set is the same."),
 *   compatible_data_types={"*"},
 *   is_aggregation=true,
 * )
 */
class SetEquals extends WebformScoreAggregateBase {

  /**
   * Returns the maximum score you can get for answering this question.
   *
   * @todo This could be moved to config if desired?
   *
   * @return int
   *   The maximum score for asking this question correctly.
   */
  public function getMaxScore() {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answers) {

    $answerValues = $this->getValues($answers);
    $expectedValues = $this->getExpectedValues();

    return $this->valuesMatch($answerValues, $expectedValues) ? $this->getMaxScore() : 0;
  }

  /**
   * Return whether 2 arrays of strings match.
   *
   * @param array $values1
   *   A first array of values.
   * @param array $values2
   *   A second array of values.
   *
   * @return bool
   *   Whether the arrays match.
   */
  public function valuesMatch(array $values1, array $values2) {
    $values1 = $this->normaliseValues($values1);
    $values2 = $this->normaliseValues($values2);

    // Compare each way to make sure the answers are exactly the same.
    return empty(array_diff($values1, $values2)) && empty(array_diff($values2, $values1));
  }

  /**
   * Trim an array of strings.
   *
   * @param array $values
   *   The strings to be trimmed.
   *
   * @return array
   *   The trimmed strings.
   */
  protected function normaliseValues(array $values) {
    // Remove any rogue whitespace.
    $values = array_map('trim', $values);
    return $values;
  }

  /**
   * Accepts an array of TypedData objects. Returns an array of their values.
   */
  protected function getValues(TypedDataInterface $answers) {
    $answerValues = [];

    foreach ($answers as $key => $answer) {
      $answerValues[] = $answer->getValue();
    }

    return $answerValues;
  }

  /**
   * Returns an array of the expected answers.
   */
  protected function getExpectedValues() {

    if (isset($this->configuration['correct_answers'])) {
      return explode(',', $this->configuration['correct_answers']);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // This deliberately doesn't call the parent.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // This deliberately doesn't call the parent.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // This deliberately doesn't call the parent.
    $form['correct_answers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected answers'),
      '#description' => 'Please enter the values of the checkboxes that should be checked, separated by commas. If the chosen set matches this set, the answer to this question is considered correct and the full score is awarded.',
      '#default_value' => $this->configuration['correct_answers'] ?? '',

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    // This deliberately doesn't call the parent.
    // @todo Decide whethere there's a validation we can apply.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    // This deliberately doesn't call the parent.
    $this->configuration['correct_answers'] = $form_state->getValue('correct_answers');
  }

  /**
   * Ajax callback to inject webform score plugin configuration form.
   */
  public static function ajaxForm($form, FormStateInterface $form_state) {
    // This deliberately doesn't call the parent.
  }

  /**
   * Create all the underlying score plugin objects.
   */
  protected function createPlugins() {
    // This deliberately doesn't call the parent.
    return [];
  }

}
