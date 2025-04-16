<?php

namespace Drupal\zero_wrapper_generator\Service;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;
use Drupal\zero_wrapper_generator\Data\GeneratorFile;
use Drupal\zero_wrapper_generator\Form\ZeroWrapperAllGeneratorForm;
use Drupal\zero_wrapper_generator\Form\ZeroWrapperGeneratorForm;

class ZeroWrapperGeneratorService {

  const IMAGE_BUNDLES = [
    'image',
    'photo',
    'picture',
    'graphic',
    'illustration',
  ];

  const VIDEO_BUNDLES = [
    'video',
    'movie',
    'clip',
    'footage',
    'recording',
  ];

  function getGenerateForm(string $entity_type, string $bundle) {
    return \Drupal::formBuilder()->getForm(ZeroWrapperGeneratorForm::class, [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ]);
  }

  function getAllGenerateForm(string $entity_type): array {
    return \Drupal::formBuilder()->getForm(ZeroWrapperAllGeneratorForm::class, [
      'entity_type' => $entity_type,
    ]);
  }

  function getJoinPath(...$parts) {
    return implode(DIRECTORY_SEPARATOR, array_map(fn($p) => trim($p, DIRECTORY_SEPARATOR), $parts));
  }

  public function getRootPath(): string {
    return \Drupal::service('kernel')->getAppRoot();
  }

  public function getThemeTemplatePath(string $theme = NULL): ?string {
    $themeManager = \Drupal::service('theme.manager');
    $pathResolver = \Drupal::service('extension.path.resolver');

    if ($theme === NULL) {
      $theme = $themeManager->getActiveTheme()->getName();
    }

    return $pathResolver->getPath('theme', $theme) . '/templates';
  }

  public function getTemplatePath(string $entity_type, string $bundle, string $view_mode = NULL, string $extension = '.html.twig'): string {
    return "$entity_type/$bundle/$entity_type--$bundle" . ($view_mode === NULL ? '' : '--' . $view_mode) . $extension;
  }

  public function getGeneratorTemplatePath(string $file_type, string $type, string ...$types): ?string {
    $pathResolver = \Drupal::service('extension.path.resolver');
    $base = $pathResolver->getPath('module', 'zero_wrapper_generator') . '/generate';

    $file = $type . '.' . implode('.', $types) . '.' . $file_type;
    if (file_exists('/' . $this->getJoinPath($this->getRootPath(), $base, $file))) {
      return $this->getJoinPath($base, $file);
    }

    $file = $type . '.fallback.' . $file_type;
    if (file_exists('/' . $this->getJoinPath($this->getRootPath(), $base, $file))) {
      return $this->getJoinPath($base, $file);
    }

    return NULL;
  }

