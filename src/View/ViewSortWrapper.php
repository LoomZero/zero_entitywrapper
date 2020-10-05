<?php

namespace Drupal\zero_entitywrapper\View;

class ViewSortWrapper extends ViewHandlerWrapper {

  protected function getHandlerType(): string {
    return 'sort';
  }

  private function addSort(string $order): ViewWrapper {
    return $this->addHandler([
      'order' => $order,
    ]);
  }

  /**
   * Remove other sorts with the same target (table and field).
   *
   * @param bool $table if FALSE all sorts on the same field will be removed
   * @param bool $field if FALSE all sorts on the same table will be removed
   *
   * @return $this
   */
  public function removeOthers(bool $table = TRUE, bool $field = TRUE): ViewSortWrapper {
    if (!$table && !$field) return $this;
    $this->wrapper->removeSort($table ? $this->table : NULL, $field ? $this->field : NULL);
    return $this;
  }

  public function asc(): ViewWrapper {
    return $this->addSort('ASC');
  }

  public function desc(): ViewWrapper {
    return $this->addSort('DESC');
  }

}