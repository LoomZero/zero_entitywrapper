<?php

namespace Drupal\zero_entitywrapper\Render;

class RenderItemWrapper {

  private RenderWrapperCollection $collection;
  private mixed $delta;

  public function __construct(RenderWrapperCollection $collection, $delta) {
    $this->collection = $collection;
    $this->delta = $delta;
  }

  public function collection(): RenderWrapperCollection {
    return $this->collection;
  }

  public function delta() {
    return $this->delta;
  }

  public function item($item = NULL) {
    if ($item === NULL) {
      return $this->collection[$this->delta()];
    } else {
      $this->collection[$this->delta()] = $item;
      return $this;
    }
  }

  public function get($key) {
    return $this->item()[$key] ?? NULL;
  }

  public function set($key, $value): self {
    $this->collection()[$this->delta()][$key] = $value;
    return $this;
  }

  /**
   * Set the wrapper for all items
   *
   * @param array $options = [
   *     'none' => TRUE,
   *     'attribute' => 'div',
   *     'class' => ['wrapper', 'wrapper--field'],
   *     'data-src' => '/path/to/src',
   * ]
   * @param bool $merge
   *
   * @return $this
   */
  public function setWrapper(array $options, bool $merge = TRUE): self {
    $this->collection()->setItemInfo($this->delta(), 'wrapper', $options, $merge);
    return $this;
  }

}
