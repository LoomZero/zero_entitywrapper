<?php

namespace Drupal\zero_wrapper_generator\Form;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;
use Drupal\zero_wrapper_generator\Service\ZeroWrapperGeneratorService;

class ZeroWrapperGeneratorForm extends FormBase {

  public function getFormId() {
    return 'zero_wrapper_generator_form';
  }

  public function isIncludableField(array $field): bool {
    if ($field['type'] !== 'entity_reference' && $field['type'] !== 'entity_reference_revisions') {
      return FALSE;
    }
    if ($field['definition']->getSetting('target_type') === 'media') {
      return FALSE;
    }
    if (count($field['definition']->getSetting('handler_settings')['target_bundles']) > 1) {
      return FALSE;
    }
    return TRUE;
  }

  public function buildForm(array $form, FormStateInterface $form_state, array $info = NULL) {

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'zero-wrapper-generator__codes',
      ],
    ];

    $form['container']['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity wrapper actions'),
    ];

    $form['container']['actions']['generate'] = [
      '#type' => 'button',
      '#value' => 'Generate',
      '#operation' => 'generate',
      '#ajax' => [
        'wrapper' => 'zero-wrapper-generator__codes',
        'callback' => [$this, 'onGenerate'],
        'event' => 'click',
      ],
    ];

    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      \Drupal::messenger()->deleteByType('error');
      /** @var ZeroWrapperGeneratorService $generator */
      $generator = Drupal::service('zero_wrapper_generator.service');

      $form['container']['actions']['create'] = [
        '#type' => 'button',
        '#value' => 'Create',
        '#attributes' => ['class' => ['button--primary']],
        '#operation' => 'create',
        '#ajax' => [
          'wrapper' => 'zero-wrapper-generator__codes',
          'callback' => [$this, 'onGenerate'],
          'event' => 'click',
        ],
      ];

      $form['container']['config'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configuration'),
      ];

      $theme_handler = \Drupal::service('theme_handler');
      $default_theme = \Drupal::config('system.theme')->get('default');
      $theme_options = [];

      foreach ($theme_handler->listInfo() as $machine_name => $theme_info) {
        $theme_options[$machine_name] = $theme_info->getName();
      }

      $form['container']['config']['theme'] = [
        '#type' => 'select',
        '#title' => $this->t('Theme'),
        '#options' => $theme_options,
        '#default_value' => $default_theme,
        '#ajax' => [
          'wrapper' => 'zero-wrapper-generator__codes',
          'callback' => [$this, 'onGenerate'],
          'event' => 'change',
        ],
      ];

      $display_repository = \Drupal::service('entity_display.repository');

      $view_mode_options = [
        '' => '<none>',
      ];

      foreach ($display_repository->getViewModeOptionsByBundle($info['entity_type'], $info['bundle']) as $view_mode => $label) {
        $view_mode_options[$view_mode] = $label;
      }

      $form['container']['config']['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#options' => $view_mode_options,
        '#default_value' => $this->getDefaultBundle($form_state, $view_mode_options),
        '#ajax' => [
          'wrapper' => 'zero-wrapper-generator__codes',
          'callback' => [$this, 'onGenerate'],
          'event' => 'change',
        ],
      ];

      $form['container']['config']['view_mode_custom'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom view mode'),
        '#ajax' => [
          'wrapper' => 'zero-wrapper-generator__codes',
          'callback' => [$this, 'onGenerate'],
          'event' => 'change',
        ],
      ];

      $fields = $generator->getFieldList($info['entity_type'], $info['bundle']);

      foreach ($fields as $index => $field) {
        if ($this->isIncludableField($field)) {
          $form['container']['config']['inline_' . $field['name']] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Include @field', ['@field' => $field['name']]),
            '#ajax' => [
              'wrapper' => 'zero-wrapper-generator__codes',
              'callback' => [$this, 'onGenerate'],
              'event' => 'change',
            ],
          ];
          if ($form_state->getValue('inline_' . $field['name'])) {
            $fields[$index]['include'] = TRUE;
          }
        }
      }

      $form['container']['files'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Files'),
      ];

      $package = new GeneratePackage([
        'entity_type' => $info['entity_type'],
        'bundle' => $info['bundle'],
        'component' => $info['bundle'],
        'theme' => $form_state->getValue('theme') ?? $default_theme,
        'view_mode' => $form_state->getValue('view_mode_custom') ?: $form_state->getValue('view_mode') ?: NULL,
      ]);
      $package->setFields($generator->define($package, $fields));
      $generator->generate($package);

      $preprocess = $package->getFile('preprocess');
      $template = $package->getTwigFile('template');

      $view_mode_append = '';
      if ($package->get('view_mode')) {
        $view_mode_append = '--' . $package->get('view_mode');
      }
      $preprocess->setPath($template->s($package->get('theme') . '/templates/' . $package->get('entity_type') . '/' . $package->get('bundle') . '/' . $package->get('entity_type') . '--' . $package->get('bundle') . $view_mode_append . '.preprocess.php'));
      $template->setPath($template->s($package->get('theme') . '/templates/' . $package->get('entity_type') . '/' . $package->get('bundle') . '/' . $package->get('entity_type') . '--' . $package->get('bundle') . $view_mode_append . '.html.twig'));

      $form['container']['files']['preprocess'] = [
        '#type' => 'inline_template',
        '#template' => '<template class="zero-generator__template" data-file-type="preprocess" data-file-path="{{ path }}">{{ content }}</template>',
        '#context' => [
          'content' => $package->getFile('preprocess')->getContent(),
          'path' => $preprocess->getPath(),
        ],
        '#allowed_tags' => ['template'],
      ];
      $form['container']['files']['twig'] = [
        '#type' => 'inline_template',
        '#template' => '<template class="zero-generator__template" data-file-type="template" data-file-path="{{ path }}">{{ content }}</template>',
        '#context' => [
          'content' => $package->getFile('template')->getContent(),
          'path' => $template->getPath(),
        ],
      ];

      if ($triggering_element['#operation'] === 'create') {
        $this->onCreate($package);
      }

      /*
      $form['container']['twig_includer'] = [
        '#type' => 'inline_template',
        '#template' => '<template class="zero-generator__template" data-file-type="template" data-file-path="{{ path }}">{{ content }}</template>',
        '#context' => [
          'content' => $file->getIncludeComponent(),
          'path' => $file->getTheme() . '/templates/' . $file->get('entity_type') . '/' . $file->get('bundle') . '/' . $file->get('entity_type') . '--' . $file->get('bundle') . '.html.twig',
        ],
      ];
      $form['container']['sdn_info'] = [
        '#type' => 'inline_template',
        '#template' => '<template class="zero-generator__template" data-file-type="info" data-file-path="{{ path }}">{{ content }}</template>',
        '#context' => [
          'content' => $file->yaml()->getContent(),
          'path' => $file->getTheme() . '/templates/' . $file->get('entity_type') . '/' . $file->get('bundle') . '/' . $file->get('entity_type') . '--' . $file->get('bundle') . '.info.yml',
        ],
      ];
      */
    }

    $form['#attached']['library'][] = 'zero_wrapper_generator/zero_generator';

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) { }

  public function onGenerate(array &$form, FormStateInterface $form_state) {
    return $form['container'];
  }

  public function onCreate(GeneratePackage $package): void {
    $file_system = \Drupal::service('file_system');

    $path = \Drupal::service('theme_handler')->getTheme($package->get('theme'))->getPath();
    $realpath = realpath(DRUPAL_ROOT . '/' . $path);

    $preprocess = $package->getFile('preprocess');
    $preprocess_path = dirname($realpath) . '/' . $preprocess->getPath();
    if (!file_exists($preprocess_path)) {
      $dir = dirname($preprocess_path);
      $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      $file_system->saveData($preprocess->getContent(), $preprocess_path);
      Drupal::messenger()->addMessage($this->t('The preprocess file was successfully created.'));
    } else {
      Drupal::messenger()->addWarning($this->t('The preprocess file already exists.'));
    }

    $template = $package->getFile('template');
    $template_path = dirname($realpath) . '/' . $template->getPath();
    if (!file_exists($template_path)) {
      $dir = dirname($template_path);
      $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      $file_system->saveData($template->getContent(), $template_path);
      Drupal::messenger()->addMessage($this->t('The template file was successfully created.'));
    } else {
      Drupal::messenger()->addWarning($this->t('The template file already exists.'));
    }
  }

  public function getDefaultBundle(FormStateInterface $form_state, array $options): string {
    if ($form_state->getBuildInfo()['args'][0]['entity_type'] === 'node' && isset($options['full'])) {
      return 'full';
    } else {
      return '';
    }
  }

}
