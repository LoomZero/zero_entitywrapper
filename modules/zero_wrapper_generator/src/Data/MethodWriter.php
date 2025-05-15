<?php

namespace Drupal\zero_wrapper_generator\Data;

class MethodWriter {

  private string $context = '';
  private array $calls = [];

  public function setContext(string $context): self {
    $this->context = $context;
    $this->calls = [];
    return $this;
  }

  public function __call(string $name, array $arguments) {
    $this->calls[] = ['method' => $name, 'arguments' => $arguments];
    return $this;
  }

  public function __toString(): string {
    return $this->context . implode('', array_map(function ($call) {
        $args = array_map(fn($arg) => var_export($arg, true), $call['arguments']);
        return '->' . $call['method'] . '(' . implode(', ', $args) . ')';
      }, $this->calls));
  }

}
