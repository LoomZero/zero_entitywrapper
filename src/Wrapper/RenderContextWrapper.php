<?php

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;
use Drupal\zero_entitywrapper\Service\StaticWrapperService;
use Drupal\zero_entitywrapper\Service\EntitywrapperService;

class RenderContextWrapper implements RenderContextWrapperInterface {

  /** @var StaticWrapperService */
  private $staticPageCache;
  /** @var BaseWrapperInterface */
  private $wrapper;
  /** @var EntitywrapperService */
  private $service;

  public function getWrapper(): ?BaseWrapperInterface {
    return $this->wrapper;
  }

  public function setWrapper(BaseWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

  public function cachable(): bool {
    return TRUE;
  }

  private function &renderArray() {
    return $this->getWrapper()->getRenderContext();
  }

  public function getViewMode(): ?string {
    if (isset($this->renderArray()['view_mode'])) {
      return $this->renderArray()['view_mode'];
    } else {
      return NULL;
    }
  }

  private function getService(): EntitywrapperService {
    if ($this->service === NULL) {
      $this->service = Drupal::service('zero_entitywrapper.service');
    }
    return $this->service;
  }

  private function getStaticPageCache(): StaticWrapperService {
    if ($this->staticPageCache === NULL) {
      $this->staticPageCache = Drupal::service('zero.entitywrapper.static');
    }
    return $this->staticPageCache;
  }

  public function addLibrary(string $module, string $library = NULL): void {
    if ($this->renderArray() === NULL) {
      $this->getStaticPageCache()->addLibrary($module, $library);
    } else {
      if ($library === NULL) {
        $this->renderArray()['#attached']['library'][] = $module;
      } else {
        $this->renderArray()['#attached']['library'][] = $module . '/' . $library;
      }
    }
  }

  public function addSettings(string $module, string $setting, $values): void {
    if ($this->renderArray() === NULL) {
      $this->getStaticPageCache()->addSettings($module, $setting, $values);
    } else {
      $this->renderArray()['#attached']['drupalSettings'][$module][$setting] = $values;
    }
  }

  public function setElementSettings(string $namespace, $settings, string $uuid = NULL): string {
    if ($uuid === NULL) {
      /** @var Php $uuid_generator */
      $uuid_generator = Drupal::service('uuid');
      $uuid = $uuid_generator->generate();
    }
    $this->addLibrary('zero_entitywrapper', 'settings');
    $this->addSettings('zero_entitywrapper__' . $uuid, $namespace, $settings);
    return $uuid;
  }

  ### cache methods ###

  public function cacheMaxAge(int $seconds = 0): void {
    if ($this->renderArray() === NULL) {
      $this->getStaticPageCache()->cacheMaxAge($seconds);
    } else {
      if (empty($this->renderArray()['#cache']['max-age']) || $seconds < $this->renderArray()['#cache']['max-age']) {
        $this->renderArray()['#cache']['max-age'] = $seconds;
      }
    }
  }

  ### cache tag methods ###

  public function cacheAddTags(array $tags = []): void {
    if ($this->renderArray() === NULL) {
      $this->getStaticPageCache()->cacheAddTags($tags);
    } else {
      if (empty($this->renderArray()['#cache']['tags'])) {
        $this->getService()->log('cache_tag', 'Add cache tags <code>[' . implode(', ', $tags) . ']</code>');
        $this->renderArray()['#cache']['tags'] = $tags;
      } else {
        $this->getService()->log('cache_tag', 'Merge cache tags <code>[' . implode(', ', $tags) . ']</code> and <code>[' . implode(', ', $this->renderArray()['#cache']['tags']) . ']</code>');
        $this->renderArray()['#cache']['tags'] = Cache::mergeTags($this->renderArray()['#cache']['tags'], $tags);
      }
    }
  }

  public function cacheAddEntity(EntityInterface $entity, bool $forAllEntities = FALSE): void {
    $tags = [
      $entity->getEntityTypeId() . ':' . $entity->id(),
    ];
    if ($forAllEntities) {
      $tags[] = $entity->getEntityTypeId() . '_list';
    }
    $this->cacheAddTags($tags);
  }

  ### cache context methods ###

  public function cacheAddContexts(array $contexts = []): void {
    if ($this->renderArray() === NULL) {
      $this->getStaticPageCache()->cacheAddContexts($contexts);
    } else {
      if (empty($this->renderArray()['#cache']['contexts'])) {
        $this->getService()->log('cache_context', 'Add cache tags <code>[' . implode(', ', $contexts) . ']</code>');
        $this->renderArray()['#cache']['contexts'] = $contexts;
      } else {
        $this->getService()->log('cache_context', 'Merge cache tags <code>[' . implode(', ', $contexts) . ']</code> and <code>[' . implode(', ', $this->renderArray()['#cache']['contexts']) . ']</code>');
        $this->renderArray()['#cache']['contexts'] = Cache::mergeContexts($this->renderArray()['#cache']['contexts'], $contexts);
      }
    }
  }

}
