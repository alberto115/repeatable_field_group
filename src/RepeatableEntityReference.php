<?php

namespace Drupal\repeatable_field_group;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

class RepeatableEntityReference {

  /**
   * Create a new widget from the original field.
   */
  public static function createWidget($original_field, $index) {
    // Set widget with one value only.
    $widget = array_filter($original_field['widget'], function($key) use ($index) {
      return !is_numeric($key) || $key === $index;
    }, ARRAY_FILTER_USE_KEY);
    // If no value for this widget, create one from last valid element.
    if (!isset($widget[$index])) {
      $last_element_index = $original_field['widget']['#max_delta'];
      // Set last original element as new widget
      $widget[$index] = $original_field['widget'][$last_element_index];
      // Update delta and weight for this field.
      $widget[$index]['target_id']['#delta'] = $index;
      $widget[$index]['target_id']['#weight'] = $index;
    }
    return $widget;
  }
}
