<?php

namespace Drupal\zero_entitywrapper\Base;

interface WrapperExtenderInterface {

  public function getExtension(BaseWrapperInterface $wrapper, string $name, array $args = []): ?BaseWrapperExtensionInterface;

}
