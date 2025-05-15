<?php

namespace Drupal\zero_wrapper_generator\Data;

class GeneratePackage {

  private array $info = [];
  private array $files = [];
  private bool $inline = FALSE;

  public function __construct(array $info = []) {
    $this->info = $info;
  }

  public function get(string $key, $fallback = NULL) {
    return $this->info[$key] ?? $fallback;
  }

  public function set(string $key, $value = NULL): self {
    $this->info[$key] = $value;
    return $this;
  }

  public function setFields(array $fields): self {
    return $this->set('fields', $fields);
  }

  public function getFields(): array {
    return $this->get('fields', []);
  }

  public function addModifier(string $name, string $value = NULL, string $append = ''): self {
    $this->info['modifiers'][] = [
      'query' => $name,
      'value' => ($value === NULL ? $this->k($name) : $value),
      'append' => $append,
    ];
    return $this;
  }

  public function getFile(string $id): ?GeneratorFileBase {
    return $this->files[$id] ?? NULL;
  }

  public function getPHPFile(string $id): PHPGeneratorFile {
    if (!isset($this->files[$id])) {
      $this->files[$id] = new PHPGeneratorFile($this, $id);
    }
    return $this->files[$id];
  }

  public function getTwigFile(string $id): TwigGeneratorFile {
    if (!isset($this->files[$id])) {
      $this->files[$id] = new TwigGeneratorFile($this, $id);
    }
    return $this->files[$id];
  }

  public function getYamlFile(string $id): YamlGeneratorFile {
    if (!isset($this->files[$id])) {
      $this->files[$id] = new YamlGeneratorFile($this, $id);
    }
    return $this->files[$id];
  }

  public function k(string $s): string {
    return str_replace('_', '-', $s);
  }

  public function setInlineMode(bool $inline): self {
    $this->inline = $inline;
    return $this;
  }

  public function getInlineMode(): bool {
    return $this->inline;
  }

}
