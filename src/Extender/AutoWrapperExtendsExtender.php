<?php

namespace Drupal\zero_entitywrapper\Extender;

use Drupal\zero_preprocess\Base\PreprocessExtenderInterface;

class AutoWrapperExtendsExtender implements PreprocessExtenderInterface {

  public function weight(): int {
    return -900000;
  }

  public function config(): array {
    return [
      'title' => 'Auto wrapper extend extender',
      'description' => 'Automatically extend $wrapper in all preprocess files.',
    ];
  }

  public function registry(array &$zero, array $item, $name, array $theme_registry) {
    if (empty($zero['preprocess'])) return;

    $file = $item['path'] . '/' . $item['template'] . '.html.twig';
    if (is_file($file)) {
      $content = file_get_contents($file);
      $matches = [];
      preg_match('/^[\s\n]*\{%\s*extends [\'"]([a-z\-\.]*)[\'"]\s*%\}/', $content, $matches);
      if (!empty($matches[1]) && strpos($matches[1], '.html.twig') !== -1) {
        $zero['wrapper']['extends'] = substr($matches[1], 0, strlen($matches[1]) - 10);
      }
    }
  }

  public function preprocess(array &$vars, array $zero, array $template) {
    if (isset($vars['zero']['local']['wrapper']) && isset($zero['wrapper']['extends'])) {
      $vars['zero']['local']['wrapper']->extendPreprocess($zero['wrapper']['extends']);
    }
  }

}