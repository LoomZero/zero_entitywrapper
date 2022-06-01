<?php

namespace Drupal\zero_entitywrapper\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    $service->log('deprecation', 'More info for update:', [
      '- don`t use ```ContentWrapper``` as argument type, instead use ```ContentWrapperInterface```',
      '- don`t use ```ContentWrapper->getEntities()->render()``` use instead ```ContentWrapper->getEntitiesCollection()->render()```',
      '- don`t use ```ContentWrapper->view()```use instead ```ContentWrapper->display()```',
      '- don`t use ```ContentWrapper->view()->image()->addItemClass()```use instead ```ContentWrapper->displayCollection()->image()->addItemClass()```',
    ]);

    $form['logging'] = [
      '#type' => 'details',
      '#title' => 'Logging',
    ];

    $form['logging']['upgrade_status'] = [
      '#type' => 'fieldset',
      '#title' => 'Upgrade status',
      '#description' => 'Show message about the possibilities to upgrade.',
    ];

    $form['logging']['upgrade_status']['log_deprecation'] = [
      '#type' => 'checkbox',
      '#title' => 'Show Deprecation (state)',
      '#description' => 'Show deprecation as message on page',
      '#default_value' => $service->config('log_deprecation', FALSE),
    ];

    $form['logging']['entity'] = [
      '#type' => 'fieldset',
      '#title' => 'Entity',
    ];

    $form['logging']['entity']['log_reference_invalid'] = [
      '#type' => 'checkbox',
      '#title' => 'Show invalid references (state)',
      '#default_value' => $service->config('log_reference_invalid', TRUE),
    ];

    $form['logging']['cache'] = [
      '#type' => 'fieldset',
      '#title' => 'Cache',
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

}
