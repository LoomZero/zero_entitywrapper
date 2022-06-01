<?php

namespace Drupal\zero_entitywrapper\Service;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\WrapperExtenderInterface;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;

class WrapperExtenderManager {

  /** @var ClassResolverInterface */
  private $classResolver;
  /** @var string[] */
  private $ids;
  /** @var WrapperExtenderInterface[] */
  private $extender;

  public function __construct(ClassResolverInterface $class_resolver, array $ids) {
    $this->classResolver = $class_resolver;
    $this->ids = $ids;
  }

  /**
   * @return WrapperExtenderInterface[]
   */
  public function getExtenders(): array {
    if ($this->extender === NULL) {
      $this->extender = [];
      foreach ($this->ids as $id) {
        $this->extender[$id] = $this->classResolver->getInstanceFromDefinition($id);
      }
    }
    return $this->extender;
  }

  public function getExtension(BaseWrapperInterface $parent, string $name, array $args = []) {
    foreach ($this->getExtenders() as $extender) {
      $extender = $extender->getExtension($parent, $name, $args);
      if ($extender !== NULL) {
        $extender->setWrapper($parent);
        return $extender;
      }
    }
    return NULL;
  }

}
