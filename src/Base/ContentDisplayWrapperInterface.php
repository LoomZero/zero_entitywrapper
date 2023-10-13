<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

interface ContentDisplayWrapperInterface {

  /**
   * @param string|NULL $view_mode
   * @param string|NULL $field
   *
   * @return array|null
   */
  public function getDisplaySettings(string $view_mode = NULL, string $field = NULL): ?array;

  /**
   * @param string $field
   * @param string|NULL $view_mode
   *
   * @return array
   */
  public function as(string $field, string $view_mode = NULL);

  /**
   * @param string $field
   * @param int $index
   * @param string $formatter
   * @param array $settings
   *
   * @return array
   */
  public function formatter(string $field, int $index, string $formatter, array $settings = []);

  /**
   * @param string $field
   * @param string $formatter
   * @param array $settings
   *
   * @return array
   */
  public function formatters(string $field, string $formatter, array $settings = []);

  /**
   * @param string $field
   * @param int $index
   * @param string $view_mode
   *
   * @return array
   */
  public function entity(string $field, int $index = 0, string $view_mode = 'full');

  /**
   * @param string $field
   * @param string $view_mode
   *
   * @return array
   */
  public function entities(string $field, string $view_mode = 'full');

  /**
   * @param string $field
   * @param int $index
   * @param bool $linkToEntity
   *
   * @return array
   */
  public function string(string $field, int $index = 0, bool $linkToEntity = FALSE);

  /**
   * @param string $field
   * @param bool $linkToEntity
   *
   * @return array
   */
  public function strings(string $field, bool $linkToEntity = FALSE);

  /**
   * @param string $field
   * @param int $index
   * @param int $trimmed
   * @param bool $summary
   *
   * @return array
   */
  public function body(string $field, int $index = 0, int $trimmed = 0, bool $summary = FALSE);

  /**
   * @param string $field
   * @param int $trimmed
   * @param bool $summary
   *
   * @return array
   */
  public function bodies(string $field, int $trimmed = 0, bool $summary = FALSE);

  /**
   * @param string $field
   * @param int $index
   * @param string $image_style
   * @param string $image_link
   *
   * @return array
   */
  public function image(string $field, int $index = 0, string $image_style = '', string $image_link = '');

  /**
   * @param string $field
   * @param string $image_style
   * @param string $image_link
   *
   * @return array
   */
  public function images(string $field, string $image_style = '', string $image_link = '');

  /**
   * @param string|NULL $field
   * @param int $index
   * @param array $options = [
   *     '<media_bundle>' = [
   *        'embed' => TRUE,
   *        'video' => TRUE,
   *        'responsive' => '',
   *        'style' => '',
   *        'attributes' => ['class' => 'custom-class'],
   *        'element' => ['#width' => 500],
   *     ],
   * ]
   * @param array $additions = [
   *     '#attributes' => ['class' => 'custom-class'],
   *     '#alt' => 'media',
   *     '#title' => '',
   *     '#type' => '<media_bundle>',
   * ]
   *
   * @return array
   */
  public function media(string $field = NULL, int $index = 0, array $options = [], array $additions = []);

  /**
   * @see ContentDisplayWrapperInterface::media()
   *
   * @param string|NULL $field
   * @param array $options
   * @param array $additions
   *
   * @return array
   */
  public function medias(string $field = NULL, array $options = [], array $additions = []);

  /**
   * @param string $field
   * @param int $index
   * @param string $responsive_image_style
   * @param string $image_link
   * @param ?array|callable $item_attributes Use this parameter to add attributes to items. Allowed all parameter from Attribute object.
   *  Alternativ you can use a callable to adjust the attributes for every item.<br />
   *  CALLABLE ARGUMENTS:<br />
   *    - array $item The current item to add attributes<br />
   *    - NULL $key The render key from $parent (WARNING: is NULL for single call)<br />
   *    - int $index The delta of the item<br />
   *    - NULL $parent The parent render array (mostly theme field) (WARNING: is NULL for single call)<br />
   *  CALLABLE RETURN: array Attributes array supported by Attribute object.<br />
   *  CALLABLE EXAMPLE:<br />
   *  ```
   *  $wrapper
   *  ->display()
   *  ->responsiveImages('field_image', 'responsive_image_style', '', function($item, $key, $index, $parent) {
   *    return ['class' => ['additional-class']];
   *  });
   *  ```
   *
   * @return array
   */
  public function responsiveImage(string $field, int $index = 0, string $responsive_image_style = '', string $image_link = '', $item_attributes = NULL);

  /**
   * @param string $field
   * @param string $responsive_image_style
   * @param string $image_link
   * @param ?array|callable $item_attributes Use this parameter to add attributes to items. Allowed all parameter from Attribute object.
   *   Alternativ you can use a callable to adjust the attributes for every item.<br />
   *   CALLABLE ARGUMENTS:<br />
   *     - array $item The current item to add attributes<br />
   *     - string|int $key The render key from $parent<br />
   *     - int $index The delta of the item<br />
   *     - array $parent The parent render array (mostly theme field)<br />
   *   CALLABLE RETURN: array Attributes array supported by Attribute object.<br />
   *   CALLABLE EXAMPLE:<br />
   *   ```
   *   $wrapper
   *   ->display()
   *   ->responsiveImages('field_image', 'responsive_image_style', '', function($item, $key, $index, $parent) {
   *     return ['class' => ['additional-class']];
   *   });
   *   ```
   *
   * @return array
   */
  public function responsiveImages(string $field, string $responsive_image_style = '', string $image_link = '', $item_attributes = NULL);

  /**
   * @param string $field
   * @param int $index
   * @param string $type
   * @param string $format
   *
   * @return array
   */
  public function date(string $field, int $index = 0, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

  /**
   * @param string $field
   * @param string $type
   * @param string $format
   *
   * @return array
   */
  public function dates(string $field, string $type = 'medium', string $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

  /**
   * @param string $template
   * @param callable|array $context
   *
   * @return array
   */
  public function template(string $template, $context = []);

  /**
   * @param string $path
   * @param callable|array $vars
   * @param null|string $pattern
   *
   * @return array
   */
  public function component(string $path, $vars = [], string $pattern = NULL);
}
