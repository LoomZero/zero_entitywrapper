<?php

namespace Drupal\zero_wrapper_generator\Plugin\Zero\WrapperGenerator;

use Drupal\zero_wrapper_generator\Base\WrapperGeneratorInterface;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;
use Drupal\zero_wrapper_generator\Service\ZeroWrapperGeneratorService;

/**
 * @WrapperGenerator(
 *   id = "entity_reference_wrapper_generator",
 * )
 */
class EntityReferenceWrapperGenerator implements WrapperGeneratorInterface {

  public function accept(GeneratePackage $package, array $field): bool {
    $allowed_types = [
      'entity_reference',
      'entity_reference_revisions',
    ];
    return in_array($field['type'], $allowed_types);
  }

  public function define(GeneratePackage $package, array $field): void {
    $keys = [
      'define',
      $field['type'],
      ($field['multiple'] ? 'multiple' : 'single'),
    ];
    $method = implode('__', $keys);
    if (method_exists($this, $method)) {
      $this->$method($package, $field);
    }
  }

  public function generate(GeneratePackage $package, array $field, array $context = []): void {
    $keys = [
      'generate',
      ($field['type'] === 'entity_reference_revisions' ? 'entity_reference' : $field['type']),
      ($field['multiple'] ? 'multiple' : 'single'),
      ($field['definition']->getSetting('target_type') === 'media' ? 'media' : 'entity'),
    ];
    $method = implode('__', $keys);
    if (method_exists($this, $method)) {
      $this->$method($package, $field, $context);
    }
  }

