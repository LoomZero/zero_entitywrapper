<?php

namespace Drupal\zero_entitywrapper\View;

abstract class ViewHandlerWrapper {

  /** @var ViewWrapper */
  protected $wrapper;
  /** @var string */
  protected $table;
  /** @var string */
  protected $field;

  public function __construct(ViewWrapper $wrapper, string $table, string $field) {
    $this->wrapper = $wrapper;
    $this->table = $table;
    $this->field = $field;
  }

  abstract protected function getHandlerType(): string;

  protected function addHandler(array $options = []): ViewWrapper {
    $this->wrapper->executable()->addHandler(
      $this->wrapper->getDisplay(),
      $this->getHandlerType(),
      $this->table,
      $this->field,
      $options
    );
    return $this->wrapper;
  }

}