<?php

namespace Drupal\zero_entitywrapper\Content;

class ContentEachWrapper {

  /** @var ContentWrapper[] */
  protected $wrappers;

  public function __construct(array $wrappers) {
    $this->wrappers = $wrappers;
  }

  public function __call($method, $arguments) {
    if ($method === 'each') {
      $values = [];
      foreach ($this->wrappers as $wrapper) {
        $values = array_merge($values, $wrapper->getEntities($arguments[0]));
      }
      return new ContentEachWrapper($values);
    }
    $values = [];
    foreach ($this->wrappers as $wrapper) {
      $value = $wrapper->$method(...$arguments);
      if (is_array($value) && !count($value) || $value === NULL) continue;
      $values[] = $value;
    }
    return $values;
  }

}