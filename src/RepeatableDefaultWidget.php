<?php

namespace Drupal\repeatable_field_group;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Class RepeatableDefaultWidget.
 *
 * @package Drupal\repeatable_field_group
 *
 * Modification of the select list widget for repeatable field group.
 */
class RepeatableDefaultWidget {

  /**
   * Create a new widget from the original field.
   *
   * @param array $original_field
   * @param int $index
   *
   * @return array
   */
  public static function createWidget(array $original_field, int $index) {
    $widget = array_filter($original_field['widget'], function($key) use ($index) {
      return !is_numeric($key) || $key == $index;
    }, ARRAY_FILTER_USE_KEY);

    return $widget;
  }
}
