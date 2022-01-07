<?php

namespace Drupal\zero_entitywrapper\Extender;

use Drupal\zero_entitywrapper\Base\BaseWrapperExtensionInterface;
use Drupal\zero_entitywrapper\Base\WrapperExtenderInterface;
use Drupal\zero_entitywrapper\Content\ContentDisplayCollectionWrapper;
use Drupal\zero_entitywrapper\Content\ContentDisplayWrapper;
use Drupal\zero_entitywrapper\Content\ContentViewWrapper;
use Drupal\zero_entitywrapper\Content\ContentWrapper;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;
use Drupal\zero_entitywrapper\Wrapper\RenderContextWrapper;

class DefaultWrapperExtender implements WrapperExtenderInterface {

  public function getExtension(BaseWrapper $wrapper, string $name, array $args = []): ?BaseWrapperExtensionInterface {
    switch ($name) {
      case 'view':
        if ($wrapper instanceof ContentWrapper) {
          return new ContentViewWrapper();
        }
        break;
      case 'display':
        if ($wrapper instanceof ContentWrapper) {
          return new ContentDisplayWrapper();
        }
        break;
      case 'display.collection':
        if ($wrapper instanceof ContentWrapper) {
          return new ContentDisplayCollectionWrapper();
        }
        break;
      case 'render_context':
        if ($wrapper->parent() === NULL) {
          return new RenderContextWrapper();
        } else {
          return $wrapper->root()->getExtension('render_context');
        }
    }
    return NULL;
  }

}
