<?php

namespace Drupal\zero_entitywrapper\Base;

use DateInterval;
use DateTime;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\zero_entitywrapper\Content\ContentViewWrapper;
use Drupal\zero_entitywrapper\Content\ContentWrapperCollection;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;

interface ContentWrapperInterface extends BaseWrapperInterface {

  public const CONTENT_BYPASS_ACCESS = 'content_bypass_access';
  public const CONTENT_ACCESS_FOR_ACCOUNT = 'content_access_for_account';

  /**
   * Get the url from this entity
   *
   * @see ContentEntityBase::toUrl()
   *
   * @param array $options
   * @param string $rel
   * @return Url|null
   */
  public function url(array $options = [], string $rel = 'canonical'): ?Url;

  /**
   * If this entity has a field
   *
   * @see ContentEntityBase::hasField()
   *
   * @param string $field
   * @return bool
   */
  public function hasField(string $field): bool;

  /**
   * If this field is empty
   *
   * @param string $field
   * @return bool
   */
  public function isEmpty(string $field): bool;

  /**
   * Count the field items
   *
   * @param string $field
   * @return int
   */
  public function count(string $field): int;

  /**
   * If the field item is valid
   *
   * @param FieldItemBase $item
   * @return bool
   */
  public function metaAcceptItem(FieldItemBase $item): bool;

  /**
   * Log if the field has a not valid entity in storage
   *
   * @param string $field
   * @param FieldItemBase $item
   */
  public function metaLogItem(string $field, FieldItemBase $item): void;

  /**
   * If the field exist and have values
   *
   * @see ContentWrapperInterface::hasField()
   * @see ContentWrapperInterface::isEmpty()
   *
   * @param string $field
   * @return bool
   */
  public function hasValue(string $field): bool;

  /**
   * Get the specific drupal items of a field
   *
   * @param string $field
   * @return FieldItemListInterface
   */
  public function metaItems(string $field): FieldItemListInterface;

  /**
   * Get a specific drupal item of a field
   *
   * @param string $field
   * @param int $index
   * @return TypedDataInterface|null
   */
  public function metaItem(string $field, int $index): ?TypedDataInterface;

  /**
   * Get the defined bundle key
   *
   * @see EntityTypeInterface::getKey()
   *
   * @param string $key
   * @return mixed
   */
  public function metaEntityKey(string $key);

  /**
   * Get the field type
   *
   * @see FieldDefinitionInterface::getType()
   *
   * @param string $field
   * @return string
   */
  public function metaFieldType(string $field): string;

  /**
   * Get the field settings, or from property
   *
   * @see FieldDefinitionInterface::getSettings()
   * @see FieldStorageDefinitionInterface::getSettings()
   *
   * @param string $field
   * @param string|NULL $property
   * @return mixed
   */
  public function metaFieldSettings(string $field, string $property = NULL);

  /**
   * Get the name of the main property
   *
   * @see FieldStorageDefinitionInterface::getMainPropertyName()
   *
   * @param string $field
   * @return string
   */
  public function metaMainProperty(string $field): string;

  /**
   * Get the human-readable values of a list field
   *
   * @param string $field
   * @return array|null
   */
  public function metaListOptions(string $field): ?array;

  /**
   * Get the entity_type of a reference field target
   *
   * @param string $field
   * @return string|null
   */
  public function metaReferenceTargetType(string $field): ?string;

  /**
   * Get a list of allowed entity_bundles of a reference field target
   *
   * @param string $field
   * @return array|null
   */
  public function metaReferenceTargetBundles(string $field): ?array;

  /**
   * Get the source field of a media entity
   *
   * @return string|null
   */
  public function metaMediaSourceField(): ?string;

  /**
   * Check if entity has access
   *
   * @param $operation
   * @param EntityInterface|NULL $entity
   * @param AccountInterface|NULL $account
   * @return bool
   */
  public function access($operation = 'view', EntityInterface $entity = NULL, AccountInterface $account = NULL): bool;

