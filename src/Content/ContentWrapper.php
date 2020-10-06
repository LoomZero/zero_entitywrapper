<?php

namespace Drupal\zero_entitywrapper\Content;

use DateInterval;
use DateTime;
use DateTimeZone;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\zero_entitywrapper\Base\ItemWrapperInterface;
use Drupal\zero_entitywrapper\View\ViewWrapper;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;
use Drupal\zero_entitywrapper\Content\ContentViewWrapper;

class ContentWrapper extends BaseWrapper implements ItemWrapperInterface {

  /**
   * @param ContentEntityBase|ContentWrapper $entity
   * @param BaseWrapper|null $parent
   *
   * @return ContentWrapper
   */
  public static function create($entity, BaseWrapper $parent = NULL): ContentWrapper {
    if ($entity instanceof ContentWrapper) return $entity;
    return new ContentWrapper($entity, NULL, $parent);
  }

  /**
   * @param string $entity_type
   * @param int|string $entity_id
   * @param BaseWrapper|null $parent
   *
   * @return ContentWrapper
   */
  public static function load(string $entity_type, $entity_id, BaseWrapper $parent = NULL): ContentWrapper {
    return new ContentWrapper($entity_type, $entity_id, $parent);
  }

  /**
   * @param string[]|ContentEntityBase[] $entities Item can by either a string <strong>[entity_type]:[entity_id]</strong> or a <strong>ContentEntityBase</strong>
   * @param BaseWrapper|null $parent
   *
   * @return ContentWrapper[]
   */
  public static function multi(array $entities, BaseWrapper $parent = NULL): array {
    $wrappers = [];
    foreach ($entities as $delta => $entity) {
      if (is_string($entity)) {
        $split = explode(':', $entity);
        $wrappers[$delta] = ContentWrapper::load($split[0], $split[1], $parent);
      } else {
        $wrappers[$delta] = ContentWrapper::create($entity, $parent);
      }
    }
    return $wrappers;
  }

  /** @var ContentViewWrapper */
  protected $view;

  /**
   * @param ContentEntityBase|string $entity_type
   * @param int|string|null $entity_id
   * @param BaseWrapper|null $parent
   */
  private function __construct($entity_type, $entity_id = NULL, $parent = NULL) {
    parent::__construct($entity_type, $entity_id);
    if ($parent !== NULL) {
      $this->setParent($parent);
      $this->renderContext()->cacheAddEntity($this->entity());
    }
  }

  /**
   * @return ContentEntityBase
   * @noinspection PhpIncompatibleReturnTypeInspection
   */
  public function entity() {
    return $this->entity;
  }

  public function langcode(): ?string {
    return $this->entity->get('langcode')->getString();
  }

  public function url(array $options = [], string $rel = 'canonical'): ?Url {
    return $this->entity()->toUrl($rel, $options);
  }

  public function metaItems(string $field): FieldItemListInterface {
    return $this->entity()->get($field);
  }

  public function metaItem(string $field, int $index): ?TypedDataInterface {
    return $this->entity()->get($field)->get($index);
  }

  protected function metaForeach(callable $callable, string $field, ...$params) {
    $values = [];
    $count = count($this->metaItems($field));
    for ($index = 0; $index < $count; $index++) {
      $value = $callable($field, $index, ...$params);
      if ($value === NULL) continue;
      $values[] = $value;
    }
    return $values;
  }

  public function metaFieldType(string $field): string {
    return $this->metaItems($field)->getFieldDefinition()->getType();
  }

  public function metaFieldSettings(string $field, string $property = NULL) {
    if ($property === NULL) {
      $settings = $this->metaItems($field)->getFieldDefinition()->getSettings();
      $settings += $this->metaItems($field)->getFieldDefinition()->getFieldStorageDefinition()->getSettings();
      return $settings;
    } else {
      $value = $this->metaItems($field)->getFieldDefinition()->getSetting($property);
      if ($value === NULL) {
        $value = $this->metaItems($field)->getFieldDefinition()->getFieldStorageDefinition()->getSetting($property);
      }
      return $value;
    }
  }

  public function metaMainProperty(string $field): string {
    return $this
      ->metaItems($field)
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();
  }

  public function metaListOptions(string $field): ?array {
    return $this->metaFieldSettings($field, 'allowed_values');
  }

  public function metaReferenceTargetType(string $field): ?string {
    return $this->metaFieldSettings($field, 'target_type');
  }

  public function metaReferenceTargetBundles(string $field): ?array {
    return $this->metaFieldSettings($field, 'handler_settings')['target_bundles'];
  }

  public function metaMediaSourceField(): ?string {
    return $this->entity()->getSource()->getConfiguration()['source_field'];
  }

  public function view(): ContentViewWrapper {
    if ($this->view === NULL) {
      $this->view = new ContentViewWrapper($this);
    }
    return $this->view;
  }

  public function getRaw(string $field, int $index = 0, string $property = NULL) {
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    if ($property === NULL) {
      return $item->getValue();
    }
    return $item->getValue()[$property];
  }

  public function getRaws(string $field, string $property = NULL): array {
    return $this->metaForeach([$this, 'getRaw'], $field, $property);
  }

  public function getValue(string $field, int $index = 0) {
    $main = $this->metaMainProperty($field);

    return $this->getRaw($field, $index, $main);
  }

