<?php

namespace Drupal\zero_entitywrapper\Base;

interface BaseWrapperExtensionInterface {

  public function getWrapper(): ?BaseWrapperInterface;

  public function setWrapper(BaseWrapperInterface $wrapper);

  public function cachable(): bool;

}