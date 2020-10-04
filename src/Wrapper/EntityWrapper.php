<?php

namespace Drupal\loom_preprocess\Data;

use Closure;
use DateInterval;
use DateTime;
use DateTimeZone;
use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\loom_preprocess\Exception\PreprocessException;
use Drupal\loom_preprocess\Helper\EntityWrapperHelper;

class EntityWrapper implements EntityWrapperInterface {

  ### properties ###

  /**
   * @var array - the relevant entity render array
   */
  public $vars = NULL;

  /**
   * @var ContentEntityInterface - the relvent entity
   */
  protected $entity = NULL;

  /**
   * @var EntityWrapper|null null - the parent from this wrapper
   */
  protected $parent = NULL;

  /**
   * @var array
   *   Config array
   */
  protected $config = [];

  ### create static methods ###

  /**
   * Create a new root entity view wrapper.
   *
   * @param array $vars
   * @param ContentEntityInterface|ContentEntityInterface[] $entity
   * @param bool $addCache
   * @return EntityWrapper|EntityWrapper[]
   */
  public static function createRoot(array &$vars, $entity, bool $addCache = TRUE) {
    // try to find the entity in the render array
    if (is_string($entity)) {
      if (!empty($vars['elements']['#' . $entity]) && $vars['elements']['#' . $entity] instanceof ContentEntityInterface) {
        $entity = $vars['elements']['#' . $entity];
      } else {
        return (new EntityWrapper())->createRenderBridge($vars);
      }
    }

    $wrappers = is_array($entity) ? $entity : [$entity];

    foreach ($wrappers as $index => $value) {
      $wrappers[$index] = (new EntityWrapper($value))->createRenderBridge($vars);
      if ($addCache) $wrappers[$index]->cacheAddEntity($value);
    }

    return is_array($entity) ? $wrappers : reset($wrappers);
  }

  /**
   * Create a new entity view wrapper related to the parent wrapper.
   *
   * @param EntityWrapperInterface $parent
   * @param ContentEntityInterface|ContentEntityInterface[] $entity
   * @return EntityWrapperInterface|EntityWrapperInterface[]
   */
  public static function createForEntity(EntityWrapperInterface $parent, $entity) {
    $wrappers = is_array($entity) ? $entity : [$entity];

    foreach ($wrappers as $index => $value) {
      $wrappers[$index] = EntityWrapperHelper::toLanguage($value, $parent->toLangcode());
      $wrappers[$index] = (new EntityWrapper($wrappers[$index], $parent))->cacheAddEntity($wrappers[$index]);
    }

    return is_array($entity) ? $wrappers : reset($wrappers);
  }

  /**
   * Load a entity and create the wrapper.
   *
   * @see EntityStorageInterface::load()
   * @see EntityWrapper::createForEntity()
   *
   * @param EntityWrapperInterface $parent
   * @param string $storage
   * @param $id
   * @return EntityWrapperInterface|EntityWrapperInterface[]
   */
  public static function loadForEntity(EntityWrapperInterface $parent, string $storage, $id) {
    /** @var ContentEntityInterface $entity */
    $entity = Drupal::entityTypeManager()->getStorage($storage)->load($id);
    return EntityWrapper::createForEntity($parent, $entity);
  }

  ### init methods ###

  /**
   * @param ContentEntityInterface|NULL $entity - the relevant entity
   * @param EntityWrapper|null $parent
   */
  private function __construct(ContentEntityInterface $entity = NULL, EntityWrapper $parent = NULL) {
    $this->entity = $entity;
    $this->parent = $parent;

    if ($parent !== NULL) {
      $this->config = $parent->config();
    }
  }

  /**
   * Set the render array - only for the root wrapper!
   *
   * @param array $vars - render array
   * @return $this
   */
  public function createRenderBridge(array &$vars): EntityWrapperInterface {
    $this->vars = &$vars;
    return $this;
  }

  ### magic methods ###

  public function __toString() {
    return EntityWrapperHelper::getEntityDescription($this, FALSE) . '(' . EntityWrapperHelper::getEntityDescription($this) . ')';
  }

  ### helper methods ###

  /**
   * @param string $field
   * @return int[]
   */
  protected function getFor(string $field): array {
    $len = $this->getLength($field);
    if ($len === 0) return [];
    return range(0, $len - 1);
  }

  protected function getItems(string $field): FieldItemListInterface {
    return $this->entity->get($field);
  }

