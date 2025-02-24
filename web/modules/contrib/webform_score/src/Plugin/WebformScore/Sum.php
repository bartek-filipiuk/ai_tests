<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Score is based on the sub of scores from a set of answers.
 *
 * @WebformScore(
 *   id="sum",
 *   label=@Translation("Sum score from a set"),
 *   compatible_data_types={"*"},
 *   is_aggregation=true,
 * )
 */
class Sum extends WebformScoreAggregateBase {

  /**
   * {@inheritdoc}
   */
  public function getMaxScore() {
    $max_score = 0;
    foreach ($this->createPlugins() as $plugin) {
      $max_score += $plugin->getMaxScore();
    }

    return $max_score;
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answers) {
    $score = 0;
    $plugins = $this->createPlugins();

    if (!($answers instanceof TraversableTypedDataInterface)) {
      // If the answers are not in traversable format, convert them in an array
      // so that further logic can work correctly.
      $temp[] = $answers;
      $answers = $temp;
    }

    foreach ($answers as $answer) {
      foreach ($plugins as $plugin) {
        $score += $plugin->score($answer);
      }
    }

    return $score;
  }

}
