<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TypedDataInterface;

interface ItemWrapperInterface {

  public function metaItems(string $field): FieldItemListInterface;

  public function metaItem(string $field, int $index): ?TypedDataInterface;

}