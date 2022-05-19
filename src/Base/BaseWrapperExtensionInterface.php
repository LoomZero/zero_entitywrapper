<?php

namespace Drupal\zero_entitywrapper\Base;

interface BaseWrapperExtensionInterface {

  /**
   * The wrapper that is extended
   *
   * @return BaseWrapperInterface|null
   */
  public function getWrapper(): ?BaseWrapperInterface;

  /**
   * Set the wrapper that is extended
   *
   * @param BaseWrapperInterface $wrapper
   * @return mixed
   */
  public function setWrapper(BaseWrapperInterface $wrapper);

  /**
   * If this extension is cachable or will be created for every call
   *
   * @return bool
   */
  public function cachable(): bool;

}