  protected function getItem(string $field, int $index): ?TypedDataInterface {
    return $this->entity->get($field)->get($index);
  }

  protected function getDummy(): DummyEntityWrapper {
    return new DummyEntityWrapper($this);
  }

  protected function metaMainProperty(string $field): string {
    return $this
      ->getItems($field)
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();
  }

  protected function checkViewAccess(EntityInterface $entity): bool {
    if ($this->configGet(static::CONFIG_IGNORE_ENTITY_VIEW_ACCESS)) return TRUE;
    return $entity->access('view');
  }

  protected function transformEntity(EntityInterface $entity = NULL): ?EntityInterface {
    if ($entity === NULL) return NULL;
    $revision = NULL;

    if (!$this->configGet(static::CONFIG_IGNORE_LANGUAGE_SUPPORT)) {
      $entity = EntityWrapperHelper::toLanguage($entity, EntityWrapperHelper::getLanguage($this->entity));
    }

    if ($this->configGet(static::CONFIG_LOAD_LATEST_REVISION)) {
      $revision = EntityWrapperHelper::toLatestRevision($entity);
    }

    if (!$this->configGet(static::CONFIG_IGNORE_CACHE_SUPPORT)) {
      $this->cacheAddEntity($entity);
    }

    if ($revision !== NULL && $this->checkViewAccess($revision)) return $revision;

    if ($this->checkViewAccess($entity)) {
      return $entity;
    } else {
      return NULL;
    }
  }

  ### entity related methods ###

  public function toRoot(): EntityWrapperInterface {
    if ($this->parent === NULL) {
      return $this;
    }
    return $this->parent->toRoot();
  }

  public function toEntity(): ?ContentEntityInterface {
    return $this->entity;
  }

  public function toParent(): ?EntityWrapperInterface {
    return $this->parent;
  }

  public function toType(): ?string {
    return $this->entity->getEntityTypeId();
  }

  public function toBundle(): ?string {
    return $this->entity->bundle();
  }

  public function toId() {
    return $this->entity->id();
  }

  public function toLangcode(): ?string {
    return $this->entity->get('langcode')->getString();
  }

  public function toUrl(array $options = [], string $rel = 'canonical'): ?Url {
    return $this->toEntity()->toUrl($rel, $options);
  }

  public function toView(string $view_mode = 'full'): array {
    return Drupal::entityTypeManager()
      ->getViewBuilder($this->toType())
      ->view($this->entity, $view_mode);
  }

  public function toViewMode(): ?string {
    if (isset($this->vars['view_mode'])) {
      return $this->vars['view_mode'];
    } else {
      return NULL;
    }
  }

  ### config methods ###

  public function config(): array {
    return $this->config;
  }

  public function configGet(string $config) {
    if (!isset($this->config[$config])) return NULL;
    return $this->config[$config];
  }

  public function configIgnoreLanguageSupport(bool $ignore = TRUE): EntityWrapperInterface {
    $this->config[static::CONFIG_IGNORE_LANGUAGE_SUPPORT] = $ignore;
    return $this;
  }

  public function configIgnoreCacheSupport(bool $ignore = TRUE): EntityWrapperInterface {
    $this->config[static::CONFIG_IGNORE_CACHE_SUPPORT] = $ignore;
    return $this;
  }

  public function configIgnoreEntityViewAccess(bool $ignore = TRUE): EntityWrapperInterface {
    $this->config[static::CONFIG_IGNORE_ENTITY_VIEW_ACCESS] = $ignore;
    return $this;
  }

  public function configLoadLatestRevision(bool $latest = TRUE): EntityWrapperInterface {
    $this->config[static::CONFIG_LOAD_LATEST_REVISION] = $latest;
    return $this;
  }

  ### field helper methods ###

  public function hasField(string $field): bool {
    return $this->entity->hasField($field);
  }

  public function isEmpty(string $field): bool {
    return $this->getItems($field)->isEmpty();
  }

  public function hasValue(string $field, int $index = 0): bool {
    return $this->hasField($field) && $this->getLength($field) > $index && $this->getItems($field)->get($index) !== NULL;
  }

  public function getIndex(string $field, $value, string $property = NULL): int {
    if ($property === NULL) $property = $this->metaMainProperty($field);

    /** @var FieldItemInterface $item */
    foreach ($this->getItems($field) as $index => $item) {
      $item_value = $item->get($property);

      if ($item_value->getValue() == $value) {
        return $index;
      }
    }
    return -1;
  }

