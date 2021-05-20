<?php

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;
use Drupal\zero_entitywrapper\Service\StaticWrapperService;

class RenderContextWrapper implements RenderContextWrapperInterface {

  /** @var StaticWrapperService */
  private $staticPageCache;
  /** @var BaseWrapperInterface */
  private $wrapper;

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
        $this->renderArray()['#cache']['tags'] = $tags;
      } else {
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
        $this->renderArray()['#cache']['contexts'] = $contexts;
      } else {
        $this->renderArray()['#cache']['contexts'] = Cache::mergeContexts($this->renderArray()['#cache']['contexts'], $contexts);
      }
    }
  }

}