  public function getFieldList(string $entity_type, string $bundle): array {
    /** @var EntityFieldManager $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    $fieldDefinitions = $fieldManager->getFieldDefinitions($entity_type, $bundle);
    $fields = [];
    foreach ($fieldDefinitions as $definition) {
      if (!str_starts_with($definition->getName(), 'field_')) continue;

      $fields[$definition->getName()] = $this->prepareField([
        'name' => $definition->getName(),
        'type' => $definition->getType(),
        'multiple' => $definition->getFieldStorageDefinition()->getCardinality() !== 1,
        'definition' => $definition,
      ]);
    }

    return $fields;
  }

  public function prepareField(array $field): array {
    if (isset($field['name'])) {
      $plural = $field['definition']->getFieldStorageDefinition()->getCardinality() !== 1;
      if (empty($field['twig_single'])) {
        if (str_ends_with($field['name'], '_items')) {
          $field['twig_single'] = substr($field['name'], 6, -6);
        } else {
          $field['twig_single'] = substr($field['name'], 6, (str_ends_with($field['name'], 's') && $plural ? -1 : NULL));
        }
      }
      if (empty($field['twig_plural'])) {
        if (str_ends_with($field['name'], '_items')) {
          $field['twig_plural'] = substr($field['name'], 6, -6) . (str_ends_with($field['name'], 's') ? '' : 's');
        } else {
          $field['twig_plural'] = substr($field['name'], 6) . (str_ends_with($field['name'], 's') ? '' : 's');
        }
      }

      if (empty($field['twig'])) {
        if ($field['multiple']) {
          $field['twig'] = $field['twig_plural'];
        } else {
          $field['twig'] = $field['twig_single'];
        }
      }
    }
    return $field;
  }

  public function define(GeneratePackage $package, array $fields = NULL): array {
    /** @var ZeroWrapperGeneratorPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.wrapper_generator');

    $fields ??= $package->getFields();
    foreach ($fields as $index => $field) {
      $fields[$index] = $this->prepareField($field);
      $fields[$index]['plugin'] = $manager->getPluginForField($package, $field);
    }

    $this->defineFields($package, $fields);

    return $fields;
  }

  public function defineFields(GeneratePackage $package, array $fields): self {
    /** @var ZeroWrapperGeneratorPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.wrapper_generator');
    $plugins = $manager->getPlugins();

    foreach ($fields as $field) {
      if (isset($plugins[$field['plugin']])) {
        $plugins[$field['plugin']]->define($package, $field);
      }
    }
    return $this;
  }

  public function generate(GeneratePackage $package): self {
    $preprocess = $package->getPHPFile('preprocess');
    $preprocess->line(
      '<?php',
      '',
      '/**',
      ' * @var \Drupal\zero_entitywrapper\Base\ContentWrapperInterface $wrapper',
      ' * @var array $vars',
      ' */',
      '',
    );
    $fields = $package->getFields();
    $this->generateFields($package, $fields);
    return $this;
  }

  public function generateFields(GeneratePackage $package, array $fields, array $context = NULL): self {
    /** @var ZeroWrapperGeneratorPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.wrapper_generator');
    $plugins = $manager->getPlugins();

    if ($context === NULL) {
      $context = ['target' => '$vars[\'@\']', 'var' => '$wrapper'];
    }
    foreach ($fields as $field) {
      if (isset($plugins[$field['plugin']])) {
        $plugins[$field['plugin']]->generate($package, $field, $context);
      }
    }
    return $this;
  }

  public function getResponsiveStyle(array $field): ?string {
    if (\Drupal::moduleHandler()->moduleExists('responsive_image')) {
      if (($field['responsive_style'] ?? NULL) === NULL) {
        $ids = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->getQuery()->execute();

        return $this->findBestMatch([$field['definition']->getTargetBundle(), $field['name']], $ids);
      } else {
        return $field['responsive_style'];
      }
    }
    return NULL;
  }

  public function isImageBundle(string $bundle): bool {
    foreach (self::IMAGE_BUNDLES as $imageBundle) {
      if (str_contains($bundle, $imageBundle)) return TRUE;
    }
    return FALSE;
  }

  public function isVideoBundle(string $bundle): bool {
    foreach (self::VIDEO_BUNDLES as $videoBundle) {
      if (str_contains($bundle, $videoBundle)) return TRUE;
    }
    return FALSE;
  }

  public function kString(string $string): string {
    return '\'' . $string . '\'';
  }

  public function kMethod(string $target, string $method, string ...$params): string {
    return $target . '->' . $method . '(' . implode(', ', $params) . ');';
  }

  public function kArray(string $target, string ...$keys): string {
    return $target . implode('', array_map(fn($v) => '[\'' . $v . '\']', $keys));
  }

  public function kkey(string $key): string {
    return str_replace('-', '_', $key);
  }

  /**
   * @param string|string[] $input
   * @param array $options
   * @return string
   */
  public function findBestMatch(string|array $input, array $options): string {
    if (!is_array($input)) {
      $inputs = [$input];
    } else {
      $inputs = $input;
    }
    $options = array_values($options);
    $best_match = 0;
    $best_perc = 0;
    foreach ($inputs as $input) {
      foreach ($options as $index => $option) {
        $perc = 0;
        similar_text($input, $option, $perc);
        if ($perc > $best_perc) {
          $best_match = $index;
          $best_perc = $perc;
        }
      }
    }
    return $options[$best_match];
  }

}
