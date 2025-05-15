<?php

namespace Drupal\zero_wrapper_generator\Base;

use Drupal\zero_wrapper_generator\Data\GeneratePackage;

/**
 *
 */
interface WrapperGeneratorInterface {

  public function accept(GeneratePackage $package, array $field): bool;

  public function define(GeneratePackage $package, array $field): void;

  public function generate(GeneratePackage $package, array $field, array $context = []): void;

}
