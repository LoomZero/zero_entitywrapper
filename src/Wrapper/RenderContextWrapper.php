<?php

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;
use Drupal\zero_entitywrapper\Service\StaticWrapperService;

class RenderContextWrapper implements RenderContextWrapperInterface {

  /** @var array */
  private $render_array;
  /** @var StaticWrapperService */
  private $staticPageCache;
  /** @var BaseWrapperInterface */
  private $wrapper;

  public function __construct(&$render_array = NULL) {
    $this->render_array = &$render_array;
  }

  public function getWrapper(): ?BaseWrapperInterface {
    return $this->wrapper;
  }

  public function setWrapper(BaseWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

  public function getViewMode(): ?string {
    if (isset($this->render_array['view_mode'])) {
      return $this->render_array['view_mode'];
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
    if ($this->render_array === NULL) {
      $this->getStaticPageCache()->addLibrary($module, $library);
    } else {
      if ($library === NULL) {
        $this->render_array['#attached']['library'][] = $module;
      } else {
        $this->render_array['#attached']['library'][] = $module . '/' . $library;
      }
    }
  }

  public function addSettings(string $module, string $setting, $values): void {
    if ($this->render_array === NULL) {
      $this->getStaticPageCache()->addSettings($module, $setting, $values);
    } else {
      $this->render_array['#attached']['drupalSettings'][$module][$setting] = $values;
    }
  }

  ### cache methods ###

  public function cacheMaxAge(int $seconds = 0): void {
    if ($this->render_array === NULL) {
      $this->getStaticPageCache()->cacheMaxAge($seconds);
    } else {
      if (empty($this->render_array['#cache']['max-age']) || $seconds < $this->render_array['#cache']['max-age']) {
        $this->render_array['#cache']['max-age'] = $seconds;
      }
    }
  }

  ### cache tag methods ###

  public function cacheAddTags(array $tags = []): void {
    if ($this->render_array === NULL) {
      $this->getStaticPageCache()->cacheAddTags($tags);
    } else {
      if (empty($this->render_array['#cache']['tags'])) {
        $this->render_array['#cache']['tags'] = $tags;
      } else {
        $this->render_array['#cache']['tags'] = Cache::mergeTags($this->render_array['#cache']['tags'], $tags);
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
    if ($this->render_array === NULL) {
      $this->getStaticPageCache()->cacheAddContexts($contexts);
    } else {
      if (empty($this->render_array['#cache']['contexts'])) {
        $this->render_array['#cache']['contexts'] = $contexts;
      } else {
        $this->render_array['#cache']['contexts'] = Cache::mergeContexts($this->render_array['#cache']['contexts'], $contexts);
      }
    }
  }

}