  public function getLength(string $field): int {
    return count($this->getItems($field));
  }

  public function each(string $field, Closure $function): array {
    $result = [];

    foreach ($this->getItems($field) as $index => $item) {
      $result[] = $function($item->getValue(), $index, $item, $this);
    }
    return $result;
  }

  ### getter ###

  public function getRaw(string $field, int $index = 0, string $propertie = NULL) {
    $item = $this->getItem($field, $index);
    if ($item === NULL) return NULL;

    if ($propertie === NULL) {
      return $item->getValue();
    }
    return $item->getValue()[$propertie];
  }

  public function getRaws(string $field, string $propertie = NULL): array {
    $properties = [];
    foreach ($this->getFor($field) as $index) {
      $properties[] = $this->getRaw($field, $index, $propertie);
    }
    return $properties;
  }

  public function getValue(string $field, int $index = 0) {
    $main = $this->metaMainProperty($field);

    return $this->getRaw($field, $index, $main);
  }

  public function getValues(string $field): array {
    $items = [];
    $main = $this->metaMainProperty($field);

    foreach ($this->getFor($field) as $index) {
      $items[] = $this->getRaw($field, $index, $main);
    }
    return $items;
  }

  public function getListValue(string $field, int $index = 0) {
    $allowed_values = $this
      ->getItems($field)
      ->getFieldDefinition()
      ->getSetting('allowed_values');
    $value = $this->getValue($field, $index);

    if (empty($allowed_values[$value])) return NULL;

    return $allowed_values[$value];
  }

  public function getListValues(string $field): array {
    $allowed_values = $this
      ->getItems($field)
      ->getFieldDefinition()
      ->getSetting('allowed_values');
    $original = $this->getValues($field);

    $values = [];
    foreach ($original as $index => $value) {
      $values[$value] = $allowed_values[$value];
    }

    return $values;
  }

  public function getEntity(string $field, int $index = 0): ?EntityWrapperInterface {
    /** @var FieldItemInterface $item */
    $item = $this->getItem($field, $index);

    if ($item === NULL || $item->isEmpty()) return $this->getDummy();
    /** @var EntityInterface $entity */
    $entity = $item->get('entity')->getValue();

    if ($entity === NULL || !$this->checkViewAccess($entity)) return $this->getDummy();

    $entity = $this->transformEntity($entity);

    if ($entity === NULL) {
      return $this->getDummy();
    }

    return new EntityWrapper($entity, $this);
  }

  public function getEntities(string $field): array {
    $entities = [];
    /** @var FieldItemInterface $item */
    foreach ($this->getItems($field) as $index => $item) {
      $entity = $this->getEntity($field, $index);

      // filter all orphaned references
      if (!($entity instanceof DummyEntityWrapper)) {
        $entities[$item->getName()] = $entity;
      }
    }
    return $entities;
  }

