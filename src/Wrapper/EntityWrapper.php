<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentWrapperInterface;
use Drupal\zero_entitywrapper\Content\ContentWrapper;
use Drupal\zero_entitywrapper\View\ViewWrapper;
use Symfony\Component\HttpFoundation\Request;

class EntityWrapper extends BaseWrapper {

  public static function createNew(string $entity_type, string $bundle, array $fields = []): EntityWrapper {
    $storage = Drupal::entityTypeManager()->getStorage($entity_type);
    $fields[$storage->getEntityType()->getKey('bundle')] = $bundle;
    $entity = $storage->create($fields);
    return new EntityWrapper($entity);
  }

  public static function createFromRequest(string $entity_type, Request $request = NULL): ?EntityWrapper {
    if ($request === NULL) $request = Drupal::request();
    $entity = $request->get($entity_type);
    if ($entity instanceof EntityInterface) {
      return new EntityWrapper($entity);
    }
    return NULL;
  }

  private function prepareWrapper(BaseWrapperInterface $wrapper) {
    $wrapper->setRenderContext($this->getRenderContext());
    $wrapper->setConfigs($this->configs);
  }

  /**
   * @return EntityInterface
   */
  public function entity() {
    return $this->entity;
  }

  public function wrapContent(): ContentWrapperInterface {
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
