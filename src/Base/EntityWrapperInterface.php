<?php

namespace Drupal\zero_entitywrapper\Base;

interface EntityWrapperInterface {

  public function entity();

  public function type(): string;

  public function bundle(): string;

  public function id();

}