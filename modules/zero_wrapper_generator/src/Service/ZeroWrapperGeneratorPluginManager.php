<?php

namespace Drupal\zero_wrapper_generator\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\zero_wrapper_generator\Base\WrapperGeneratorInterface;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;

class ZeroWrapperGeneratorPluginManager extends DefaultPluginManager {

  private ?array $plugins = NULL;

  /**
   * Constructs a new EventsPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Zero/WrapperGenerator', $namespaces, $module_handler, 'Drupal\zero_wrapper_generator\Base\WrapperGeneratorInterface', 'Drupal\zero_wrapper_generator\Annotation\WrapperGenerator');

    $this->alterInfo('rule_event_info');
    $this->setCacheBackend($cache_backend, 'zero_wrapper_generator_plugins');
  }

  /**
   * @return WrapperGeneratorInterface[]
   */
  public function getPlugins(): array {
    if ($this->plugins === NULL) {
      foreach ($this->getDefinitions() as $definition) {
        $this->plugins[$definition['id']] = $this->createInstance($definition['id']);
      }
    }
    return $this->plugins;
  }

  public function getPluginForField(GeneratePackage $package, array $field): ?string {
    foreach ($this->getPlugins() as $id => $plugin) {
      if ($plugin->accept($package, $field)) {
        return $id;
      }
    }
    return NULL;
  }

}

