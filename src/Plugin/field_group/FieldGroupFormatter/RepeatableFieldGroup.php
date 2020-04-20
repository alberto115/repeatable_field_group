<?php

namespace Drupal\repeatable_field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Repatable field group element.
 *
 * @FieldGroupFormatter(
 *   id = "repeatable_field_group",
 *   label = @Translation("Repeatable field group"),
 *   description = @Translation("Group together fields with infinite values."),
 *   supported_contexts = {
 *     "form"
 *   }
 * )
 */
class RepeatableFieldGroup extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {
    // Set settings for opening and closing
    $element += [
      '#type' => 'details',
      '#title' => $this->getLabel(),
      '#description' => $this->getSetting('description'),
      '#open' => TRUE,
      '#items_count' => 0
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getUniqueId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.details';
      $element['#attached']['library'][] = 'field_group/core';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function addMore(array $form, FormStateInterface $form_state) {

    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $group = $button['#group'];

    // Add one more of each field
    foreach($button['#fields_parents'] as $field_name => $parents) {
      $field_state = \Drupal\Core\Field\WidgetBase::getWidgetState($parents, $field_name, $form_state);
      $field_state['items_count']++;
      \Drupal\Core\Field\WidgetBase::setWidgetState($parents, $field_name, $form_state, $field_state);
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

}
