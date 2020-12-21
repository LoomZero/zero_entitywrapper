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

    if (!empty($item['base hook'])) {
      $zero['wrapper']['entity'] = $item['base hook'];
    }
  }

  public function preprocess(array &$vars, array $zero, array $template) {
    if (empty($zero['wrapper']['entity'])) return;

    $entity = NULL;
    if (isset($vars[$zero['wrapper']['entity']])) {
      $entity = NULL;
    }
    if ($entity === NULL && isset($vars['elements']['#' . $zero['wrapper']['entity']])) {
      $entity = $vars['elements']['#' . $zero['wrapper']['entity']];
    }

    if ($entity !== NULL) {
      if ($entity instanceof ContentEntityBase) {
        $vars['zero']['local']['wrapper'] = ContentWrapper::create($entity);
        $vars['zero']['local']['wrapper']->setRenderContext($vars);
      }
    }
  }

}