<?php

namespace Drupal\block_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "block_configurator",
 *   label = @Translation("Block Configurator"),
 *   description = @Translation("A block configurator."),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockConfiguratorWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];
    $parents[] = $field_name;
    $parents[] = $delta;
    $id = Html::getId($field_name . '-' . implode('-', $parents) . '-' . $delta);
    $block_manager = \Drupal::service('plugin.manager.block');

    $definitions = $block_manager->getDefinitionsForContexts();
    $configuration = [];

    $options = [
      '_none' => $this->t('- None -')
    ];

    foreach ($definitions as $definition) {
      $options[$definition['id']] = $definition['admin_label'];
    }

    if (isset($items[$delta]->target_id) && $items[$delta]->target_id != '_none') {
      $block_plugin_name = $items[$delta]->target_id;
      if ($items[$delta]->configuration) {
        $configuration = json_decode($items[$delta]->configuration, TRUE);
      }
    }

    $form_state_values = $form_state->getValues();
    $form_state_value = NestedArray::getValue($form_state_values, $parents);

    if ($form_state_value) {
      $block_plugin_name = $form_state_value['target_id'];
      if ($form_state_value['configuration']) {
        $configuration = $form_state_value['configuration'];
      }
    }

    if (isset($block_plugin_name)) {
      $sub_form = $this->getSubForm($block_plugin_name, $configuration);
    }

    $element += [
      '#type' => 'container',
      'target_id' => [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => isset($items[$delta]->target_id) ? $items[$delta]->target_id : NULL,
        '#element_validate' => [[get_class($this), 'validateElement']],
        '#ajax' => [
          'callback' => array($this, 'loadDefinitionConfigForm'),
          'event' => 'change',
          'wrapper' => $id
        ]
      ],
      'form' => [
        '#id' => $id,
        '#type' => 'container',
        'subform' => isset($sub_form) ? $sub_form : NULL
      ]
    ];

    return $element;
  }

  private function getSubForm($block_plugin_name, $configuration = []) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $plugin_block = $block_manager->createInstance($block_plugin_name, $configuration);
    $plugin_block->setConfiguration($configuration);
    return $plugin_block->buildConfigurationForm([], new FormState());
  }

  public function loadDefinitionConfigForm (array &$form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($select['#array_parents'], 0, -1));

    return $element['form'];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    if ($element['#value'] == '_none') {
      $element['#value'] = NULL;
    }

    $form_state->setValueForElement($element, $element['#value']);
  }


}
