<?php

namespace Drupal\zero_wrapper_generator\Data;

class GeneratorFile {

  private array $info = [];
  private PHPGeneratorFile $preprocess;
  private TwigGeneratorFile $template;
  private YamlGeneratorFile $yaml;

  public function __construct(array $info = []) {
    $this->info = $info;
    $this->preprocess = new PHPGeneratorFile($this);
    $this->template = new TwigGeneratorFile($this);
    $this->yaml = new YamlGeneratorFile($this);
  }

  public function get(string $key, $fallback = NULL) {
    return $this->info[$key] ?? $fallback;
  }

  public function set(string $key, $value = NULL): self {
    $this->info[$key] = $value;
    return $this;
  }

  public function preprocess(): PHPGeneratorFile {
    return $this->preprocess;
  }

  public function template(): TwigGeneratorFile {
    return $this->template;
  }

  public function yaml(): YamlGeneratorFile {
    return $this->yaml;
  }

  public function getDefaultTheme(): string {
    return \Drupal::config('system.theme')->get('default');
  }

  public function getTheme(): string {
    return $this->get('theme') ?? $this->getDefaultTheme();
  }

  public function getIncludeComponent(): string {
    return '{% include \'' . $this->getTheme() . ':' . $this->get('component') . '\' %}';
  }

}
