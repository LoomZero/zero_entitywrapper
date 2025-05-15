<?php

namespace Drupal\zero_wrapper_generator\Data;

use Drupal\Core\Serialization\Yaml;

class YamlGeneratorFile extends GeneratorFileBase {

  protected array $data = [];

  public function setProperty() {

  }

  public function getContent(): string {
    return Yaml::encode($this->data);
  }

}
