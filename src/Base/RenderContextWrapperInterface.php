<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\Core\Entity\EntityInterface;

interface RenderContextWrapperInterface extends BaseWrapperExtensionInterface {

  /**
   * Get the view mode of this render context, if givin in `$vars['view_mode']`
   *
   * @return string|null
   */
  public function getViewMode(): ?string;

  /**
   * Add a js/css library to render array
   *
   * @param string $module
   * @param string|NULL $library
   * @return void
   */
  public function addLibrary(string $module, string $library = NULL): void;

  /**
   * Add js settings to render array
   *
   * @param string $module
   * @param string $setting
   * @param $values
   * @return void
   */
  public function addSettings(string $module, string $setting, $values): void;

  /**
   * Add settings for scripts to a element.
   * Add data-zero-uuid="{{ uuid }}" to the twig component.
   * Get data via Drupal.zero.Settings.get($('.selector'));
   *
   * @param string $name
   * @param $settings
   * @param null|string $uuid
   *
   * @return string
   */
  public function setElementSettings(string $name, $settings, string $uuid = NULL): string;

  ### cache methods ###

  /**
   * Set the cache max age (`$vars['#cache']['max-age']`)
   *
   * @param int $seconds
   * @return void
   */
  public function cacheMaxAge(int $seconds = 0): void;

  ### cache tag methods ###

  /**
   * Add a cache tag (`$vars['#cache']['tags']`)
   *
   * @see \Drupal\Core\Cache\Cache::mergeTags()
   *
   * @param array $tags
   * @return void
   */
  public function cacheAddTags(array $tags = []): void;

  /**
   * Add a cache tag for a entity (`$vars['#cache']['tags']`) - `node:1` or `node_list`
   *
   * @param EntityInterface $entity
   * @param bool $forAllEntities
   * @return void
   */
  public function cacheAddEntity(EntityInterface $entity, bool $forAllEntities = FALSE): void;

  ### cache context methods ###

  /**
   * Add a cache context (`$vars['#cache']['contexts']`)
   *
   * @see \Drupal\Core\Cache\Cache::mergeContexts()
   *
   * @param array $contexts
   * @return void
   */
  public function cacheAddContexts(array $contexts = []): void;

}
