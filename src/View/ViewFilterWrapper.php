<?php

namespace Drupal\zero_entitywrapper\View;

use Drupal\zero_entitywrapper\Base\ViewWrapperInterface;

class ViewFilterWrapper extends ViewHandlerWrapper {

  protected function getHandlerType(): string {
    return 'filter';
  }

  private function addFilter(string $op, $value = '', $min = '', $max = '', $options = NULL): ViewWrapperInterface {
    if ($options === NULL) {
      $options = [
        'min' => $min,
        'max' => $max,
        'value' => $value,
      ];
    }
    return $this->addHandler([
      'operator' => $op,
      'value' => $options,
    ]);
  }

  /**
   * Remove other filters with the same target (table and field).
   *
   * @param bool $table if FALSE all filters on the same field will be removed
   * @param bool $field if FALSE all filters on the same table will be removed
   *
   * @return $this
   */
  public function removeOthers(bool $table = TRUE, bool $field = TRUE): ViewFilterWrapper {
    if (!$table && !$field) return $this;
    $this->wrapper->removeFilter($table ? $this->table : NULL, $field ? $this->field : NULL);
    return $this;
  }

  /**
   * @param string|int $value
   *
   * @return ViewWrapper
   */
  public function equal($value): ViewWrapperInterface {
    return $this->addFilter('=', $value);
  }

  public function notEqual($value): ViewWrapperInterface {
    return $this->addFilter('!=', $value);
  }

  public function min(int $value, bool $equal = FALSE): ViewWrapperInterface {
    $op = '>';
    if ($equal) $op .= '=';
    return $this->addFilter($op, $value);
  }

  public function max(int $value, bool $equal = FALSE): ViewWrapperInterface {
    $op = '<';
    if ($equal) $op .= '=';
    return $this->addFilter($op, $value);
  }

  public function between(int $min, int $max): ViewWrapperInterface {
    return $this->addFilter('between', '', $min, $max);
  }

  public function notBetween(int $min, int $max): ViewWrapperInterface {
    return $this->addFilter('not between', $min, $max);
  }

  public function regex(string $regex): ViewWrapperInterface {
    return $this->addFilter('regular_expression', $regex);
  }

  public function isEmpty(): ViewWrapperInterface {
    return $this->addFilter('empty');
  }

  public function isNotEmpty(): ViewWrapperInterface {
    return $this->addFilter('not empty');
  }

  public function oneOf(array $values) {
    $filter = [];
    foreach ($values as $value) {
      $filter[$value] = $value;
    }
    return $this->addFilter('or', '', '', '', $filter);
  }

  public function allOf(array $values) {
    $filter = [];
    foreach ($values as $value) {
      $filter[$value] = $value;
    }
    return $this->addFilter('and', '', '', '', $filter);
  }

  public function noneOf(array $values) {
    $filter = [];
    foreach ($values as $value) {
      $filter[$value] = $value;
    }
    return $this->addFilter('not', '', '', '', $filter);
  }

}
