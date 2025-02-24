<?php

namespace Drupal\webform_score\Plugin\WebformScore;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Score is based on the maximum value from a set of scores.
 *
 * @WebformScore(
 *   id="maximum",
 *   label=@Translation("Max score from a set"),
 *   compatible_data_types={"*"},
 *   is_aggregation=true,
 * )
 */
class Maximum extends WebformScoreAggregateBase {

  /**
   * {@inheritdoc}
   */
  public function getMaxScore() {
    $max_scores = [];
    foreach ($this->createPlugins() as $plugin) {
      $max_scores[] = $plugin->getMaxScore();
    }

    return max($max_scores);
  }

  /**
   * {@inheritdoc}
   */
  public function score(TypedDataInterface $answers) {
    $scores = [];
    $plugins = $this->createPlugins();

    if (!($answers instanceof TraversableTypedDataInterface)) {
      // If the answers are not in traversable format, convert them in an array
      // so that further logic can work correctly.
      $answers = [$answers];
    }

    foreach ($answers as $answer) {
      foreach ($plugins as $plugin) {
        $scores[] = $plugin->score($answer);
      }
    }

    return max($scores);
  }

}
