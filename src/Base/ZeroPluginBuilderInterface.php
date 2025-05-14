<?php

namespace Drupal\zero_entitywrapper\Base;

interface ZeroPluginBuilderInterface {
  public function getExtension(BaseWrapperInterface $wrapper, string $name, array $args = []): ?BaseWrapperExtensionInterface;
}
