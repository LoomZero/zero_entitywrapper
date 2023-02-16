<?php

namespace Drupal\zero_entitywrapper\Service;

use Drupal;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;

class EntitywrapperService {

  public function hasLogPermission() {
    return Drupal::currentUser()->hasPermission('see zero_entitywrapper info');
  }

  public function config($key, $fallback = NULL, string $type = NULL) {
    if ($type === NULL || $type === 'state') {
      $state = Drupal::state()->get('zero_entitywrapper_config', []);
      if ($type === 'state') return $state[$key] ?? $fallback;
      if (isset($state[$key])) return $state[$key];
    }
    if ($type === NULL || $type === 'settings') {
      $settings = Settings::get('zero_entitywrapper');
      if ($type === 'settings') return $settings[$key] ?? $fallback;
      if (isset($settings[$key])) return $settings[$key];
    }
    if ($type === NULL || $type === 'config') {
      $config = Drupal::config('zero_entitywrapper.config');
      $value = $config->get($key);
      if (isset($value)) return $value;
    }
    return $fallback;
  }

  public function getConfigState($key) {
    return [
      'state' => $this->config($key, NULL, 'state'),
      'settings' => $this->config($key, NULL, 'settings'),
      'config' => $this->config($key, NULL, 'config'),
    ];
  }

  public function resetConfig($key, string $type = NULL) {
    if ($type === NULL || $type === 'state') {
      $state = Drupal::state()->get('zero_entitywrapper_config', []);
      unset($state[$key]);
      Drupal::state()->set('zero_entitywrapper_config', $state);
    }
    if ($type === NULL || $type === 'config') {
      $config = Drupal::configFactory()->getEditable('zero_entitywrapper.config');
      $config->clear($key);
      $config->save();
    }
  }

  public function getBacktracerInfo(int $call = 0) {
    $info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $call + 3)[$call + 2];
    $info['local_file'] = substr($info['file'], strlen(DRUPAL_ROOT));
    $info['call'] = $info['local_file'] . ':' . $info['line'];
    return $info;
  }

  public function logDeprecation() {
    if (!$this->hasLogPermission() || !$this->config('log_deprecation', FALSE)) return;
    $info = $this->getBacktracerInfo();
    $reflection = new \ReflectionClass($info['class']);
    $comment = $reflection->getMethod($info['function'])->getDocComment();
    $matches = [];
    preg_match('/@deprecated(?:(?!(@|\*\/)).)*/s', $comment, $matches);
    foreach ($matches as $match) {
      $lines = explode("\n", $match);
      foreach ($lines as $index => $line) {
        $lines[$index] = str_replace('<code>', '<code style="background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">', trim(trim($line), '* '));
      }
      $lines = array_filter($lines);
      $warning = '';
      foreach ($lines as $index => $line) {
        if ($index === 0) {
          $warning .= '<strong>[DEPRECATION] Zero EntityWrapper - method <code style="background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">' . $info['class'] . $info['type'] . $info['function'] . '()</code><br /><span style="margin-left: 2em;">' . $lines[0] . '</span></strong>';
        } else {
          $warning .= '<span style="margin-left: 2em;">' . $line . '</span>';
        }
        $warning .= '<br />';
      }
      $warning .= '<span style="margin-left: 2em;"><i>in <code style="background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">' . $info['call'] . '</i></code></span><br />';
      $warning .= '<span style="margin-left: 2em;">' . Link::createFromRoute('Config ⇒', 'zero_entitywrapper.config.form')->toString() . '</span>';
      Drupal::messenger()->addWarning(Markup::create($warning));
    }
  }

  public function log(string $key, string $title, array $messages = [], $type = MessengerInterface::TYPE_STATUS, $fallback = FALSE) {
    if (!$this->hasLogPermission() || !$this->config('log_' . $key, $fallback)) return;

    $message = '<strong>[' . strtoupper(str_replace('_', ' ', $key)) . '] ' . $title . '</strong></br>';
    $list = [];
    foreach ($messages as $line) {
      if (str_starts_with($line, '- ')) {
        $list[] = substr($line, 2);
      } else {
        if (count($list)) {
          $message .= '<ul><li>' . implode('</li><li>', $list) . '</li></ul>';
          $list = [];
        }
        $message .= '<span style="margin-left: 2em;">' . $line . '</span></br>';
      }
    }
    if (count($list)) {
      $message .= '<ul><li>' . implode('</li><li>', $list) . '</li></ul>';
    }

    $message .= '<span style="margin-left: 2em;">' . Link::createFromRoute('Config ⇒', 'zero_entitywrapper.config.form')->toString() . '</span>';
    $message = str_replace('<code>', '<code style="background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">', $message);

    $count = 0;
    $message = preg_replace_callback('/```/', function($match) use (&$count) {
      if ($count++ % 2 === 0) {
        return '<code style="background: rgba(0, 0, 0, .3); padding: 0.2em 0.3em; border-radius: 3px;">';
      } else {
        return '</code>';
      }
    }, $message);

    Drupal::messenger()->addMessage(Markup::create($message), $type);
  }

}