  public function generate__entity_reference__single__entity(GeneratePackage $package, array $field, array $context): void {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $entity_type = $field['definition']->getSetting('target_type');

    $include = $field['include'] ?? FALSE;
    if ($include) {
      $bundles = $field['definition']->getSetting('handler_settings')['target_bundles'];
      if (count($bundles) === 1) {
        $bundle = array_shift($bundles);
        $fields = $generator->getFieldList($entity_type, $bundle);
        $preprocess->line('');
        if ($field['inline'] ?? FALSE) {
          $preprocess->openInline($context['target'], $field['twig'], $context['var'], $preprocess->writer('')->getEntity($field['name']), function($context) use ($generator, $fields, $package) {
            $generator->generateFields($package, $fields, [
              'target' => '\'@\'',
              'var' => $context,
            ]);
          });
        } else {
          $preprocess->assign('$@', $field['twig'], $preprocess->writer($context['var'])->getEntity($field['name']));
          $preprocess->openArray($context['target'], $field['twig']);
          $generator->generateFields($package, $fields, [
            'target' => '\'@\'',
            'var' => '$' . $field['twig'],
          ]);
          $preprocess->close('];');
        }
        $preprocess->line('');
      } else {
        $include = FALSE;
      }
    }
    if (!$include) {
      $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getEntity($field['name'])->render());
      $template->line($template->v($field['twig']));
    }
  }

  public function generate__entity_reference__multiple__entity(GeneratePackage $package, array $field, array $context): void {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $entity_type = $field['definition']->getSetting('target_type');

    $include = $field['include'] ?? FALSE;
    if ($include) {
      $bundles = $field['definition']->getSetting('handler_settings')['target_bundles'];
      if (count($bundles) === 1) {
        $bundle = array_shift($bundles);
        $fields = $generator->getFieldList($entity_type, $bundle);
        $fields = $generator->define($package, $fields);

        $preprocess->line('');
        $preprocess->assign($context['target'], $field['twig'], '[]');
        $preprocess->open('foreach (' . $preprocess->writer($context['var'])->getEntities($field['name']) . ' as $' . $field['twig_single'] . ') {');
        if ($field['inline'] ?? FALSE) {
          $preprocess->openInline($context['target'] . '[]', $field['twig'], '$' . $field['twig_single'], '', function($context) use ($generator, $fields, $package) {
            $generator->generateFields($package, $fields, [
              'target' => '\'@\'',
              'var' => $context,
            ]);
          });
        } else {
          $preprocess->openArray($context['target'], $field['twig'], TRUE);
          $package->setInlineMode(TRUE);
          $generator->generateFields($package, $fields, [
            'target' => '\'@\'',
            'var' => '$item',
          ]);
          $package->setInlineMode(FALSE);
          $preprocess->close('];');
        }
        $preprocess->close('}');
        $preprocess->line('');
        $template->openFor($field['twig'], 'item', function ($key) use ($template, $field, $fields) {
          $template->el('div', ['class' => [$template->getComponent() . '__' . $template->s($field['twig']) . '-item']]);
          foreach ($fields as $field) {
            $template->line($template->v($key . '.' . $field['twig']));
          }
          $template->close('</div>');
        });
      } else {
        $include = FALSE;
      }
    }
    if (!$include) {
      $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->entities($field['name']));
      $template->line($template->v($field['twig']));
    }
  }

  public function generate__entity_reference__single__media(GeneratePackage $package, array $field, array $context): void {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $responsive_style = $generator->getResponsiveStyle($field);
    $bundles = $field['definition']->getSetting('handler_settings')['target_bundles'];
    if (count($bundles) === 1) {
      if ($generator->isImageBundle(reset($bundles)) && $responsive_style !== NULL) {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->responsiveImage($field['name'], 0, $responsive_style));
        $template->line($template->v($field['twig']));
      } else if ($generator->isVideoBundle(reset($bundles))) {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->entity($field['name']));
        $template->line($template->v($field['twig']));
      } else {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getUrl($field['name']));
        $template->line('<a href="' . $template->v($field['twig']) . '" download>' . $template->v('\'Download\'|t') . '</a>');
      }
    } else {
      $preprocess->assign('$@', $field['twig'], $preprocess->writer($context['var'])->getEntity($field['name']));
      $first = TRUE;
      $template->line($template->v($field['twig']));
      foreach ($bundles as $bundle) {
        if (!$first) {
          $preprocess->indent--;
        }
        $preprocess->open(($first ? '' : '} else ') . 'if ($' . $field['twig'] . '->bundle() === ' . $bundle . ') {');
        $first = FALSE;
        if ($generator->isImageBundle(reset($bundles)) && $responsive_style !== NULL) {
          $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->responsiveImage($field['name'], 0, $responsive_style));
        } else if ($generator->isVideoBundle(reset($bundles))) {
          $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->entity($field['name']));
        } else {
          $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getUrl($field['name']));
        }
      }
      $preprocess->close();
    }
  }

  public function generate__entity_reference__multiple__media(GeneratePackage $package, array $field, array $context): void {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');
    $preprocess = $package->getPHPFile('preprocess');
    $template = $package->getTwigFile('template');

    $responsive_style = $generator->getResponsiveStyle($field);
    $bundles = $field['definition']->getSetting('handler_settings')['target_bundles'];
    if (count($bundles) === 1) {
      if ($generator->isImageBundle(reset($bundles)) && $responsive_style !== NULL) {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->responsiveImages($field['name'], $responsive_style));
      } else if ($generator->isVideoBundle(reset($bundles))) {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->display()->entities($field['name']));
      } else {
        $preprocess->assign($context['target'], $field['twig'], $preprocess->writer($context['var'])->getUrls($field['name']));
      }
    } else {
      $template->compEl($field['twig']);
      $template->openFor($field['twig'], 'item', function ($key) use ($template, $field) {
        $template->el('div', ['class' => [$template->getComponent() . '__' . $template->s($field['twig']) . '-item', $template->getComponent() . '__' . $template->s($field['twig']) . '-item--{{ ' . $key . '.type }}']]);
        $template->line($template->v($key . '.value'));
        $template->close('</div>');
      });
      $template->close('</div>');

      $preprocess->openItems($context['target'], $field['twig'], $context['var'], '->getEntitiesCollection(\'' . $field['name'] . '\', TRUE)', function($context) use ($generator, $preprocess, $field, $bundles, $responsive_style) {
        foreach ($bundles as $bundle) {
          $preprocess->open('if (' . $context . '->bundle() === \'' . $bundle . '\') {');
          if ($generator->isImageBundle($bundle) && $responsive_style !== NULL) {
            $preprocess->openReturnArray([
              ['value', $preprocess->writer($context)->display()->responsiveImage($field['name'], 0, $responsive_style) . ''],
              ['type', $preprocess->writer($context)->bundle() . ''],
            ]);
          } else if ($generator->isVideoBundle($bundle)) {
            $preprocess->openReturnArray([
              ['value', $preprocess->writer($context)->display()->entity($field['name']) . ''],
              ['type', $preprocess->writer($context)->bundle() . ''],
            ]);
          } else {
            $preprocess->openReturnArray([
              ['value', $preprocess->writer($context)->getUrl($field['name']) . ''],
              ['type', $preprocess->writer($context)->bundle() . ''],
            ]);
          }
          $preprocess->close('}');
        }
      });
      $preprocess->line('');
    }
    $template->line($template->v($field['twig']));
  }

}
