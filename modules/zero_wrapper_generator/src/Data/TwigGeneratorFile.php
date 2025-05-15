<?php

namespace Drupal\zero_wrapper_generator\Data;

use Drupal\Core\Template\Attribute;
use Drupal\migrate\Plugin\migrate\process\Substr;

class TwigGeneratorFile extends GeneratorFileBase {

  public int $indent = 0;
  public array $lines = [];

  public function getComponent(): ?string {
    return $this->s($this->package->get('component'));
  }

  public function line(string ...$lines): self {
    if ($this->package->getInlineMode()) return $this;
    foreach ($lines as $line) {
      $this->lines[] = str_repeat('  ', $this->indent) . $line;
    }
    return $this;
  }

  public function open(string $line = ''): self {
    if ($this->package->getInlineMode()) return $this;
    $this->lines[] = str_repeat('  ', $this->indent) . $line;
    $this->indent++;
    return $this;
  }

  public function openFor(string $key, string $item = 'item', callable $callback = NULL): string {
    $item_key = $key . '_' . $item;
    if (str_ends_with($key, 's')) {
      $item_key = substr($key, 0, -1);
    }
    $this->open('{% for ' . $item_key . ' in ' . $key . ' %}');
    if ($callback !== NULL) {
      $callback($item_key);
      $this->closeFor();
    }
    return $item_key;
  }

  public function closeFor(): self {
    return $this->close('{% endfor %}');
  }

  public function close(string $line = ''): self {
    if ($this->package->getInlineMode()) return $this;
    $this->indent--;
    $this->lines[] = str_repeat('  ', $this->indent) . $line;
    return $this;
  }

  public function s(string $s): string {
    return str_replace('_', '-', $s);
  }

  public function v(string $key): string {
    return '{{ ' . $key . ' }}';
  }

  public function el(string $el, array $attributes = [], callable $callback = NULL): self {
    $this->open("<$el" . new Attribute($attributes) . ">");
    if ($callback) {
      $callback();
      $this->close("</$el>");
    }
    return $this;
  }

  public function assign(string $key, array $attributes = []): self {
    $attributes['class'][] = $this->getComponent() . '__' . $this->s($key);
    $this->el('div', $attributes, function() use ($key) {
      $this->line($this->v($key));
    });
    return $this;
  }

  public function compEl(string $key, array $attributes = []): self {
    $attributes['class'][] = $this->getComponent() . '__' . $this->s($key);
    $this->el('div', $attributes);
    return $this;
  }

  public function addModifier(string $query, string $value = NULL, string $append = ''): self {
    $this->package->addModifier($query, $value, $append);
    return $this;
  }

  public function getModifiers(): array {
    return $this->package->get('modifiers', []);
  }

  public function getContent(): string {
    $lines = [];
    if (count($this->getModifiers())) {
      $lines[] = '{% set classes = [';
      $lines[] = '  \'' . $this->getComponent() . '\',';
      foreach ($this->getModifiers() as $modifier) {
        $lines[] = '  ' . $modifier['query'] . ' ? \'' . $this->getComponent() . '--' . $modifier['value'] . '\'' . $modifier['append'] . ',';
      }
      $lines[] = '%}';
      $lines[] = '';
    }
    if ($this->getComponent()) {
      $lines[] = '<div {{ (attributes|default(create_attribute())).addClasses(classes) }}>';
      array_push($lines, ...array_map(fn($line) => '  ' . $line, $this->lines));
      $lines[] = '</div>';
      return implode("\n", $lines);
    } else {
      return implode("\n", [...$lines, ...$this->lines]);
    }
  }

}