  /**
   * @deprecated Will be removed at version 1.0.0, use instead <code>$wrapper->display()</code>
   *   <i>More Info:</i>
   *   Use <code>$wrapper->displayCollection()</code> if you used the collection feature of the <code>ContentViewWrapper</code>.
   *   <i>Example:</i>
   *   <code>$wrapper->displayCollection()->responsiveImage('field_placeholder', 0, 'video_placeholder')->addItemClass('idle--fit');</code>
   * @return ContentViewWrapper
   */
  public function view(): ContentViewWrapper;

  /**
   * Get the render arrays for fields.
   *
   * @return ContentDisplayWrapperInterface
   */
  public function display(): ContentDisplayWrapperInterface;

  /**
   * Get the render arrays as collections.
   *
   * @see ContentWrapperInterface::display()
   *
   * @return ContentDisplayCollectionWrapperInterface
   */
  public function displayCollection(): ContentDisplayCollectionWrapperInterface;

  /**
   * Get the label of the entity
   *
   * @return mixed
   */
  public function getLabel();

  /**
   * Get the value of a field or property
   *
   * @param string $field
   * @param int $index
   * @param string|NULL $property
   * @return mixed
   */
  public function getRaw(string $field, int $index = 0, string $property = NULL);

  /**
   * Get all value of a field or property
   *
   * @see ContentWrapperInterface::getRaw()
   *
   * @param string $field
   * @param string|NULL $property
   * @return array
   */
  public function getRaws(string $field, string $property = NULL): array;

  /**
   * Get the value of the field
   *
   * @see ContentWrapperInterface::metaMainProperty()
   * @see ContentWrapperInterface::getRaw()
   *
   * @param string $field
   * @param int $index
   * @return mixed
   */
  public function getValue(string $field, int $index = 0);

  /**
   * Get all values of the field
   *
   * @see ContentWrapperInterface::getValue()
   *
   * @param string $field
   * @return array
   */
  public function getValues(string $field): array;

  /**
   * Get human-readable value of a list field
   *
   * @see ContentWrapperInterface::metaListOptions()
   *
   * @param string $field
   * @param int $index
   * @return mixed
   */
  public function getListValue(string $field, int $index = 0);

  /**
   * Get all human-readable values of a list field
   *
   * @see ContentWrapperInterface::getListValue()
   *
   * @param string $field
   * @return array
   */
  public function getListValues(string $field): array;

  /**
   * If a list field has all values
   *
   * @param string $field
   * @param ...$value
   * @return bool
   */
  public function hasListValue(string $field, ...$value): bool;

  /**
   * Get value of the field as markup
   *
   * @see ContentWrapperInterface::metaMainProperty()
   *
   * @param string $field
   * @param int $index
   * @param string|NULL $property
   * @return MarkupInterface|string
   */
  public function getMarkup(string $field, int $index = 0, string $property = NULL);

  /**
   * Get all values of the field as markup
   *
   * @see ContentWrapperInterface::getMarkup()
   *
   * @param string $field
   * @param string|NULL $property
   * @return MarkupInterface[]|string[]
   */
  public function getMarkups(string $field, string $property = NULL): array;

  /**
   * Get the entity of the reference field
   *
   * Please don't use the parameter $ignoreAccess, instead use `$wrapper->setConfig(ContentWrapperInterface::CONTENT_BYPASS_ACCESS)`
   *
   * @param string $field
   * @param int $index
   * @param bool $ignoreAccess
   * @return ContentWrapperInterface|null
   */
  public function getEntity(string $field, int $index = 0, bool $ignoreAccess = FALSE): ?ContentWrapperInterface;

  /**
   * Get all entities of the reference field
   * Please use the method "getEntitiesCollection()" to get the ContentWrapperCollection.
   *
   * Please don't use the parameter $ignoreAccess, instead use `$wrapper->setConfig(ContentWrapperInterface::CONTENT_BYPASS_ACCESS)`
   *
   * @see ContentWrapperInterface::getEntity()
   * @see ContentWrapperInterface::getEntitiesCollection()
   *
   * @param string $field
   *
   * @return ContentWrapperInterface|ContentWrapperInterface[]
   */
  public function getEntities(string $field, bool $ignoreAccess = FALSE): ContentWrapperCollection;

  /**
   * Get all entities of the reference field as collection
   *
   * @param string $field
   *
   * @return ContentWrapperInterface|ContentWrapperInterface[]|ContentWrapperCollection
   */
  public function getEntitiesCollection(string $field): ContentWrapperCollection;

