<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Content;

use ArrayObject;

class ContentWrapperCollection extends ArrayObject {

  /**
   * ContentWrapperCollection constructor.
   *
   * @param ContentWrapper[] $array
   */
  public function __construct(array $array = []) {
    parent::__construct($array);
  }

  public function offsetSet($offset, $value) {
    if ($value instanceof ContentWrapper) {
      throw new \InvalidArgumentException("Must be an BaseWrapper");
    }

    parent::offsetSet($offset, $value);
  }

  public function __call($name, $arguments) {
    $results = [];
    /** @var ContentWrapper $item */
    foreach ($this as $item) {
      if (method_exists($item, $name)) {
        $results[$item->id()] = $item->{$name}(...$arguments);
      }
    }
    return $results;
  }

}