  public function getValues(string $field): array {
    $main = $this->metaMainProperty($field);

    return $this->getRaws($field, $main);
  }

  public function getListValue(string $field, int $index = 0) {
    $allowed_values = $this->metaListOptions($field);
    $value = $this->getValue($field, $index);

    if (empty($allowed_values[$value])) return NULL;

    return $allowed_values[$value];
  }

  public function getListValues(string $field): array {
    $allowed_values = $this->metaListOptions($field);
    $original = $this->getValues($field);

    $values = [];
    foreach ($original as $index => $value) {
      $values[$value] = $allowed_values[$value];
    }

    return $values;
  }

  public function getEntity(string $field, int $index = 0): ?ContentWrapper {
    /** @var FieldItemInterface $item */
    $item = $this->metaItem($field, $index);

    if ($item === NULL || $item->isEmpty()) return NULL;
    /** @var ContentEntityBase $entity */
    $entity = $item->get('entity')->getValue();

    $this->renderContext()->cacheAddEntity($entity);

    return ContentWrapper::create($entity, $this);
  }

  /**
   * @param string $field
   *
   * @return ContentWrapper[]
   */
  public function getEntities(string $field): array {
    return $this->metaForeach([$this, 'getEntity'], $field);
  }

  public function getUrl(string $field, int $index = 0, array $options = []): ?Url {
    $items = $this->metaItems($field);

    if ($items instanceof EntityReferenceFieldItemListInterface) {
      $wrapper = $this->getEntity($field, $index);
      $entity = $wrapper->entity();

      if ($entity instanceof FileInterface) {
        $raw = $this->getRaw($field);
        if (!empty($raw['alt'])) {
          $options['attributes']['alt'] = $raw['alt'];
        }
        if (!empty($raw['title'])) {
          $options['attributes']['title'] = $raw['title'];
        }
        return Url::fromUri(file_create_url($entity->getFileUri()), $options);
      } else if ($wrapper->type() === 'media') {
        return $wrapper->getUrl($wrapper->metaMediaSourceField(), 0, $options);
      } else {
        return $wrapper->url($options);
      }
    }

    /** @var FieldItemInterface */
    $item = $items->get($index);

    // Assume we have a link field
    return Url::fromUri($item->getValue()['uri'], $options);
  }

  public function getUrls(string $field, array $options = []): array {
    return $this->metaForeach([$this, 'getUrl'], $field, $options);
  }

  public function setUrl(string $field, Url $url): ContentWrapper {
    $this->entity()->set($field, [$url]);
    return $this;
  }

  public function getLink(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): ?Link {
    /** @var FieldItemInterface */
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    $values = $item->getValue();
    $url = $this->getUrl($field, $index, $options);
    return Link::fromTextAndUrl($title_overwrite ?? $values['title'] ?? $url->toString(), $url);
  }

  public function getLinks(string $field, array $options = [], string $title_overwrite = NULL): array {
    return $this->metaForeach([$this, 'getLink'], $field, $options, $title_overwrite);
  }

  public function getImageUrl(string $field, int $index = 0, string $image_style = ''): ?Url {
    if ($image_style) {
      $wrapper = $this->getEntity($field, $index);
      /** @var FileInterface $image */
      $image = $wrapper->entity();

      if ($wrapper->type() === 'media') {
        return $wrapper->getImageUrl($wrapper->metaMediaSourceField(), 0, $image_style);
      } else {
        /** @var ImageStyle $style */
        $style = ImageStyle::load($image_style);
        return Url::fromUri($style->buildUrl($image->getFileUri()));
      }
    } else {
      return $this->getUrl($field, $index);
    }
  }

  public function getImageUrls(string $field, string $image_style = ''): array {
    return $this->metaForeach([$this, 'getImageUrl'], $field, $image_style);
  }

  public function getNumber(string $field, int $index = 0, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): ?string {
    $value = $this->getValue($field, $index);
    if ($value === NULL) return NULL;
    return number_format($value, $decimals, $dec_point, $thousands_sep);
  }

  public function getNumbers(string $field, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): array {
    return $this->metaForeach([$this, 'getNumber'], $field, $decimals, $dec_point, $thousands_sep);
  }

  public function getUTCDate(string $field, int $index = 0, string $property = 'value'): ?DateTime {
    $date = $this->getRaw($field, $index, $property);
    if ($date === NULL) return NULL;
    if (!is_numeric($date)) $date = strtotime($date);
    $userTimezone = new DateTimeZone(date_default_timezone_get());
    $gmtTimezone = new DateTimeZone('GMT');
    $gmtDateTime = new DateTime();
    $gmtDateTime->setTimestamp($date);
    $gmtDateTime->setTimezone($gmtTimezone);
    $offset = $userTimezone->getOffset($gmtDateTime);
    $gmtInterval = DateInterval::createFromDateString((string)$offset . 'seconds');
    $gmtDateTime->add($gmtInterval);
    return $gmtDateTime;
  }

  public function getUTCDates(string $field, string $property = 'value'): array {
    return $this->metaForeach([$this, 'getUTCDate'], $field, $property);
  }

  public function getView(string $field, string $explode = ':'): ?ViewWrapper {
    $value = $this->getValue($field);
    [$view, $display] = explode($explode, $value);
    return new ViewWrapper($view, $display);
  }

}