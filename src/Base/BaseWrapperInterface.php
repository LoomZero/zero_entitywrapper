<?php

namespace Drupal\zero_entitywrapper\Base;

interface BaseWrapperInterface {

  public function setRenderContext(array &$vars);

  public function &getRenderContext(): ?array;

  public function renderContext(): RenderContextWrapperInterface;

  public function setParent(BaseWrapperInterface $parent = NULL);

  public function parent(): ?BaseWrapperInterface;

  public function root();

  public function entity();

  public function type(): string;

  public function bundle(): string;

  public function id();

  public function getExtension(string $name): BaseWrapperExtensionInterface;

}