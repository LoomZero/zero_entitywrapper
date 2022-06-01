<?php

namespace Drupal\zero_entitywrapper\Content;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperExtensionInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentDisplayWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentWrapperInterface;
use Drupal\zero_entitywrapper\Exception\EntityWrapperException;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;

class ContentDisplayWrapper implements BaseWrapperExtensionInterface, ContentDisplayWrapperInterface {

  /** @var ContentWrapper */
  private $wrapper;

  public function setWrapper(BaseWrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

  public function getWrapper(): ?BaseWrapperInterface {
    return $this->wrapper;
  }

  public function cachable(): bool {
    return TRUE;
  }

  /**
   * @param $value
   * @return array
   */
  protected function process($value) {
    return $value;
  }

  public function getDisplaySettings(string $view_mode = NULL, string $field = NULL): ?array {
    $display = WrapperHelper::getViewDisplay($this->getWrapper(), $view_mode ?? $this->getWrapper()->renderContext()->getViewMode(), $view_mode === NULL);
    if ($display === NULL) return NULL;
    $displayFields = $display->getComponents();

    if ($field !== NULL) {
      if (isset($displayFields[$field])) {
        return $displayFields[$field];
      }
      throw new EntityWrapperException('The field ' . $field . ' is unknown.');
    }
    return $displayFields;
  }

  public function as(string $field, string $view_mode = NULL) {
    $view = $this->getDisplaySettings($view_mode, $field);
    return $this->process($this->getWrapper()->entity()->get($field)->view($view));
  }

  protected function doFormatter(ContentWrapperInterface $wrapper, string $field, int $index, string $formatter, array $settings = []) {
    /** @var FieldItemInterface $item */
    $item = $wrapper->metaItem($field, $index);
    if ($item === NULL) return [];

    return $item->view([
      'type' => $formatter,
      'label' => 'hidden',
      'settings' => $settings,
    ]);
  }

  protected function doFormatters(ContentWrapperInterface $wrapper, string $field, string $formatter, array $settings = []) {
    return $wrapper->metaItems($field)->view([
      'type' => $formatter,
      'label' => 'hidden',
      'settings' => $settings,
    ]);
  }

  public function formatter(string $field, int $index, string $formatter, array $settings = []) {
    return $this->process($this->doFormatter($this->wrapper, $field, $index, $formatter, $settings));
  }

  public function formatters(string $field, string $formatter, array $settings = []) {
    return $this->process($this->doFormatters($this->wrapper, $field, $formatter, $settings));
  }

  public function entity(string $field, int $index = 0, string $view_mode = 'full') {
    return $this->formatter($field, $index, 'entity_reference_entity_view', ['view_mode' => $view_mode]);
  }

  public function entities(string $field, string $view_mode = 'full') {
    return $this->formatters($field, 'entity_reference_entity_view', ['view_mode' => $view_mode]);
  }

  public function string(string $field, int $index = 0, bool $linkToEntity = FALSE) {
    return $this->formatter($field, $index, 'string', ['link_to_entity' => $linkToEntity]);
  }

  public function strings(string $field, bool $linkToEntity = FALSE) {
    return $this->formatters($field, 'string', ['link_to_entity' => $linkToEntity]);
  }

  public function body(string $field, int $index = 0, int $trimmed = 0, bool $summary = FALSE) {
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

  public function bodies(string $field, int $trimmed = 0, bool $summary = FALSE) {
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

  public function image(string $field, int $index = 0, string $image_style = '', string $image_link = '') {
    $field = WrapperHelper::getDefaultField($this->wrapper, $field);
    if ($this->wrapper->metaReferenceTargetType($field) === 'media') {
      $media = $this->wrapper->getEntity($field, $index);
      return $this->process($this->doFormatter($media, $media->metaMediaSourceField(), 0, 'image', ['image_style' => $image_style, 'image_link' => $image_link]));
    }
    return $this->formatter($field, $index, 'image', ['image_style' => $image_style, 'image_link' => $image_link]);
  }

  public function images(string $field, string $image_style = '', string $image_link = '') {
    $field = WrapperHelper::getDefaultField($this->wrapper, $field);
    if ($this->wrapper->metaReferenceTargetType($field) === 'media') {
      $medias = $this->wrapper->getEntities($field);
      $output = [];
      foreach ($medias as $media) {
        $output[] = $this->doFormatter($media, $media->metaMediaSourceField(), 0, 'image', ['image_style' => $image_style, 'image_link' => $image_link]);
      }
      return $this->process($output);
    }
    return $this->formatters($field, 'image', ['image_style' => $image_style, 'image_link' => $image_link]);
  }

  public function responsiveImage(string $field, int $index = 0, string $responsive_image_style = '', string $image_link = '') {
    $field = WrapperHelper::getDefaultField($this->wrapper, $field);
    if ($this->wrapper->metaReferenceTargetType($field) === 'media') {
      $media = $this->wrapper->getEntity($field, $index);
      return $this->process($this->doFormatter($media, $media->metaMediaSourceField(), 0, 'responsive_image', ['responsive_image_style' => $responsive_image_style, 'image_link' => $image_link]));
    }
    return $this->formatter($field, $index, 'responsive_image', ['responsive_image_style' => $responsive_image_style, 'image_link' => $image_link]);
  }

  public function responsiveImages(string $field, string $responsive_image_style = '', string $image_link = '') {
    $field = WrapperHelper::getDefaultField($this->wrapper, $field);
    if ($this->wrapper->metaReferenceTargetType($field) === 'media') {
      $medias = $this->wrapper->getEntities($field);
      $output = [];
      foreach ($medias as $media) {
        $output[] = $this->doFormatter($media, $media->metaMediaSourceField(), 0, 'responsive_image', ['responsive_image_style' => $responsive_image_style, 'image_link' => $image_link]);
      }
      return $this->process($output);
    }
    return $this->formatters($field, 'responsive_image', ['responsive_image_style' => $responsive_image_style, 'image_link' => $image_link]);
  }

  public function date(string $field, int $index = 0, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT) {
    if ($type === 'custom') {
      return $this->formatter($field, $index, 'datetime_custom', ['date_format' => $format]);
    } else {
      return $this->formatter($field, $index, 'datetime_default', ['format_type' => $type]);
    }
  }

  public function dates(string $field, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT) {
    if ($type === 'custom') {
      return $this->formatters($field, 'datetime_custom', ['date_format' => $format]);
    } else {
      return $this->formatters($field, 'datetime_default', ['format_type' => $type]);
    }
  }

  public function template(string $template, $context = []) {
    return $this->process(['#type' => 'inline_template', '#template' => $template, '#context' => WrapperHelper::getArray($context, $this->wrapper)]);
  }

  public function component(string $path, $vars = [], string $pattern = NULL) {
    $theme = [];
    if ($pattern) {
      $theme[] = 'zero_component__' . $pattern;
    }
    $theme[] = 'zero_component';

    return $this->process(['#theme' => $theme, '#component_vars' => WrapperHelper::getArray($vars, $this->wrapper), '#component_path' => $path]);
  }

}