  public function getUrl(string $field, int $index = 0, array $options = []): ?Url {
    if (!$this->hasValue($field, $index)) return NULL;
    $items = $this->getItems($field);

    if ($items instanceof EntityReferenceFieldItemListInterface) {
      $entity = $this->getEntity($field, $index)->toEntity();

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
    $urls = [];
    foreach ($this->getFor($field) as $index) {
      $url = $this->getUrl($field, $index, $options);
      if ($url !== NULL) {
        $urls[] = $url;
      }
    }
    return $urls;
  }

  public function getLink(string $field, int $index = 0, array $options = []): ?Link {
    /** @var FieldItemInterface */
    $item = $this->getItem($field, $index);
    if ($item === NULL) return NULL;

    $values = $item->getValue();
    return Link::fromTextAndUrl($values['title'], Url::fromUri($values['uri'], $options));
  }

  public function getLinks(string $field, array $options = []): array {
    $links = [];
    foreach ($this->getFor($field) as $index) {
      $link = $this->getLink($field, $index, $options);
      if ($link !== NULL) {
        $links[] = $link;
      }
    }
    return $links;
  }

  public function getImageUrl(string $field, int $index = 0, string $image_style = ''): ?Url {
    if ($image_style) {
      if (!$this->hasValue($field, $index)) return NULL;

      $style = ImageStyle::load($image_style);

      /** @var FileInterface $image */
      $image = $this->getEntity($field, $index)->toEntity();
      return Url::fromUri($style->buildUrl($image->getFileUri()));
    } else {
      return $this->getUrl($field, $index);
    }
  }

  public function getImageUrls(string $field, string $image_style = ''): array {
    $urls = [];
    foreach ($this->getFor($field) as $index) {
      $urls[] = $this->getImageUrl($field, $index, $image_style);
    }
    return $urls;
  }

  public function getNumber(string $field, int $index = 0, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): ?string {
    $value = $this->getValue($field, $index);
    if ($value === NULL) return NULL;
    return number_format($value, $decimals, $dec_point, $thousands_sep);
  }

  public function getNumbers(string $field, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): array {
    $numbers = [];
    foreach ($this->getFor($field) as $index) {
      $numbers[] = $this->getNumber($field, $index, $decimals, $dec_point, $thousands_sep);
    }
    return $numbers;
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
    $dates = [];
    foreach ($this->getFor($field) as $index) {
      $dates[] = $this->getUTCDate($field, $index, $property);
    }
    return $dates;
  }

  public function getDisplaySettings(string $view_mode = NULL, string $field = NULL): ?array {
    $display = EntityWrapperHelper::getViewDisplay($this, $view_mode ?? $this->toViewMode(), $view_mode === NULL);
    if ($display === NULL) return NULL;
    $displayFields = $display->getComponents();

    if ($field !== NULL) {
      if (isset($displayFields[$field])) {
        return $displayFields[$field];
      }
      throw new PreprocessException('The field ' . $field . ' is unknown.');
    }
    return $displayFields;
  }

  ### view methods ###

  public function formatter(string $field, int $index, string $formatter, array $settings = []): array {
    /** @var FieldItemInterface $item */
    $item = $this->getItem($field, $index);
    if ($item === NULL) return [];

    return $item->view([
      'type' => $formatter,
      'label' => 'hidden',
      'settings' => $settings,
    ]);
  }

  public function formatters(string $field, string $formatter, array $settings = []): array {
    return $this->getItems($field)
      ->view([
        'type' => $formatter,
        'label' => 'hidden',
        'settings' => $settings,
      ]);
  }

  public function entity(string $field, int $index = 0, string $view_mode = 'full'): array {
    $entity = $this->getEntity($field, $index);
    if ($entity === NULL) return [];

    return $entity->toView($view_mode);
  }

  public function entities(string $field, string $view_mode = 'full'): array {
    $entities = $this->getEntities($field);
    foreach ($entities as $index => $entity) {
      $entities[$index] = $entity->toView($view_mode);
    }
    return $entities;
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

  public function addLibrary(string $module, string $library = NULL): EntityWrapperInterface {
    /** @var EntityWrapper $root */
    $root = $this->toRoot();

    if ($library === NULL) {
      $root->vars['#attached']['library'][] = 'your_module/library_name';
    } else {
      $root->vars['#attached']['library'][] = $module . '/' . $library;
    }
    return $this;
  }

  ### cache methods ###

  public function cacheMaxAge(int $seconds = 0): EntityWrapperInterface {
    /** @var EntityWrapper $root */
    $root = $this->toRoot();

    if (empty($root->vars['#cache']['max-age']) || $seconds < $root->vars['#cache']['max-age']) {
      $root->vars['#cache']['max-age'] = $seconds;
    }
    return $this;
  }

  ### cache tag methods ###

  public function cacheAddTags(array $tags = []): EntityWrapperInterface {
    /** @var EntityWrapper $root */
    $root = $this->toRoot();

    if (empty($root->vars['#cache']['tags'])) {
      $root->vars['#cache']['tags'] = $tags;
    } else {
      $root->vars['#cache']['tags'] = Cache::mergeTags($root->vars['#cache']['tags'], $tags);
    }
    return $this;
  }

  public function cacheAddEntity(EntityInterface $entity, bool $forAllEntities = FALSE): EntityWrapperInterface {
    $tags = [
      $entity->getEntityTypeId() . ':' . $entity->id(),
    ];
    if ($forAllEntities) {
      $tags[] = $entity->getEntityTypeId() . '_list';
    }
    return $this->cacheAddTags($tags);
  }

  ### cache context methods ###

  public function cacheAddContexts(array $contexts = []): EntityWrapperInterface {
    /** @var EntityWrapper $root */
    $root = $this->toRoot();

    if (empty($root->vars['#cache']['contexts'])) {
      $root->vars['#cache']['contexts'] = $contexts;
    } else {
      $root->vars['#cache']['contexts'] = Cache::mergeContexts($root->vars['#cache']['contexts'], $contexts);
    }
    return $this;
  }

}
