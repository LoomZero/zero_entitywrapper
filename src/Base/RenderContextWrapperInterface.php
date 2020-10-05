<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\zero_entitywrapper\Wrapper\RenderContextWrapper;

interface RenderContextWrapperInterface {

  public function setRenderContext(array &$vars);

  public function renderContext(): RenderContextWrapper;

  public function setParent($parent);

  public function parent();

  public function root();

}