<?php

namespace Drupal\repeatable_field_group;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Class RepeatableSelect.
 *
 * @package Drupal\repeatable_field_group
 *
 * Modification of the select list widget for repeatable field group.
 */
class RepeatableSelect {

  /**
   * Create a new widget from the original field.
   *
   * @param array $original_field
   * @param int $index
   *
   * @return array
   */
  public static function createWidget(array $original_field, int $index) {
    $class = '\Drupal\repeatable_field_group\RepeatableSelect';
    $original_field['widget']['#value_callback'] = [
      $class,
      'selectValueCallback'
    ];
    $original_field['widget']['#process'][] = [
      $class,
      'processSelect'
    ];
    $original_field['widget']['#value_index'] = $index;
    $widget = $original_field['widget'];
    $widget['#default_value'] = $original_field['widget']['#default_value'][$index] ?? NULL;
    return $widget;
  }

  /**
   * Override multiple array to keep key => value array for index and multivalue.
   */
  public static function selectValueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (isset($element['#multiple']) && $element['#multiple']) {

        // If an enabled multi-select submits NULL, it means all items are
        // unselected. A disabled multi-select always submits NULL, and the
        // default value should be used.
        if (empty($element['#disabled'])) {
          $user_input = $form_state->getUserInput();
          if (isset($user_input['op'])) {
            return array_filter($input, function($v) { return $v !== '_none'; });
          } else {
            return [$element['#value_index'] => $input[$element['#value_index']]];
          }
        }
        else {
          return isset($element['#default_value']) && is_array($element['#default_value']) ? $element['#default_value'] : array();
        }
      }
      elseif (isset($element['#empty_value']) && $input === (string) $element['#empty_value']) {
        return $element['#empty_value'];
      }
      else {
        return $input;
      }
    }
  }

  /**
   * Remove multiple display after processing Select.
   *
   * Since we are dipslaying a new dropdown for each group,
   * we don't need to display as multiple select box.
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = Select::processSelect($element, $form_state, $complete_form);
    // #multiple select fields need a special #name.
    if ($element['#multiple']) {
      unset($element['#attributes']['multiple']);
    }
    return $element;
  }
}
