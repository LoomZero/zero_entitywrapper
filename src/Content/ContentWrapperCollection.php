<?php

namespace Drupal\zero_entitywrapper\Content;

use Drupal\zero_preprocess\Collection\ProxyCollection;

class ContentWrapperCollection extends ProxyCollection {

  public function offsetSet($offset, $value) {
    if ($value instanceof ContentWrapper) {
      throw new \InvalidArgumentException('Added item must be an ContentWrapper');
    }

    parent::offsetSet($offset, $value);
  }

  public function __call($name, $arguments) {
    $results = [];
    $is_array = FALSE;
    foreach ($this as $item) {
      if (method_exists($item, $name)) {
        $value = $item->{$name}(...$arguments);
        if ($value instanceof ProxyCollection) {
          foreach ($value as $value_item) {
            $results[] = $value_item;
          }
        } else {
          if (is_array($value)) $is_array = TRUE;
          $results[] = $value;
        }
      }
    }
    if ($is_array) {
      return $results;
    } else {
      return new self($results);
    }
  }

}
