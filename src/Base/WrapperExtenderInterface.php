<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;

interface WrapperExtenderInterface {

  public function getExtension(BaseWrapper $wrapper, string $name, array $args = []): ?BaseWrapperExtensionInterface;

}