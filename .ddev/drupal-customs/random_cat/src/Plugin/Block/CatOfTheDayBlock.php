<?php

namespace Drupal\random_cat\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Cat of the day' block.
 *
 * @Block(
 *   id = "random_cat",
 *   admin_label = @Translation("Random cat"),
 *   category = @Translation("Cats")
 * )
 */
class CatOfTheDayBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'image' => ['#theme' => 'cat_of_the_day_block'],
      '#attached' => [
        'library' => ['random_cat/rabdom_cat.core'],
      ],
    ];
  }

}
