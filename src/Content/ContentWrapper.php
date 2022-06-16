<?php

namespace Drupal\zero_entitywrapper\Content;

use DateInterval;
use DateTime;
use DateTimeZone;
use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentDisplayCollectionWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentDisplayWrapperInterface;
use Drupal\zero_entitywrapper\Base\ContentWrapperInterface;
use Drupal\zero_entitywrapper\Base\ViewWrapperInterface;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;
use Drupal\zero_entitywrapper\View\ViewWrapper;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;

class ContentWrapper extends BaseWrapper implements ContentWrapperInterface {

  public const CONTENT_BYPASS_ACCESS = 'content_bypass_access';
  public const CONTENT_ACCESS_FOR_ACCOUNT = 'content_access_for_account';

  /**
   * @param ContentEntityBase|ContentWrapperInterface $entity
   * @param BaseWrapperInterface|null $parent
   *
   * @return ContentWrapperInterface
   */
  public static function create($entity, BaseWrapperInterface $parent = NULL): ContentWrapperInterface {
    if ($entity instanceof ContentWrapperInterface) return $entity;
    return new ContentWrapper($entity, NULL, $parent);
  }

  /**
   * @param string $entity_type
   * @param int|string $entity_id
   * @param BaseWrapperInterface|null $parent
   *
   * @return ContentWrapperInterface
   */
  public static function load(string $entity_type, $entity_id, BaseWrapperInterface $parent = NULL): ContentWrapperInterface {
    return new ContentWrapper($entity_type, $entity_id, $parent);
  }

