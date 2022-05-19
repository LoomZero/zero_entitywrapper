<?php

namespace Drupal\zero_entitywrapper\Service;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;

class StaticWrapperService {

  /** @var EntitywrapperService */
  private $service;

  private $libraries;
  private $settings;

  private $cacheMaxAge;
  private $cacheTags;
  private $cacheContext;

  private $pageAttached = FALSE;

  private function getService(): EntitywrapperService {
    if ($this->service === NULL) {
      $this->service = Drupal::service('zero_entitywrapper.service');
    }
    return $this->service;
  }

  public function addLibrary(string $module, string $library = NULL): void {
    if ($this->libraries === NULL) $this->libraries = [];
    if ($library === NULL) {
      $this->libraries[] = $module;
    } else {
      $this->libraries[] = $module . '/' . $library;
    }
  }

  public function addSettings(string $module, string $setting, $values): void {
    if ($this->settings === NULL) $this->settings = [];
    $this->settings[$module][$setting] = $values;
  }

  ### cache methods ###

  public function cacheMaxAge(int $seconds = 0): void {
    if ($this->cacheMaxAge === NULL) $this->cacheMaxAge = Cache::PERMANENT;
    $this->cacheMaxAge = Cache::mergeMaxAges($this->cacheMaxAge, $seconds);
  }

  ### cache tag methods ###

  public function cacheAddTags(array $tags = []): void {
    if ($this->cacheTags === NULL) $this->cacheTags = [];
    $this->getService()->log('cache_tag', 'Merge cache tags (static) <code>[' . implode(', ', $tags) . ']</code> and <code>[' . implode(', ', $this->cacheTags) . ']</code>');
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $tags);
  }

  public function cacheAddEntity(BaseWrapperInterface $entity, bool $forAllBundleEntities, bool $forAllTypeEntities = FALSE): void {
    $tags = [
      $entity->type() . ':' . $entity->id(),
    ];
    if ($forAllTypeEntities) {
      $tags[] = $entity->type() . '_list';
    } else if ($forAllBundleEntities) {
      $tags[] = $entity->type() . ':' . $entity->bundle();
    }
    $this->cacheAddTags($tags);
  }

  ### cache context methods ###

  public function cacheAddContexts(array $contexts = []): void {
    if ($this->cacheContext === NULL) $this->cacheContext = [];
    $this->getService()->log('cache_context', 'Merge cache contexts (static) <code>[' . implode(', ', $contexts) . ']</code> and <code>[' . implode(', ', $this->cacheContext) . ']</code>');
    $this->cacheContext = Cache::mergeContexts($this->cacheContext, $contexts);
  }

  public function apply(array &$render_array) {
    $this->applyAttachments($render_array);
    $this->applyCache($render_array);
  }

  public function applyAttachments(array &$attachments) {
    if ($this->libraries !== NULL) {
      foreach ($this->libraries as $library) {
        $attachments['#attached']['library'][] = $library;
      }
    }
    $this->libraries = NULL;

    if ($this->settings !== NULL) {
      foreach ($this->settings as $setting => $values) {
        $attachments['#attached']['drupalSettings'][$setting] = $values;
      }
    }
    $this->settings = NULL;
  }

  public function applyCache(array &$render_array, bool $isPageAttachment = FALSE) {
    if ($this->cacheMaxAge !== NULL) {
      if (empty($render_array['#cache']['max-age'])) {
        $render_array['#cache']['max-age'] = Cache::PERMANENT;
      }
      $render_array['#cache']['max-age'] = Cache::mergeMaxAges($render_array['#cache']['max-age'], $this->cacheMaxAge);
    }

    if ($this->cacheTags !== NULL) {
      if (empty($render_array['#cache']['tags'])) {
        $render_array['#cache']['tags'] = [];
      }
      $render_array['#cache']['tags'] = Cache::mergeTags($render_array['#cache']['tags'], $this->cacheTags);
    }

    if ($this->cacheContext !== NULL) {
      if (empty($render_array['#cache']['contexts'])) {
        $render_array['#cache']['contexts'] = [];
      }
      $render_array['#cache']['contexts'] = Cache::mergeTags($render_array['#cache']['contexts'], $this->cacheContext);
    }

    if ($isPageAttachment) $this->pageAttached = TRUE;
    $this->cacheMaxAge = NULL;
    $this->cacheTags = NULL;
    $this->cacheContext = NULL;
  }

}
