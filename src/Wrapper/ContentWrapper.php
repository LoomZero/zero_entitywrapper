<?php

namespace Drupal\zero_entitywrapper\Wrapper;

use DateInterval;
use DateTime;
use DateTimeZone;
use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\zero_entitywrapper\Base\ItemWrapperInterface;

class ContentWrapper implements ItemWrapperInterface, Drupal\zero_entitywrapper\Base\EntityWrapperInterface {

  /**
   * @param ContentEntityBase|ContentWrapper $entity
   * @param ContentWrapper|null $parent
   *
   * @return ContentWrapper
   */
  public static function create($entity, ContentWrapper $parent = NULL): ContentWrapper {
    if ($entity instanceof ContentWrapper) return $entity;
    return new ContentWrapper($entity, NULL, $parent);
  }

  /**
   * @param string $entity_type
   * @param int|string $entity_id
   * @param ContentWrapper|null $parent
   *
   * @return ContentWrapper
   */
  public static function load(string $entity_type, $entity_id, ContentWrapper $parent = NULL): ContentWrapper {
    return new ContentWrapper($entity_type, $entity_id, $parent);
  }

  /**
   * @param string[]|ContentEntityBase[] $entities Item can by either a string <strong>[entity_type]:[entity_id]</strong> or a <strong>ContentEntityBase</strong>
   * @param ContentWrapper|null $parent
   *
   * @return ContentWrapper[]
   */
  public static function multi(array $entities, ContentWrapper $parent = NULL): array {
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

  /** @var ContentEntityBase */
  protected $entity;
  /** @var ContentWrapper */
  protected $parent;
  /** @var FieldViewWrapper */
  protected $view;
  /** @var RenderContextWrapper */
  protected $renderContext;
  /** @var array */
  protected $vars;

  /**
   * @param ContentEntityBase|string $entity_type
   * @param int|string $entity_id
   * @param ContentWrapper|null $parent
   */
  private function __construct($entity_type, $entity_id, $parent = NULL) {
    if ($entity_type instanceof ContentEntityBase) {
      $this->entity = $entity_type;
    } else {
      Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
    $this->parent = $parent;
  }

  public function setRenderContext(array &$vars): ContentWrapper {
    $this->vars = &$vars;
    return $this;
  }

  public function metaItems(string $field): FieldItemListInterface {
    return $this->entity->get($field);
  }

  public function metaItem(string $field, int $index): ?TypedDataInterface {
    return $this->entity->get($field)->get($index);
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

  public function metaMainProperty(string $field): string {
    return $this
      ->metaItems($field)
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();
  }

  public function metaListOptions(string $field): array {
    return $this
      ->metaItems($field)
      ->getFieldDefinition()
      ->getSetting('allowed_values');
  }

  public function view(): FieldViewWrapper {
    if ($this->view === NULL) {
      $this->view = new FieldViewWrapper($this);
    }
    return $this->view;
  }

  public function renderContext(): RenderContextWrapper {
    $root = $this->root();
    if ($root->renderContext === NULL) {
      $root->renderContext = new RenderContextWrapper($root->vars);
    }
    return $root->renderContext;
  }

  public function entity(): EntityInterface {
    return $this->entity;
  }

  public function type(): string {
    return $this->entity->getEntityTypeId();
  }

  public function bundle(): string {
    return $this->entity->bundle();
  }

  public function id() {
    return $this->entity->id();
  }

  public function parent(): ?ContentWrapper {
    return $this->parent;
  }

  public function root(): ContentWrapper {
    $root = $this;
    while ($root->parent !== NULL) {
      $root = $root->parent;
    }
    return $root;
  }

  public function getRaw(string $field, int $index = 0, string $propertie = NULL) {
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    if ($propertie === NULL) {
      return $item->getValue();
    }
    return $item->getValue()[$propertie];
  }

  public function getRaws(string $field, string $propertie = NULL): array {
    return $this->metaForeach([$this, 'getRaw'], $field, $propertie);
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
      $entity = $this->getEntity($field, $index)->entity();

      if ($entity instanceof FileInterface) {
        return Url::fromUri(file_create_url($entity->getFileUri()), $options);
      } else {
        return $entity->toUrl('canonical', $options);
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

  public function getLink(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): ?Link {
    /** @var FieldItemInterface */
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    $values = $item->getValue();
    return Link::fromTextAndUrl($title_overwrite ?? $values['title'], Url::fromUri($values['uri'], $options));
  }

  public function getLinks(string $field, array $options = [], string $title_overwrite = NULL): array {
    return $this->metaForeach([$this, 'getLink'], $field, $options, $title_overwrite);
  }

  public function getImageUrl(string $field, int $index = 0, string $image_style = ''): ?Url {
    if ($image_style) {
      /** @var ImageStyle $style */
      $style = ImageStyle::load($image_style);

      /** @var FileInterface $image */
      $image = $this->getEntity($field, $index)->entity();
      return Url::fromUri($style->buildUrl($image->getFileUri()));
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

}