  /**
   * @param string[]|ContentEntityBase[] $entities Item can by either a string <strong>[entity_type]:[entity_id]</strong> or a <strong>ContentEntityBase</strong>
   * @param BaseWrapperInterface|null $parent
   *
   * @return ContentWrapperInterface[]
   */
  public static function multi(array $entities, BaseWrapperInterface $parent = NULL): array {
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

  /**
   * @param string $entity_type
   * @param array $conditions
   * @param BaseWrapperInterface|null $parent
   * @return ContentWrapperInterface[]
   */
  public static function loadByProperties(string $entity_type, array $conditions = [], BaseWrapperInterface $parent = NULL): array {
    $entities = Drupal::entityTypeManager()->getStorage($entity_type)->loadByProperties($conditions);
    foreach ($entities as $index => $entity) {
      $entities[$index] = new ContentWrapper($entity, NULL, $parent);
    }
    return $entities;
  }

  /**
   * @param ContentEntityBase|string $entity_type
   * @param int|string|null $entity_id
   * @param BaseWrapperInterface|null $parent
   */
  private function __construct($entity_type, $entity_id = NULL, BaseWrapperInterface $parent = NULL) {
    parent::__construct($entity_type, $entity_id);
    if ($parent !== NULL) {
      $this->setParent($parent);
      $this->renderContext()->cacheAddEntity($this->entity());
    }
  }

  protected function getConfigAccessAccount(): ?AccountInterface {
    return $this->getConfig(ContentWrapper::CONTENT_ACCESS_FOR_ACCOUNT);
  }

  /**
   * @return ContentEntityBase
   * @noinspection PhpIncompatibleReturnTypeInspection
   */
  public function entity() {
    return $this->entity;
  }

  /**
   * @inheritDoc
   */
  public function url(array $options = [], string $rel = 'canonical'): ?Url {
    return $this->entity()->toUrl($rel, $options);
  }

  /**
   * @inheritDoc
   */
  public function hasField(string $field): bool {
    return $this->entity()->hasField($field);
  }

  /**
   * @inheritDoc
   */
  public function isEmpty(string $field): bool {
    return $this->count($field) === 0;
  }

  /**
   * @inheritDoc
   */
  public function count(string $field): int {
    $count = 0;
    foreach ($this->metaItems($field) as $item) {
      if ($this->metaAcceptItem($item)) {
        $count++;
      } else {
        $this->metaLogItem($field, $item);
      }
    }
    return $count;
  }

  /**
   * @inheritDoc
   */
  public function metaAcceptItem(FieldItemBase $item): bool {
    if ($item->isEmpty()) return FALSE;
    if (in_array($item->getFieldDefinition()->getType(), ['entity_reference', 'entity_reference_revisions'])) {
      if ($item->get('entity')->getValue() === NULL) {
        return FALSE;
      } else if (!$this->access('view', $item->get('entity')->getValue())) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function metaLogItem(string $field, FieldItemBase $item): void {
    if ($item->isEmpty()) return;
    if (in_array($item->getFieldDefinition()->getType(), ['entity_reference', 'entity_reference_revisions'])) {
      if ($item->get('entity')->getValue() === NULL) {
        $this->getService()->log('reference_invalid', 'Reference invalid - deleted entity found in field <code>' . $field . '</code>', ['Entity: <code>' . $this->type() . ' - ' . $this->bundle() . ' - ' . $this->id() . '</code>']);
        Drupal::logger('zero_entitywrapper')->warning('<details><summary>Deleted entity found in field ' . $field . ' [' . $this->type() . ' - ' . $this->bundle() . ' - ' . $this->id() . ']</summary><p>More Data: <pre>' . json_encode($item->getValue(), JSON_PRETTY_PRINT) . '</pre></p></details>');
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function hasValue(string $field): bool {
    return $this->hasField($field) && !$this->isEmpty($field);
  }

  /**
   * @inheritDoc
   */
  public function metaItems(string $field): FieldItemListInterface {
    return $this->entity()->get($field);
  }

  /**
   * @inheritDoc
   */
  public function metaItem(string $field, int $index): ?TypedDataInterface {
    $count = 0;
    foreach ($this->metaItems($field) as $item) {
      if ($this->metaAcceptItem($item) && $index === $count++) {
        return $item;
      }
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  protected function metaForeach(callable $callable, string $field, ...$params) {
    $values = [];
    $index = 0;
    foreach ($this->metaItems($field) as $item) {
      if ($this->metaAcceptItem($item)) {
        $value = $callable($field, $index++, ...$params);
        if ($value === NULL) continue;
        $values[] = $value;
      } else {
        $this->metaLogItem($field, $item);
      }
    }
    return $values;
  }

  /**
   * @inheritDoc
   */
  public function metaEntityKey(string $key) {
    return $this->entity()->getEntityType()->getKey($key);
  }

  /**
   * @inheritDoc
   */
  public function metaFieldType(string $field): string {
    return $this->metaItems($field)->getFieldDefinition()->getType();
  }

  /**
   * @inheritDoc
   */
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

  /**
   * @inheritDoc
   */
  public function metaMainProperty(string $field): string {
    return $this
      ->metaItems($field)
      ->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();
  }

  /**
   * @inheritDoc
   */
  public function metaListOptions(string $field): ?array {
    return $this->metaFieldSettings($field, 'allowed_values');
  }

  /**
   * @inheritDoc
   */
  public function metaReferenceTargetType(string $field): ?string {
    return $this->metaFieldSettings($field, 'target_type');
  }

  /**
   * @inheritDoc
   */
  public function metaReferenceTargetBundles(string $field): ?array {
    return $this->metaFieldSettings($field, 'handler_settings')['target_bundles'];
  }

  /**
   * @inheritDoc
   */
  public function metaMediaSourceField(): ?string {
    return $this->entity()->getSource()->getConfiguration()['source_field'];
  }

  /**
   * @inheritDoc
   */
  public function access($operation = 'view', EntityInterface $entity = NULL, AccountInterface $account = NULL): bool {
    if ($this->getConfig(ContentWrapper::CONTENT_BYPASS_ACCESS)) return TRUE;
    if ($entity === NULL) $entity = $this->entity();
    if ($account === NULL) $account = $this->getConfigAccessAccount();
    return $entity->access($operation, $account);
  }

  protected function transformEntity(EntityInterface $entity = NULL, bool $ignoreAccess = FALSE): ?EntityInterface {
    if ($entity === NULL) return NULL;
    $revision = NULL;

    $entity = WrapperHelper::applyLanguage($entity, $this->entity());
    $this->renderContext()->cacheAddEntity($entity);

    if ($ignoreAccess || $this->access('view', $entity)) {
      return $entity;
    } else {
      return NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function view(): ContentViewWrapper {
    $this->getService()->logDeprecation();

    /** @var ContentViewWrapper $extension */
    $extension = $this->getExtension('view');
    return $extension;
  }

  /**
   * @inheritDoc
   */
  public function display(): ContentDisplayWrapperInterface {
    /** @var ContentDisplayWrapperInterface $extension */
    $extension = $this->getExtension('display');
    return $extension;
  }

  /**
   * @inheritDoc
   */
  public function displayCollection(): ContentDisplayCollectionWrapperInterface {
    /** @var ContentDisplayCollectionWrapperInterface $extension */
    $extension = $this->getExtension('display.collection');
    return $extension;
  }

  /**
   * @inheritDoc
   */
  public function getLabel() {
    return $this->getValue($this->metaEntityKey('label'));
  }

  /**
   * @inheritDoc
   */
  public function getRaw(string $field, int $index = 0, string $property = NULL) {
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    if ($property === NULL) {
      return $item->getValue();
    }
    return $item->getValue()[$property];
  }

  /**
   * @inheritDoc
   */
  public function getRaws(string $field, string $property = NULL): array {
    return $this->metaForeach([$this, 'getRaw'], $field, $property);
  }

  /**
   * @inheritDoc
   */
  public function getValue(string $field, int $index = 0) {
    $main = $this->metaMainProperty($field);

    return $this->getRaw($field, $index, $main);
  }

  /**
   * @inheritDoc
   */
  public function getValues(string $field): array {
    $main = $this->metaMainProperty($field);

    return $this->getRaws($field, $main);
  }

  /**
   * @inheritDoc
   */
  public function getListValue(string $field, int $index = 0) {
    $allowed_values = $this->metaListOptions($field);
    $value = $this->getValue($field, $index);

    if (empty($allowed_values[$value])) return NULL;

    return $allowed_values[$value];
  }

  /**
   * @inheritDoc
   */
  public function getListValues(string $field): array {
    $allowed_values = $this->metaListOptions($field);
    $original = $this->getValues($field);

    $values = [];
    foreach ($original as $index => $value) {
      $values[$value] = $allowed_values[$value];
    }

    return $values;
  }

  /**
   * @inheritDoc
   */
  public function hasListValue(string $field, ...$value): bool {
    foreach ($value as $item) {
      if (!in_array($item, $this->getValues($field))) return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getMarkup(string $field, int $index = 0, string $property = NULL) {
    if ($property === NULL) $property = $this->metaMainProperty($field);
    return Markup::create($this->getRaw($field, $index, $property));
  }

  /**
   * @inheritDoc
   */
  public function getMarkups(string $field, string $property = NULL): array {
    if ($property === NULL) $property = $this->metaMainProperty($field);
    $markups = [];
    foreach ($this->getRaws($field, $property) as $value) {
      $markups[] = Markup::create($value);
    }
    return $markups;
  }

  /**
   * @inheritDoc
   */
  public function getEntity(string $field, int $index = 0, bool $ignoreAccess = FALSE): ?ContentWrapperInterface {
    /** @var FieldItemInterface $item */
    $item = $this->metaItem($field, $index);

    if ($item === NULL || $item->isEmpty()) return NULL;
    /** @var ContentEntityBase $entity */
    $entity = $item->get('entity')->getValue();

    if ($entity === NULL) return NULL;

    $entity = $this->transformEntity($entity, $ignoreAccess);

    return ContentWrapper::create($entity, $this);
  }

  /**
   * @inheritDoc
   */
  public function getEntities(string $field, bool $ignoreAccess = FALSE): ContentWrapperCollection {
    return new ContentWrapperCollection($this->metaForeach([$this, 'getEntity'], $field, $ignoreAccess), ['message' => 'Please use method <code>getEntitiesCollection()</code> instead of <code>getEntities()</code> to use collection features.', 'lines' => ['Collection support will be removed at version 1.0.0']]);
  }

  /**
   * @inheritDoc
   */
  public function getEntitiesCollection(string $field): ContentWrapperCollection {
    return new ContentWrapperCollection($this->metaForeach([$this, 'getEntity'], $field));
  }

  /**
   * @inheritDoc
   */
  public function getAuthor(bool $ignoreAccess = FALSE): ?ContentWrapperInterface {
    return $this->getEntity('uid', 0, $ignoreAccess);
  }

  /**
   * @inheritDoc
   */
  public function getUrl(string $field = NULL, int $index = 0, array $options = []): ?Url {
    $field = WrapperHelper::getDefaultField($this, $field);
    $items = $this->metaItems($field);

    if ($items instanceof EntityReferenceFieldItemListInterface) {
      $wrapper = $this->getEntity($field, $index);
      if ($wrapper === NULL) return NULL;
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
    $item = $this->metaItem($field, $index);

    if ($item === NULL) return NULL;

    if ($this->metaFieldType($field) === 'string') {
      return Url::fromUri($item->getValue()['value'], $options);
    } else {
      // Assume we have a link field
      $value = $item->getValue();
      if (isset($value['options'])) {
        $options = array_merge($value['options'], $options);
      }
      return Url::fromUri($value['uri'], $options);
    }
  }

  /**
   * @inheritDoc
   */
  public function getUrls(string $field = NULL, array $options = []): array {
    return $this->metaForeach([$this, 'getUrl'], $field, $options);
  }

  /**
   * @inheritDoc
   */
  public function setUrl(string $field, Url $url): ContentWrapper {
    $this->entity()->set($field, [$url]);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getLink(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): ?Link {
    /** @var FieldItemInterface */
    $item = $this->metaItem($field, $index);
    if ($item === NULL) return NULL;

    $values = $item->getValue();
    $url = $this->getUrl($field, $index, $options);
    return Link::fromTextAndUrl($title_overwrite ?? $values['title'] ?? $url->toString(), $url);
  }

  /**
   * @inheritDoc
   */
  public function getLinks(string $field, array $options = [], string $title_overwrite = NULL): array {
    return $this->metaForeach([$this, 'getLink'], $field, $options, $title_overwrite);
  }

  /**
   * @inheritDoc
   */
  public function getLinkData(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): array {
    $link = $this->getLink($field, $index, $options, $title_overwrite);

    return [
      'text' => $link->getText(),
      'url' => $link->getUrl()->toString(),
      'options' => $link->getUrl()->getOptions(),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLinksData(string $field, array $options = [], string $title_overwrite = NULL): array {
    return $this->metaForeach([$this, 'getLinkData'], $field, $options, $title_overwrite);
  }

  /**
   * @inheritDoc
   */
  public function getImageUrl(string $field = NULL, int $index = 0, string $image_style = ''): ?Url {
    $field = WrapperHelper::getDefaultField($this, $field);
    if ($image_style) {
      $wrapper = $this->getEntity($field, $index);
      if ($wrapper === NULL) return NULL;
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

  /**
   * @inheritDoc
   */
  public function getImageUrls(string $field = NULL, string $image_style = ''): array {
    return $this->metaForeach([$this, 'getImageUrl'], $field, $image_style);
  }

  /**
   * @inheritDoc
   */
  public function getNumber(string $field, int $index = 0, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): ?string {
    $value = $this->getValue($field, $index);
    if ($value === NULL) return NULL;
    return number_format($value, $decimals, $dec_point, $thousands_sep);
  }

  /**
   * @inheritDoc
   */
  public function getNumbers(string $field, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): array {
    return $this->metaForeach([$this, 'getNumber'], $field, $decimals, $dec_point, $thousands_sep);
  }

  /**
   * @inheritDoc
   */
  public function getDateTime(string $field, int $index = 0, string $property = NULL): DrupalDateTime {
    if ($property === NULL) $property = $this->metaMainProperty($field);
    return $this->metaItem($field, $index)->get($property)->getDateTime();
  }

  /**
   * @inheritDoc
   */
  public function getDateTimes(string $field, string $property = NULL): array {
    return $this->metaForeach([$this, 'getDateTime'], $field, $property);
  }

  /**
   * @inheritDoc
   */
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

  /**
   * @inheritDoc
   */
  public function getUTCDates(string $field, string $property = 'value'): array {
    return $this->metaForeach([$this, 'getUTCDate'], $field, $property);
  }

  /**
   * @inheritDoc
   */
  public function getDateDiff(string $field, int $index = 0): DateInterval {
    return $this->getDateTime($field)->diff($this->getDateTime($field, $index, 'ent_value'));
  }

  /**
   * @inheritDoc
   */
  public function getDateDiffs(string $field): array {
    return $this->metaForeach([$this, 'getDateDiff'], $field);
  }

  /**
   * @inheritDoc
   */
  public function getDateRange(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array {
    /** @var Drupal\Core\Datetime\DateFormatterInterface $formatter */
    $formatter = Drupal::service('date.formatter');

    $start_time = $this->getDateTime($field, $index)->getTimestamp();
    $end_time = $this->getDateTime($field, $index, 'end_value')->getTimestamp();

    if ($type === 'custom') {
      $start = $formatter->format($start_time, 'custom', $start_format);
      $end = $formatter->format($end_time, 'custom', $end_format);
    } else {
      $start = $formatter->format($start_time, $type);
      $end = $formatter->format($end_time, $type);
    }

    return [
      'start' => $start,
      'end' => $end,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDateRanges(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array {
    return $this->metaForeach([$this, 'getDateRange'], $field, $type, $start_format, $end_format);
  }

  /**
   * @inheritDoc
   */
  public function getDateRangeFormatted(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $seperator = ' - '): string {
    $data = $this->getDateRange($field, $index, $type, $start_format, $end_format);
    return $data['start'] . $seperator . $data['end'];
  }

  /**
   * @inheritDoc
   */
  public function getDateRangesFormatted(string $field, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $seperator = ' - '): array {
    return $this->metaForeach([$this, 'getDateRangeFormatted'], $field, $type, $start_format, $end_format);
  }

  /**
   * @inheritDoc
   */
  public function getDateRangeMerged(string $field, int $index = 0, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $seperator = ' - '): string {
    $data = $this->getDateRange($field, $index, $type, $format, $format);
    for ($i = 0; $i < strlen($data['start']); $i++) {
      if (substr($data['start'], $i) === substr($data['end'], $i)) break;
    }
    return substr($data['start'], 0, $i) . $seperator . $data['end'];
  }

  /**
   * @inheritDoc
   */
  public function getDateRangesMerged(string $field, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $seperator = ' - '): array {
    return $this->metaForeach([$this, 'getDateRangeMerged'], $field, $type, $format, $seperator);
  }

  /**
   * @inheritDoc
   */
  public function getView(string $field, string $explode = ':'): ?ViewWrapperInterface {
    $value = $this->getValue($field);
    [$view, $display] = explode($explode, $value);
    return new ViewWrapper($view, $display, $this);
  }

  /**
   * @inheritDoc
   */
  public function getFileContent(string $field = NULL, int $index = 0): string {
    return file_get_contents($this->getFilePath($field, $index));
  }

  /**
   * @inheritDoc
   */
  public function getFileContents(string $field = NULL): array {
    return $this->metaForeach([$this, 'getFileContent'], $field);
  }

  /**
   * @inheritDoc
   */
  public function getFilePath(string $field = NULL, int $index = 0): string {
    return $this->getStreamWrapper($field, $index)->realpath();
  }

  /**
   * @inheritDoc
   */
  public function getFilePaths(string $field = NULL): array {
    return $this->metaForeach([$this, 'getFilePath'], $field);
  }

  /**
   * @inheritDoc
   */
  public function getStreamWrapper(string $field = NULL, int $index = 0): StreamWrapperInterface {
    $field = WrapperHelper::getDefaultField($this, $field);
    $wrapper = $this->getEntity($field, $index);
    /** @var FileInterface $file */
    if ($wrapper->type() === 'media') {
      $file = $wrapper->getEntity($wrapper->metaMediaSourceField())->entity();
    } else {
      $file = $wrapper->entity();
    }
    return \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri());
  }

  /**
   * @inheritDoc
   */
  public function getStreamWrappers(string $field = NULL): array {
    return $this->metaForeach([$this, 'getStreamWrapper'], $field);
  }

}