  /**
   * Get the author of this entity
   *
   * Please don't use the parameter $ignoreAccess, instead use `$wrapper->setConfig(ContentWrapperInterface::CONTENT_BYPASS_ACCESS)`
   *
   * @param bool $ignoreAccess
   * @return ContentWrapperInterface|null
   */
  public function getAuthor(bool $ignoreAccess = FALSE): ?ContentWrapperInterface;

  /**
   * Get the url of entity field or media field or url field or link field
   *
   * @see WrapperHelper::getDefaultField()
   *
   * @param string|NULL $field
   * @param int $index
   * @param array $options = [
   *     'attributes' => ['name' => 'value'],
   *     'query' => ['name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @return Url|null
   */
  public function getUrl(string $field = NULL, int $index = 0, array $options = []): ?Url;

  /**
   * Get all urls of entity field or media field or url field or link field
   *
   * @param string|NULL $field
   * @param array $options = [
   *     'attributes' => ['name' => 'value'],
   *     'query' => ['name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @return array
   */
  public function getUrls(string $field = NULL, array $options = []): array;

  /**
   * Get the link of a link field
   *
   * @param string $field
   * @param int $index
   * @param array $options = [
   *     'attributes' => ['name' => 'value'],
   *     'query' => ['name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @param string|NULL $title_overwrite
   * @return Link|null
   */
  public function getLink(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): ?Link;

  /**
   * Get all links of a link field
   *
   * @param string $field
   * @param array $options = [
   *     'attributes' => ['name' => 'value'],
   *     'query' => ['name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @param string|NULL $title_overwrite
   * @return array
   */
  public function getLinks(string $field, array $options = [], string $title_overwrite = NULL): array;

  /**
   * Get link as render ready
   *
   * @param string $field
   * @param int $index
   * @param array $options = [
   *     'attributes' => [':name' => 'value'],
   *     'query' => [':name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @param string|NULL $title_overwrite
   * @return array = [
   *     'text' => $link->getText(),
   *     'url' => $link->getUrl()->toString(),
   *     'options' => [
   *        'attributes' => [':name' => 'value'],
   *     ],
   * ]
   */
  public function getLinkData(string $field, int $index = 0, array $options = [], string $title_overwrite = NULL): array;

  /**
   * Get links as render ready
   *
   * @see ContentWrapperInterface::getLinkData()
   *
   * @param string $field
   * @param array $options = [
   *     'attributes' => ['name' => 'value'],
   *     'query' => ['name' => 'value'],
   *     'fragment' => 'jumpmark',
   *     'absolute' => FALSE,
   *     'language' => LanguageInterface::TYPE_URL,
   *     'https' => NULL,
   * ]
   * @param string|NULL $title_overwrite
   * @return array
   */
  public function getLinksData(string $field, array $options = [], string $title_overwrite = NULL): array;

  /**
   * Get the image url of a image or media field
   *
   * @param string|NULL $field
   * @param int $index
   * @param string $image_style
   * @return Url|null
   */
  public function getImageUrl(string $field = NULL, int $index = 0, string $image_style = ''): ?Url;

  /**
   * Get all image urls of a image or media field
   *
   * @see ContentWrapperInterface::getImageUrl()
   *
   * @param string|NULL $field
   * @param string $image_style
   * @return array
   */
  public function getImageUrls(string $field = NULL, string $image_style = ''): array;

  /**
   * Get the number formatted of a number field
   *
   * @param string $field
   * @param int $index
   * @param int $decimals
   * @param string $dec_point
   * @param string $thousands_sep
   * @return string|null
   */
  public function getNumber(string $field, int $index = 0, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): ?string;

  /**
   * Get all numbers formatted of a number field
   *
   * @param string $field
   * @param int $decimals
   * @param string $dec_point
   * @param string $thousands_sep
   * @return array
   */
  public function getNumbers(string $field, int $decimals = 2, string $dec_point = '.', string $thousands_sep = ','): array;

  /**
   * Get a DrupalDateTime object of a time field
   *
   * @see DrupalDateTime
   *
   * @param string $field
   * @param int $index
   * @param string|null $property ('value', 'end_value')
   * @return DrupalDateTime
   */
  public function getDateTime(string $field, int $index = 0, string $property = NULL): DrupalDateTime;

