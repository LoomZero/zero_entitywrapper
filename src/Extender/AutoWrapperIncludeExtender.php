<?php

namespace Drupal\zero_entitywrapper\Extender;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\zero_entitywrapper\Content\ContentWrapper;
use Drupal\zero_preprocess\Base\PreprocessExtenderInterface;

class AutoWrapperIncludeExtender implements PreprocessExtenderInterface {

  public function weight(): int {
    return -1000000;
  }

  public function config(): array {
    return [
      'title' => 'Auto wrapper include extender',
      'description' => 'Automatically include $wrapper in all preprocess files.',
    ];
  }

  public function registry(array &$zero, array $item, $name, array $theme_registry) {
    if (empty($zero['preprocess'])) return;

    $zero['wrapper']['entity'] = [];
    if (!empty($item['base hook'])) {
      $zero['wrapper']['entity']['wrapper'] = $item['base hook'];
    }

    // add menu_link_content support
    if (strpos($item['template'], 'menu-link-content') === 0) {
      $zero['wrapper']['entity']['wrapper'] = 'menu_link_content';
    }

    // add comment support
    if (strpos($item['template'], 'comment') === 0) {
      $zero['wrapper']['entity']['wrapper'] = 'comment';
      $zero['wrapper']['entity']['commented'] = 'commented_entity';
    }
  }

  public function preprocess(array &$vars, array $zero, array $template) {
    if (empty($zero['wrapper']['entity']) || !count($zero['wrapper']['entity'])) return;

    foreach ($zero['wrapper']['entity'] as $name => $type) {
      $entity = NULL;

      if (isset($vars[$type])) {
        $entity = $vars[$type];
      }

      if ($entity === NULL && isset($vars['elements']['#' . $type])) {
        $entity = $vars['elements']['#' . $type];
      }

      if ($entity !== NULL) {
        if ($entity instanceof ContentEntityBase) {
          $vars['zero']['local'][$name] = ContentWrapper::create($entity);
          $vars['zero']['local'][$name]->setRenderContext($vars);
        }
      }
    }
  }

}
