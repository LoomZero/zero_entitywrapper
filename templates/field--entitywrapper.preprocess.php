<?php

/**
 * @var array $vars
 */

use Drupal\Component\Utility\Html;

foreach ($vars['items'] as $delta => $item) {
  /** @var \Drupal\Core\Template\Attribute $attribute */
  $attribute = $vars['items'][$delta]['attributes'];

  $attributes = [];
  if (isset($vars['element']['#_preprocess']['items']['_all']['wrapper'])) {
    $attributes = array_merge_recursive($attributes, $vars['element']['#_preprocess']['items']['_all']['wrapper']);
  }
  if (isset($vars['element']['#_preprocess']['items'][$delta]['wrapper'])) {
    unset($attributes['none']);
    $attributes = array_merge_recursive($attributes, $vars['element']['#_preprocess']['items'][$delta]['wrapper']);
  }

  if (empty($attributes['none']) && count($attributes)) {
    $vars['items'][$delta]['wrapper_attribute'] = $attributes['attribute'] ?? 'div';
    unset($attributes['attribute']);
    if (!empty($attributes['class'])) {
      foreach ($attributes['class'] as $index => $class) {
        $attributes['class'][$index] = Html::getClass($class);
      }
    }
    foreach ($attributes as $attr => $value) {
      $attribute->setAttribute($attr, $value);
    }
  } else {
    $vars['items'][$delta]['clean'] = TRUE;
  }
}

if (isset($vars['element']['#_preprocess']['wrapper'])) {
  $wrapper = $vars['element']['#_preprocess']['wrapper'];
  if (empty($wrapper['none'])) {
    $vars['wrapper_attribute'] = $wrapper['attribute'] ?? 'div';
    unset($wrapper['attribute']);
    $vars['attributes'] = array_merge_recursive($vars['attributes'], $wrapper);
  } else {
    $vars['clean'] = TRUE;
  }
} else {
  $vars['clean'] = TRUE;
}
