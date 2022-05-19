<?php

namespace Drupal\zero_entitywrapper\Content;

use Drupal;
use Drupal\zero_entitywrapper\Service\EntitywrapperService;
use Drupal\zero_preprocess\Collection\ProxyCollection;

class ContentWrapperCollection extends ProxyCollection {

  private $service;
  private $unsafe;

  public function __construct($array = [], $unsafe = FALSE) {
    parent::__construct($array);

    if ($unsafe) {
      $this->unsafe = $unsafe;
      $this->unsafe['info'] = $this->getService()->getBacktracerInfo($this->unsafe['caller'] ?? 0);
    }
  }

  public function getService(): EntitywrapperService {
    if ($this->service === NULL) {
      $this->service = Drupal::service('zero_entitywrapper.service');
    }
    return $this->service;
  }

  public function offsetSet($offset, $value) {
    if ($value instanceof ContentWrapper) {
      throw new \InvalidArgumentException('Added item must be an ContentWrapper');
    }

    parent::offsetSet($offset, $value);
  }

  public function __call($name, $arguments) {
    if ($this->unsafe) {
      $this->getService()->log('deprecation', $this->unsafe['message'], [...($this->unsafe['lines'] ?? []), 'Called in <code>' . $this->unsafe['info']['call'] . '</code>']);
    }
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
