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

  /**
   * Create a new entity for the system as EntityWrapper
   *
   * @param string $entity_type
   * @param string $bundle
   * @param array $fields
   * @return EntityWrapper
   */
  public static function createNew(string $entity_type, string $bundle, array $fields = []): EntityWrapper {
    $storage = Drupal::entityTypeManager()->getStorage($entity_type);
    $fields[$storage->getEntityType()->getKey('bundle')] = $bundle;
    $entity = $storage->create($fields);
    return new EntityWrapper($entity);
  }

  /**
   * Create an EntityWrapper from request object
   *
   * @param string $request_key
   * @param Request|NULL $request
   * @return EntityWrapper|null
   */
  public static function createFromRequest(string $request_key, Request $request = NULL): ?EntityWrapper {
    if ($request === NULL) $request = Drupal::request();
    $entity = $request->get($request_key);
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
   * Get an ContentWrapper from this wrapper. Throw an error if no ContentEntityBase object.
   *
   * @return ContentWrapperInterface
   */
  public function wrapContent(): ContentWrapperInterface {
    $wrapper = ContentWrapper::create($this->entity);
    $this->prepareWrapper($wrapper);
    return $wrapper;
  }

  /**
   * Get an ViewWrapper from this wrapper. Throw an error if no ViewExecutable|ViewEntityInterface object.
   *
   * @param string|NULL $display
   * @return ViewWrapper
   */
  public function wrapView(string $display = NULL): ViewWrapper {
    $wrapper = new ViewWrapper($this->entity, $display);
    $this->prepareWrapper($wrapper);
    return $wrapper;
  }

}
