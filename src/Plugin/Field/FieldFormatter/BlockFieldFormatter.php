<?php

namespace Drupal\block_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'basic_string' formatter.
 *
 * @FieldFormatter(
 *   id = "block_field",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $block_manager = \Drupal::service('plugin.manager.block');

    foreach ($items as $delta => $item) {
      $config = [];
      $plugin = $block_manager->createInstance($item->target_id, $config);

      if ($item->configuration) {
        $configuration = json_decode($item->configuration, TRUE);
        $plugin->setConfiguration($configuration);
      }

      $plugin_id = $plugin->getPluginId();
      $base_id = $plugin->getBaseId();
      $derivative_id = $plugin->getDerivativeId();
      $configuration = $plugin->getConfiguration();

      $elements[$delta] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#weight' => $delta,
        '#configuration' => $configuration,
        '#plugin_id' => $plugin_id,
        '#base_plugin_id' => $base_id,
        '#derivative_plugin_id' => $derivative_id,
        'content' => $plugin->build()
      ];
    }

    return $elements;
  }

}
