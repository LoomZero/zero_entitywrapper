<?php

namespace Drupal\zero_wrapper_generator\Command;

use Drupal\zero_wrapper_generator\Service\ZeroWrapperGeneratorService;
use Drush\Commands\DrushCommands;

/**
 *
 */
class ZeroWrapperGeneratorCommand extends DrushCommands {

  /**
   * @command zero_wrapper_generate:generate
   * @option force
   * @usage drush zero_wrapper_generate:generate node page
   */
  public function generate(string $entity_type, string $bundle = 'all', array $options = ['force' => FALSE]) {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');

    $this->io()->writeln($generator->getRootPath());

    $path = $generator->getThemeTemplatePath();
    $this->io()->writeln($path);

    $this->io()->writeln($generator->getTemplatePath($entity_type, $bundle, 'default'));
    $this->io()->writeln($generator->getGeneratorTemplatePath('php', 'entity-preprocess', $entity_type) ?? 'NONE');
    $fields = $generator->getFieldList($entity_type, $bundle);

    $this->io()->writeln($generator->getPreprocessFile($fields)->getContent());
  }

}
