<?php

namespace Drupal\zero_entitywrapper\Helper;

use Drupal;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Theme\Registry;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Exception\EntityWrapperException;
use Drupal\zero_preprocess\Service\PreprocessExtenderManager;

class WrapperHelper {

  /**
   * @param callable|array $data
   * @param mixed ...$params
   * @return array
   */
  public static function getArray($data, ...$params): array {
    if (is_array($data)) return $data;
    if (is_callable($data)) {
      return $data(...$params);
    }
    throw new EntityWrapperException('The data can not be converted to array.');
  }

  public static function getStorage(EntityInterface $entity): EntityStorageInterface {
    return Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
  }

  public static function getLanguage($entity = NULL): ?string {
    if (!empty($entity) && $entity instanceof TranslatableInterface) {
      return $entity->language()->getId();
    }
    return NULL;
  }

  public static function toLanguage($entity, string $langcode = NULL) {
    if ($langcode === NULL) {
      $langcode = Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
      return $entity->getTranslation($langcode);
    }
    return $entity;
  }

  public static function applyLanguage($to, $from = NULL) {
    return WrapperHelper::toLanguage($to, WrapperHelper::getLanguage($from));
  }

  public static function toLatestRevision(EntityInterface $entity): EntityInterface {
    $storage = WrapperHelper::getStorage($entity);
    if ($storage instanceof RevisionableStorageInterface) {
      $revision = $storage->getLatestRevisionId($entity->id());
      if (!empty($revision)) {
        return $storage->loadRevision($revision);
      }
    }
    return $entity;
  }

  /**
   * @param BaseWrapperInterface $entity
   * @param string $view_mode
   * @param bool $fallback
   * @return EntityViewDisplayInterface
   */
  public static function getViewDisplay(BaseWrapperInterface $entity, string $view_mode = 'default', $fallback = TRUE): ?EntityViewDisplayInterface {
    /** @var EntityViewDisplayInterface $display */
    $display = Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load(implode('.', [$entity->type(), $entity->bundle(), $view_mode]));

    if ($display === NULL && $fallback && $view_mode !== 'default') {
      $display = Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->load(implode('.', [$entity->type(), $entity->bundle(), 'default']));
    }

    return $display;
  }

  public static function getTemplateInfo(string $template) {
    /** @var Registry $registry */
    $registry = Drupal::service('theme.registry');
    $template = str_replace('-', '_', $template);
    return $registry->get()[$template];
  }

  public static function getPreprocessFile(string $template): ?string {
    $item = WrapperHelper::getTemplateInfo($template);
    if (!empty($item['zero']['preprocess'])) {
      return $item['zero']['preprocess'];
    }
    return NULL;
  }

  public static function extendPreprocess(BaseWrapperInterface $wrapper, string $template) {
    $info = WrapperHelper::getTemplateInfo($template);

    $vars = &$wrapper->getRenderContext();

    /** @var PreprocessExtenderManager $extender */
    $extender = Drupal::service('zero.preprocess.extender');

    $extender->preprocess($vars, $info['zero'], $info);
    $extender->includePreprocess($vars, $info);
  }

}