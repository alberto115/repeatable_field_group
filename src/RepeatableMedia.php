<?php

namespace Drupal\repeatable_field_group;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\MediaLibraryState;

class RepeatableMedia extends MediaLibraryWidget {

  /**
   * Create a new widget from the original field.
   */
  public static function createWidget($original_field, $index) {
    // Get selection items for this index
    $selection = array_filter($original_field['widget']['selection'], function($el, $key) use ($index) {
      return is_numeric($key) && $el['#attributes']['data-media-library-item-delta'] == $index;
    }, ARRAY_FILTER_USE_BOTH);

    // Associate orignal widget to new one.
    $widget = $original_field['widget'];

    // Update remove_button which lives inside selection group.
    if (isset($selection[$index]['remove_button'])) {
      $selection[$index]['remove_button']['#submit'] = [
        [
          static::class,
          'removeItem'
        ]
      ];
      $selection[$index]['remove_button']['#ajax']['wrapper'] .= "-$index";
      $selection[$index]['remove_button']['#name'] .= "-$index";
    }

    // Assign selection values
    $widget['selection'] = $selection;
    $widget['#delta'] = $index;

    // Set wrapper id
    $widget['#attributes']['id'] .= "-$index";

    // Update open_button field
    $widget['open_button']['#value_index'] = $index;
    $widget['open_button']['#name'] .= "-$index";
    $widget['open_button']['#media_library_state'] = static::updateMediaState($widget['open_button']['#media_library_state'], $index);

    // Update media_library_update_widget field
    $widget['media_library_update_widget']['#name'] .= "-$index";
    $widget['media_library_update_widget']['#attributes']['data-media-library-widget-update'] .= "-$index";
    $widget['media_library_update_widget']['#ajax']['wrapper'] .= "-$index";
    $widget['media_library_update_widget']['#submit'] = [
      [
        static::class,
        'addItems'
      ]
    ];

    return $widget;
  }

  /**
   * Crate new Media State parameters to target individual fields.
   */
  public static function updateMediaState(MediaLibraryState $current_state, int $index) {
    $current_parameters = $current_state->all();
    $current_parameters['media_library_opener_parameters']['field_widget_id'] .= "-$index";
    unset($current_parameters['hash']);
    $state = new MediaLibraryState($current_parameters);
    return $state;
  }

  /**
   * @inheritdoc
   */
  public static function removeItem(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Get the parents required to find the top-level widget element.
    if (count($triggering_element['#array_parents']) < 4) {
      throw new \LogicException('Expected the remove button to be more than four levels deep in the form. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $parents);

    // Get the field state.
    $path = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $field_state = static::getFieldState($element, $form_state);

    // Get the delta of the item being removed.
    $delta = array_slice($triggering_element['#array_parents'], -2, 1)[0];
    if (isset($values['selection'][$delta])) {
      // Add the weight of the removed item to the field state so we can shift
      // focus to the next/previous item in an easy way.
      $field_state['removed_item_weight'] = $values['selection'][$delta]['weight'];
      $field_state['removed_item_id'] = $triggering_element['#media_id'];
      $values['selection'][$delta] = [];
      $field_state['items'] = $values['selection'];
      static::setFieldState($element, $form_state, $field_state);
    }

    $form_state->setRebuild();
  }

  /**
   * @inheritdoc
   */
  public static function addItems(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $delta = $element['#delta'];

    $field_state = static::getFieldState($element, $form_state);

    $media = static::getNewMediaItems($element, $form_state);
    if (!empty($media)) {
      // Get the weight of the last items and count from there.
      foreach ($media as $media_item) {
        // Any ID can be passed to the widget, so we have to check access.
        if ($media_item->access('view')) {
          $field_state['items'][$delta] = [
            'target_id' => $media_item->id(),
            'weight' => $delta,
          ];
        }
      }
      static::setFieldState($element, $form_state, $field_state);
    }
    $form_state->setRebuild();
  }

  /**
   * @inheritdoc
   */
  protected static function getNewMediaItems(array $element, FormStateInterface $form_state) {
    $index = $element['#delta'];
    $values = $form_state->getUserInput();
    $value = NestedArray::getValue($values, $element['#parents']);
    if (!empty($value['media_library_selection'][$index])) {
      $ids = explode(',', $value['media_library_selection'][$index]);
      $ids = array_filter($ids, 'is_numeric');
      if (!empty($ids)) {
        /** @var \Drupal\media\MediaInterface[] $media */
        return Media::loadMultiple($ids);
      }
    }
    return [];
  }
}
