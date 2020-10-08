<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Content\ContentWrapper;
use Drupal\zero_entitywrapper\View\ViewWrapper;

class EntityWrapper extends BaseWrapper {

  public static function createNew(string $entity_type, string $bundle, array $fields = []) {
    $storage = Drupal::entityTypeManager()->getStorage($entity_type);
    $fields[$storage->getEntityType()->getKey('bundle')] = $bundle;
    $entity = $storage->create($fields);
    return new EntityWrapper($entity);
  }

  private function prepareWrapper(BaseWrapper $wrapper) {
    $wrapper->setRenderContext($this->getRenderContext());
  }

  /**
   * @return EntityInterface
   */
  public function entity() {
    return $this->entity;
  }

  public function wrapContent(): ContentWrapper {
    $wrapper = ContentWrapper::create($this->entity);
    $this->prepareWrapper($wrapper);
    return $wrapper;
  }

  public function wrapView(string $display = NULL): ViewWrapper {
    $wrapper = new ViewWrapper($this->entity, $display);
    $this->prepareWrapper($wrapper);
    return $wrapper;
  }

}