  /**
   * Get all DrupalDateTime object of a time field
   *
   * @param string $field
   * @param string|null $property ('value', 'end_value')
   * @return DrupalDateTime[]
   */
  public function getDateTimes(string $field, string $property = NULL): array;

  /**
   * Get DateTime object of a time field
   *
   * @param string $field
   * @param int $index
   * @param string $property
   * @return DateTime|null
   */
  public function getUTCDate(string $field, int $index = 0, string $property = 'value'): ?DateTime;

  /**
   * Get all DateTime objects of a time field
   *
   * @param string $field
   * @param string $property
   * @return array
   */
  public function getUTCDates(string $field, string $property = 'value'): array;

  /**
   * Get the date diff
   *
   * @param string $field
   * @param int $index
   * @return DateInterval
   */
  public function getDateDiff(string $field, int $index = 0): DateInterval;

  /**
   * Get the date diff
   *
   * @param string $field
   * @return array
   */
  public function getDateDiffs(string $field): array;

  /**
   * Get the date range
   *
   * @param string $field
   * @param int $index
   * @param string $type
   * @param string $start_format
   * @param string $end_format
   * @return array
   */
  public function getDateRange(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array;

  /**
   * Get all date ranges of a field
   *
   * @param string $field
   * @param int $index
   * @param string $type
   * @param string $start_format
   * @param string $end_format
   * @return array
   */
  public function getDateRanges(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT): array;

  /**
   * Get the date range formatted
   *
   * @param string $field
   * @param int $index
   * @param string $type
   * @param string $start_format
   * @param string $end_format
   * @param string $seperator
   * @return string
   */
  public function getDateRangeFormatted(string $field, int $index = 0, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $seperator = ' - '): string;

  /**
   * Get all date range formatted
   *
   * @param string $field
   * @param string $type
   * @param string $start_format
   * @param string $end_format
   * @param string $seperator
   * @return array
   */
  public function getDateRangesFormatted(string $field, string $type = 'medium', string $start_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $end_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, string $seperator = ' - '): array;

  /**
   * Get date range formatted and merged equals
   *
   * @param string $field
   * @param int $index
   * @param string $type
   * @param string $format
   * @param $seperator
   * @return string
   */
  public function getDateRangeMerged(string $field, int $index = 0, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $seperator = ' - '): string;

  /**
   * Get date range formatted and merged equals
   *
   * @param string $field
   * @param string $type
   * @param string $format
   * @param $seperator
   * @return array
   */
  public function getDateRangesMerged(string $field, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $seperator = ' - '): array;

  /**
   * Get the view wrapper of a field with key as `<view>:<display>`
   *
   * @param string $field
   * @param string $explode
   * @return ViewWrapperInterface|null
   */
  public function getView(string $field, string $explode = ':'): ?ViewWrapperInterface;

  /**
   * Get the file content of a file reference field
   *
   * @see file_get_contents()
   * @see ContentWrapperInterface::getFilePath()
   *
   * @param string|NULL $field
   * @param int $index
   * @return string
   */
  public function getFileContent(string $field = NULL, int $index = 0): string;

  /**
   * Get all file content of a file reference field
   *
   * @param string|NULL $field
   * @return array
   */
  public function getFileContents(string $field = NULL): array;

  /**
   * Get a file path of a file reference field
   *
   * @param string|NULL $field
   * @param int $index
   * @return string
   */
  public function getFilePath(string $field = NULL, int $index = 0): string;

  /**
   * Get all file paths fo a file reference field
   *
   * @param string|NULL $field
   * @return array
   */
  public function getFilePaths(string $field = NULL): array;

  /**
   * Get the streamwrapper of a file reference field
   *
   * @param string|NULL $field
   * @param int $index
   * @return StreamWrapperInterface
   */
  public function getStreamWrapper(string $field = NULL, int $index = 0): StreamWrapperInterface;

  /**
   * Get all streamwrappers of a file reference field
   *
   * @param string|NULL $field
   * @return array
   */
  public function getStreamWrappers(string $field = NULL): array;

}
