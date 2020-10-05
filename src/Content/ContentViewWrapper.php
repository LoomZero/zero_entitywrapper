<?php

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\zero_entitywrapper\Base\ItemWrapperInterface;

class ContentViewWrapper {

  /** @var ItemWrapperInterface */
  private $wrapper;

  public function __construct(ItemWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

  public function formatter(string $field, int $index, string $formatter, array $settings = []): array {
    /** @var FieldItemInterface $item */
    $item = $this->wrapper->metaItem($field, $index);
    if ($item === NULL) return [];

    return $item->view([
      'type' => $formatter,
      'label' => 'hidden',
      'settings' => $settings,
    ]);
  }

  public function formatters(string $field, string $formatter, array $settings = []): array {
    return $this->wrapper->metaItems($field)
      ->view([
        'type' => $formatter,
        'label' => 'hidden',
        'settings' => $settings,
      ]);
  }

  public function entity(string $field, int $index = 0, string $view_mode = 'full'): array {
    return $this->formatter($field, $index, 'entity_reference_entity_view', [
      'view_mode' => $view_mode,
    ]);
  }

  public function entities(string $field, string $view_mode = 'full'): array {
    return $this->formatters($field, 'entity_reference_entity_view', [
      'view_mode' => $view_mode,
    ]);
  }

  public function string(string $field, int $index = 0, bool $linkToEntity = FALSE): array {
    return $this->formatter($field, $index, 'string', [
      'link_to_entity' => $linkToEntity,
    ]);
  }

  public function strings(string $field, bool $linkToEntity = FALSE): array {
    return $this->formatters($field, 'string', [
      'link_to_entity' => $linkToEntity,
    ]);
  }

  public function body(string $field, int $index = 0, int $trimmed = 0, bool $summary = FALSE): array {
    $formatter = 'text_default';
    $settings = [];

    if ($trimmed > 0) {
      $formatter = 'text_trimmed';
      $settings['trim_length'] = $trimmed;
    }

    if ($summary) {
      $formatter = 'text_summary_or_trimmed';
      if ($trimmed === 0) {
        $settings['trim_length'] = 600;
      }
    }

    return $this->formatter($field, $index, $formatter, $settings);
  }

  public function bodies(string $field, int $trimmed = 0, bool $summary = FALSE): array {
    $formatter = 'text_default';
    $settings = [];

    if ($trimmed > 0) {
      $formatter = 'text_trimmed';
      $settings['trim_length'] = $trimmed;
    }

    if ($summary) {
      $formatter = 'text_summary_or_trimmed';
      if ($trimmed === 0) {
        $settings['trim_length'] = 600;
      }
    }

    return $this->formatters($field, $formatter, $settings);
  }

  public function image(string $field, int $index = 0, string $image_style = '', string $image_link = ''): array {
    return $this->formatter($field, $index, 'image', [
      'image_style' => $image_style,
      'image_link' => $image_link,
    ]);
  }

  public function images(string $field, string $image_style = '', string $image_link = ''): array {
    return $this->formatters($field, 'image', [
      'image_style' => $image_style,
      'image_link' => $image_link,
    ]);
  }

  public function imageResponsive(string $field, int $index = 0, string $responsive_image_style = '', string $image_link = ''): array {
    return $this->formatter($field, $index, 'responsive_image', [
      'responsive_image_style' => $responsive_image_style,
      'image_link' => $image_link,
    ]);
  }

  public function imagesResponsive(string $field, string $responsive_image_style = '', string $image_link = ''): array {
    return $this->formatters($field, 'responsive_image', [
      'responsive_image_style' => $responsive_image_style,
      'image_link' => $image_link,
    ]);
  }

  public function date(string $field, int $index = 0, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array {
    if ($type === 'custom') {
      return $this->formatter($field, $index, 'datetime_custom', [
        'date_format' => $format,
      ]);
    } else {
      return $this->formatter($field, $index, 'datetime_default', [
        'format_type' => $type,
      ]);
    }
  }

  public function dates(string $field, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array {
    if ($type === 'custom') {
      return $this->formatters($field, 'datetime_custom', [
        'date_format' => $format,
      ]);
    } else {
      return $this->formatters($field, 'datetime_default', [
        'format_type' => $type,
      ]);
    }
  }

}