<?php

namespace Drupal\zero_wrapper_generator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class WrapperGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

}
