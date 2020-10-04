<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\Core\Entity\EntityInterface;

interface EntityWrapperInterface {

  public function entity(): EntityInterface;

  public function type(): string;

  public function bundle(): string;

  public function id();

}