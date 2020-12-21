<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\Core\Entity\EntityInterface;

interface RenderContextWrapperInterface extends BaseWrapperExtensionInterface {

  public function getViewMode(): ?string;

  public function addLibrary(string $module, string $library = NULL): void;

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

  public function cacheMaxAge(int $seconds = 0): void;

  ### cache tag methods ###

  public function cacheAddTags(array $tags = []): void;

  public function cacheAddEntity(EntityInterface $entity, bool $forAllEntities = FALSE): void;

  ### cache context methods ###

  public function cacheAddContexts(array $contexts = []): void;

}