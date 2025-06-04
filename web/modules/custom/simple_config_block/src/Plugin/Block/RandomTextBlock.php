<?php

declare(strict_types=1);

namespace Drupal\simple_config_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with random text.
 *
 * @Block(
 *   id = "random_text_block",
 *   admin_label = @Translation("Random text block")
 * )
 */
class RandomTextBlock extends BlockBase {

  /**
   * Possible texts.
   *
   * @var string[]
   */
  protected array $texts = [
    'To jest przykład losowego tekstu.',
    'Drupal jest super!',
    'Miłego dnia!',
    'Koduj z głową!',
  ];

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $text = $this->texts[array_rand($this->texts)];

    return [
      '#theme' => 'random_text_block',
      '#text' => $text,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
