<?php

namespace Drupal\zero_entitywrapper\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\zero_entitywrapper\Service\EntitywrapperService;
use Symfony\Component\HttpFoundation\Request;

class ZeroEntitywrapperForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['zero_entitywrapper.config'];
  }

  public function getFormId() {
    return 'zero_entitywrapper_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var Drupal\zero_entitywrapper\Service\EntitywrapperService $service */
    $service = Drupal::service('zero_entitywrapper.service');
    if (!Drupal::request()->get('ajax_form')) {
      $service->log('deprecation', 'More info for update:', [
        '- don`t use ```ContentWrapper``` as argument type, instead use ```ContentWrapperInterface```',
        '- don`t use ```ContentWrapper->getEntities()->render()``` use instead ```ContentWrapper->getEntitiesCollection()->render()```',
        '- don`t use ```ContentWrapper->view()```use instead ```ContentWrapper->display()```',
        '- don`t use ```ContentWrapper->view()->image()->addItemClass()```use instead ```ContentWrapper->displayCollection()->image()->addItemClass()```',
      ]);
    }

    $form['config_description'] = [
      '#markup' => 'Ist der State gespeichert hat dieser vorrang. Die Einstellung in der settings.php hat vorrang zur config. Für gewöhnlich wird eine Einstellung entweder als Config oder State gespeichert, in der settings.php lässt sich also der Default für einen state bestimmen und eine config lässt sich überschreiben.',
    ];

    $form['logging'] = [
      '#type' => 'details',
      '#title' => 'Logging',
    ];

    $form['logging']['upgrade_status'] = [
      '#type' => 'fieldset',
      '#title' => 'Upgrade status',
    ];

    $form['logging']['upgrade_status']['log_deprecation'] = $this->buildConfigField($service, 'logging.upgrade_status.log_deprecation', 'TRUE', function () use ($service) {
      return [
        '#type' => 'checkbox',
        '#title' => 'Show Deprecation (state)',
        '#description' => 'Show deprecation as message on page.',
        '#default_value' => $service->config('log_deprecation', FALSE),
      ];
    });

    $form['logging']['entity'] = [
      '#type' => 'fieldset',
      '#title' => 'Entity',
    ];

    $form['logging']['entity']['log_reference_invalid'] = $this->buildConfigField($service, 'logging.entity.log_reference_invalid', 'TRUE', function() use ($service) {
      return [
        '#type' => 'checkbox',
        '#title' => 'Show invalid references (state)',
        '#default_value' => $service->config('log_reference_invalid', TRUE),
      ];
    });

    $form['logging']['cache'] = [
      '#type' => 'fieldset',
      '#title' => 'Cache Debug',
    ];

    $form['logging']['cache']['log_cache_tag'] = [
      '#type' => 'checkbox',
      '#title' => 'Log Cache Tag Info (state)',
      '#description' => 'Show cache info as message, shows which cache tag is added via entitywrapper.',
      '#default_value' => $service->config('log_cache_tag', FALSE),
    ];

    $form['logging']['cache']['log_cache_context'] = [
      '#type' => 'checkbox',
      '#title' => 'Log Cache Context Info (state)',
      '#description' => 'Show cache info as message, shows which cache context is added via entitywrapper.',
      '#default_value' => $service->config('log_cache_context', FALSE),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = [
      'log_deprecation' => $form_state->getValue('log_deprecation'),
      'log_reference_invalid' => $form_state->getValue('log_reference_invalid'),
      'log_cache_tag' => $form_state->getValue('log_cache_tag'),
      'log_cache_context' => $form_state->getValue('log_cache_context'),
    ];

    Drupal::state()->set('zero_entitywrapper_config', $state);
    parent::submitForm($form, $form_state);
  }

  public function buildConfigField(EntitywrapperService $service, string $key, string $value, callable $child): array {
    $config_key = explode('.', $key);
    $config_key = array_pop($config_key);
    $build = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="' . $config_key . '-fieldset">',
      '#suffix' => '</div>',
    ];

    $build[$config_key] = $child();
    $build[$config_key . '_description'] = $this->configMarkup($config_key, $value, $service->getConfigState($config_key));
    $build[$config_key . '_buttons'] = [
      'reset_state' => [
        '#type' => 'button',
        '#value' => 'Reset ' . $config_key . ' state',
        '#key' => $key,
        '#kvalue' => $value,
        '#op' => 'state',
        '#ajax' => [
          'callback' => '::resetValue',
          'event' => 'click',
          'wrapper' => $config_key . '-fieldset',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ],
      'reset_config' => [
        '#type' => 'button',
        '#value' => 'Reset ' . $config_key . ' config',
        '#key' => $key,
        '#kvalue' => $value,
        '#op' => 'config',
        '#ajax' => [
          'callback' => '::resetValue',
          'event' => 'click',
          'wrapper' => $config_key . '-fieldset',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ],
    ];
    return $build;
  }

  public function configMarkup(string $key, string $value, array $config) {
    $string = '<div style="padding: 0.2em 0.3em; border-radius: 3px; background: rgba(0, 0, 0, .3); margin-bottom: 1em;">';
    foreach ($config as $k => $v) {
      $string .= $k . ': ' . ($v === NULL ? 'NULL' : $v) . '<br/>';
    }
    $string .= '</div>';
    return [
      '#markup' => Markup::create('<strong>To enable via settings.local.php use:</strong> <code style="display: inline-block; background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">$settings[\'zero_entitywrapper\'][\'' . $key . '\'] = ' . $value . ';</code><br/>' . $string),
    ];
  }

  public function resetValue(array &$form, FormStateInterface $form_state, Request $request) {
    /** @var Drupal\zero_entitywrapper\Service\EntitywrapperService $service */
    $service = Drupal::service('zero_entitywrapper.service');

    $trigger = $form_state->getTriggeringElement();
    $keys = explode('.', $trigger['#key']);

    $service->resetConfig(end($keys), $trigger['#op']);

    $ajaxForm = $form;
    foreach ($keys as $key) {
      $ajaxForm = $ajaxForm[$key];
    }

    $form_field = $ajaxForm[end($keys)];

    return $this->buildConfigField($service, $trigger['#key'], $trigger['#kvalue'], function() use($form_field) {
      return $form_field;
    });
  }

}
