<?php

namespace Drupal\zero_wrapper_generator\Plugin\Zero\WrapperGenerator;

use Drupal\zero_wrapper_generator\Base\WrapperGeneratorInterface;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;

/**
 * @WrapperGenerator(
 *   id = "base_wrapper_generator",
 * )
 */
class BaseWrapperGenerator implements WrapperGeneratorInterface {

  public function accept(GeneratePackage $package, array $field): bool {
    $allowed_types = [
      'link',
      'list_string',
      'string',
      'text_long',
    ];
    return in_array($field['type'], $allowed_types);
  }

  public function define(GeneratePackage $package, array $field): void {
    $method = 'define__' . $field['type'] . '__' . ($field['multiple'] ? 'multiple' : 'single');
    if (method_exists($this, $method)) {
      $this->$method($package, $field);
    }
  }

  public function generate(GeneratePackage $package, array $field, array $context = []): void {
    $method = 'generate__' . $field['type'] . '__' . ($field['multiple'] ? 'multiple' : 'single');
    if (method_exists($this, $method)) {
      $this->$method($package, $field, $context);
    }
  }

  public function define__list_string__multiple(GeneratePackage $package, array $field) {
    foreach ($field['definition']->getFieldStorageDefinition()->getSetting('allowed_values') as $key => $value) {
      $package->addModifier($key);
    }
  }

  public function generate__link__single(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');
    $yaml = $package->getYamlFile('sdc.info');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getLinkData($field['name']));
    $template->line('<a href="{{ ' . $field['twig'] . '.url }}" {{ ' . $field['twig'] . '.attributes }}>{{ ' . $field['twig'] . '.text }}</a>');
    $yaml->setProperty($field['twig'], [
      'type' => 'string',
    ]);
  }

  public function generate__link__multiple(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');
    $yaml = $package->getYamlFile('sdc.info');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getLinkDatas($field['name']));
    $template->openFor($field['twig'], 'item', function($key) use ($template) {
      $template->line('<a href="{{ ' . $key . '.url }}" {{ ' . $key . '.attributes }}>{{ ' . $key . '.text }}</a>');
    });
    $yaml->setProperty($field['twig'], [
      'type' => 'array',
    ]);
  }

  public function generate__string__single(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getValue($field['name']));
    $template->line($template->v($field['twig']));
  }

  public function generate__string__multiple(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getValues($field['name']));
    $template->openFor($field['twig'], 'item', function ($key) use ($template) {
      $template->line($template->v($key));
    });
  }

  public function generate__text_long__single(GeneratePackage $package, array $field, array $context) {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->body($field['name']));
    $template->line($template->v($field['twig']));
  }

  public function generate__text_long__multiple(GeneratePackage $package, array $field, array $context) {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->bodies($field['name']));
    $template->line($template->v($field['twig']));
  }

  public function generate__list_string__single(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getValue($field['name']));
    if (str_ends_with($field['name'], '_props')) {
      $package->addModifier($field['twig'], $template->s($field['twig']) . '-', ' ~ ' . $field['twig']);
    }
  }

  public function generate__list_string__multiple(GeneratePackage $package, array $field, array $context): void {
    $preprocess = $package->getPHPFile('preprocess');

    foreach ($field['definition']->getFieldStorageDefinition()->getSetting('allowed_values') as $key => $value) {
      $preprocess->assign($context['target'], $key, $preprocess->writer($context['var'])->hasListValue($field['name'], $key));
    }
  }

}
