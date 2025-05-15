<?php

namespace Drupal\zero_wrapper_generator\Data;

abstract class GeneratorFileBase {

  protected ?string $path = NULL;
  protected array $info = [];

  public function __construct(
    protected readonly GeneratePackage $package,
    public readonly string $id
  ) {}

  public function get(string $key, $fallback = NULL) {
    return $this->info[$key] ?? $fallback;
  }

  public function set(string $key, $value = NULL): self {
    $this->info[$key] = $value;
    return $this;
  }

  public function setPath(string $path): self {
    $this->path = $path;
    return $this;
  }

  public function getPath(): ?string {
    return $this->path;
  }

  public function getContent(): string {
    return '';
  }

}
