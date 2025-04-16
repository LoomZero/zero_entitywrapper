<?php

namespace Drupal\zero_wrapper_generator\Data;

class PHPGeneratorFile extends GeneratorFileBase {

  public int $indent = 0;
  public array $lines = [];
  public array $modifiers = [];
  public bool $is_array = FALSE;
  public MethodWriter $writer;

  public function __construct(
    GeneratePackage $package,
    string $id,
  ) {
    parent::__construct($package, $id);
    $this->writer = new MethodWriter();
  }

  public function line(string ...$lines): self {
    foreach ($lines as $line) {
      $this->lines[] = str_repeat('  ', $this->indent) . $line;
    }
    return $this;
  }

  public function assign(string $target, string $key, string $value, bool $close = TRUE): self {
    return $this->line(str_replace('@', $key, $target) . ($this->is_array ? ' => ' : ' = ') . $value . ($close ? ($this->is_array ? ',' : ';') : ''));
  }

  public function open(string $line = ''): self {
    $this->lines[] = str_repeat('  ', $this->indent) . $line;
    $this->indent++;
    return $this;
  }

  public function openArray(string $target, string $key, bool $new_item = FALSE): self {
    $this->open(str_replace('@', $key, $target) . ($new_item ? '[]' : '') . ($this->is_array ? ' => ' : ' = ') . '[');
    $this->is_array = TRUE;
    return $this;
  }

  public function openInline(string $target, string $key, string $context, string $path, callable $callback): self {
    $this->open(str_replace('@', $key, $target) . ($this->is_array ? ' => ' : ' = ') . $context . $path . '->inline(function(' . $context . ') {');
    $this->open('return [');
    $this->is_array = TRUE;
    $callback($context);
    $this->close('];');
    $this->close('});');
    return $this;
  }

  public function openItems(string $target, string $key, string $context, string $path, callable $callback): self {
    $this->open(str_replace('@', $key, $target) . ($this->is_array ? ' => ' : ' = ') . $context . $path . '->map(function($' . $key . ') {');
    $callback('$' . $key);
    $this->close('});');
    return $this;
  }

  public function openReturnArray(array $items): self {
    $this->open('return [');
    foreach ($items as $item) {
      $this->line("'" . $item[0] . '\' => ' . $item[1] . ',');
    }
    $this->close('];');
    return $this;
  }

  public function close(string $line = ''): self {
    $this->is_array = FALSE;
    $this->indent--;
    $this->lines[] = str_repeat('  ', $this->indent) . $line;
    return $this;
  }

  public function getContent(): string {
    return implode("\n", $this->lines);
  }

  /**
   * @param string $context
   * @return MethodWriter|\Drupal\zero_entitywrapper\Base\ContentWrapperInterface
   */
  public function writer(string $context): MethodWriter {
    $this->writer->setContext($context);
    return $this->writer;
  }

  public function addModifier(string $modifier): self {
    $this->modifiers[] = $modifier;
    return $this;
  }

}
