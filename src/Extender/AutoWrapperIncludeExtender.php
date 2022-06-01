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

    $zero['wrapper']['content'] = [];
    if (!empty($item['base hook'])) {
      $zero['wrapper']['content']['wrapper'] = $item['base hook'];
    }

    // add menu_link_content support
    if (strpos($item['template'], 'menu-link-content') === 0) {
      $zero['wrapper']['content']['wrapper'] = 'menu_link_content';
    }

    // add comment support
    if (strpos($item['template'], 'comment') === 0) {
      $zero['wrapper']['content']['wrapper'] = 'comment';
      $zero['wrapper']['content']['commented'] = 'commented_entity';
    }
  }

  public function preprocess(array &$vars, array $zero, array $template) {
    if (empty($zero['wrapper']['content']) || !count($zero['wrapper']['content'])) return;

    foreach ($zero['wrapper']['content'] as $name => $type) {
      $entity = NULL;

      if (isset($vars[$type])) {
        $entity = $vars[$type];
      }

      if ($entity === NULL && isset($vars['elements']['#' . $type])) {
        $entity = $vars['elements']['#' . $type];
      }

      if ($entity instanceof ContentEntityBase) {
        $vars['zero']['local'][$name] = ContentWrapper::create($entity);
        $vars['zero']['local'][$name]->setRenderContext($vars);
      }
    }
  }

}
