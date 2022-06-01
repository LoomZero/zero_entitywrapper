<?php

namespace Drupal\zero_entitywrapper\Content;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperExtensionInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentDisplayCollectionWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentWrapperInterface;
use Drupal\zero_entitywrapper\Exception\EntityWrapperException;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;
use Drupal\zero_entitywrapper\Render\RenderWrapperCollection;

class ContentDisplayCollectionWrapper extends ContentDisplayWrapper implements BaseWrapperExtensionInterface, ContentDisplayCollectionWrapperInterface {

  protected function process($value) {
    if ($value instanceof RenderWrapperCollection) return $value;
    return new RenderWrapperCollection($value, $this->getWrapper());
  }

}
