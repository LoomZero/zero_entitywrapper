<?php

namespace Drupal\zero_entitywrapper\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @see \Drupal\zero_importer\Service\ZeroImporterPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ZeroPluginBuilder extends Plugin {

  /** @var string */
  public $id;

  /** @var string */
  public $label;

}
