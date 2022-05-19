<?php

namespace Drupal\zero_entitywrapper\View;

use Drupal\zero_entitywrapper\Base\ViewWrapperInterface;

abstract class ViewHandlerWrapper {

  /** @var ViewWrapperInterface */
  protected $wrapper;
  /** @var string */
  protected $table;
  /** @var string */
  protected $field;

  public function __construct(ViewWrapperInterface $wrapper, string $table, string $field) {
    $this->wrapper = $wrapper;
    $this->table = $table;
    $this->field = $field;
  }

  abstract protected function getHandlerType(): string;

  protected function addHandler(array $options = []): ViewWrapperInterface {
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
