<?php

namespace Drupal\zero_entitywrapper\Service;

use Drupal;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;

class WrapperExtenderPluginManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Zero/EntityWrapper', $namespaces, $module_handler,
      'Drupal\zero_entitywrapper\Base\ZeroPluginBuilderInterface',
      'Drupal\zero_entitywrapper\Annotation\ZeroPluginBuilder');

    $this->alterInfo('zero_plugin_builder_info');
    $this->setCacheBackend($cache_backend, 'zero_plugin_builder_info');
  }

  public function getExtenders(): array {
    $manager = Drupal::service('plugin.manager.wrapper_extender');
    $extender = [];
    forEach($manager->getDefinitions() as $pluginId => $definition) {
      $extender[] = $manager->createInstance($pluginId);
    }
    return $extender;
  }

  public function getExtension(BaseWrapperInterface $parent, string $name, array $args = []) {
    foreach ($this->getExtenders() as $extender) {
      $result = $extender->getExtension($parent, $name, $args);
      if ($result !== NULL) {
        $result->setWrapper($parent);
        return $result;
      }
    }
    return NULL;
  }

}
