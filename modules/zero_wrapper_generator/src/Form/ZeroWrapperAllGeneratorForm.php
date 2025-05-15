<?php

namespace Drupal\zero_wrapper_generator\Form;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zero_wrapper_generator\Data\GeneratePackage;
use Drupal\zero_wrapper_generator\Service\ZeroWrapperGeneratorService;

class ZeroWrapperAllGeneratorForm extends FormBase {

  public function getFormId() {
    return 'zero_wrapper_all_generator_form';
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
      /** @var Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
      $bundle_info = \Drupal::service('entity_type.bundle.info');

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

      foreach ($display_repository->getViewModeOptions($info['entity_type']) as $view_mode => $label) {
        $view_mode_options[$view_mode] = $label;
      }

      $form['container']['config']['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#options' => $view_mode_options,
        '#default_value' => $this->getDefaultBundle($form_state, $view_mode_options),
      ];

      $form['container']['config']['view_mode_custom'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Custom view mode'),
      ];

      $form['container']['types'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Select types to generate'),
        '#tree' => TRUE,
      ];

      $form['container']['types']['container'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));',
        ],
      ];

      foreach ($bundle_info->getBundleInfo($info['entity_type']) as $bundle => $info) {
        $form['container']['types']['container'][$bundle] = [
          '#type' => 'checkbox',
          '#title' => $info['label'],
          '#default_value' => TRUE,
        ];
      }

      if ($triggering_element['#operation'] === 'create') {
        $this->onCreate($form_state);
      }
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) { }

  public function onGenerate(array &$form, FormStateInterface $form_state) {
    return $form['container'];
  }

  public function onCreate(FormStateInterface $form_state): void {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = Drupal::service('zero_wrapper_generator.service');
    /** @var FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $default_theme = \Drupal::config('system.theme')->get('default');
    $path = \Drupal::service('theme_handler')->getTheme($default_theme)->getPath();
    $realpath = dirname(realpath(DRUPAL_ROOT . '/' . $path));

    $entity_type = $form_state->getBuildInfo()['args'][0]['entity_type'];

    foreach ($form_state->getValue(['types', 'container']) as $bundle => $selected) {
      if (!$selected) continue;

      $fields = $generator->getFieldList($entity_type, $bundle);

      $package = new GeneratePackage([
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'component' => $bundle,
        'theme' => $form_state->getValue('theme'),
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

      $preprocess = $package->getFile('preprocess');
      $preprocess_path = $realpath . '/' . $preprocess->getPath();
      if (!file_exists($preprocess_path)) {
        $dir = dirname($preprocess_path);
        $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
        $file_system->saveData($preprocess->getContent(), $preprocess_path);
        Drupal::messenger()->addMessage($this->t('The preprocess file for "' . $bundle . '" was successfully created.'));
      }

      $template = $package->getFile('template');
      $template_path = $realpath . '/' . $template->getPath();
      if (!file_exists($template_path)) {
        $dir = dirname($template_path);
        $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
        $file_system->saveData($template->getContent(), $template_path);
        Drupal::messenger()->addMessage($this->t('The template file for "' . $bundle . '" was successfully created.'));
      }
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
