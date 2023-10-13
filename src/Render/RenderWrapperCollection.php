<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Render;

use ArrayObject;
use Drupal;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;

class RenderWrapperCollection extends ArrayObject implements RenderableInterface {

  /** @var BaseWrapperInterface|null */
  private $wrapper;
  /** @var RendererInterface */
  private $renderer;

  /**
   * ContentWrapperCollection constructor.
   *
   * @param array $array
   * @param BaseWrapperInterface $wrapper
   */
  public function __construct(array $array = [], BaseWrapperInterface $wrapper = NULL) {
    parent::__construct($array);
    $this->wrapper = $wrapper;
  }

  protected function getRenderer(): RendererInterface {
    if ($this->renderer === NULL) {
      $this->renderer = Drupal::service('renderer');
    }
    return $this->renderer;
  }

  public function getWrapper(): ?BaseWrapperInterface {
    return $this->wrapper;
  }

  private function getValue($value, ...$params) {
    if (is_callable($value)) {
      return $value($this, ...$params);
    } else {
      return $value;
    }
  }

  /**
   * @deprecated Will be removed at version 1.0.0, use instead <code>$wrapper->display()</code>
   * @param string $name
   * @param callable|* $value
   * @return $this
   */
  public function setItemData(string $name, $value): self {
    $this->getWrapper()->getService()->logDeprecation();
    $copy = $this->getArrayCopy();
    foreach (Element::children($copy) as $index) {
      $this[$index][$name] = $this->getValue($value, $this[$index], $index);
    }
    return $this;
  }

  /**
   * @deprecated Will be removed at version 1.0.0, info for migrate:<br />
   *   <strong>if used with <code>$wrapper->display()->responsiveImage()</code></strong><br />
   *     - remove this method<br />
   *     - add <code>$value</code> as last parameter <code>$item_attributes</code> to <code>$wrapper->display()->responsiveImage()</code><br />
   *   <br />
   *   <strong>if used another way, try to rebuild the result with:</strong>
   *     ```
   *     $wrapper->displayCollection()->{...}->setWrapper($value)
   *     // or
   *     $wrapper->displayCollection()->{...}->setItemWrapper($value)
   *     ```
   * @param callable|array $value
   * @return $this
   */
  public function setItemAttributes($value): self {
    $this->getWrapper()->getService()->logDeprecation();
    $this['#item_attributes'] = $this->getValue($value);
    return $this;
  }

  /**
   * @deprecated Will be removed at version 1.0.0, info for migrate:<br />
   *    <strong>if used with <code>$wrapper->display()->responsiveImage()</code></strong><br />
   *      - remove this method<br />
   *      - add <code>['class' => [...$classes]]</code> as last parameter <code>$item_attributes</code> to <code>$wrapper->display()->responsiveImage()</code><br />
   *    <br />
   *    <strong>if used another way, try to rebuild the result with:</strong>
   *      ```
   *      $wrapper->displayCollection()->{...}->setWrapper(['class' => [...$classes]])
   *      // or
   *      $wrapper->displayCollection()->{...}->setItemWrapper(['class' => [...$classes]])
   *      ```
   * @param string ...$classes
   *
   * @return $this
   */
  public function addItemClass(string ...$classes): self {
    $this->getWrapper()->getService()->logDeprecation();
    if (empty($this['#item_attributes']['class'])) {
      $this['#item_attributes']['class'] = [];
    }
    foreach ($classes as $class) {
      $this['#item_attributes']['class'][] = $class;
    }
    return $this;
  }

  public function each(callable $callback): self {
    $vars = $this->getArrayCopy();
    foreach (Element::children($vars) as $delta) {
      $callback(new RenderItemWrapper($this, $delta));
    }
    return $this;
  }

  public function getInfo(string $key) {
    return $this['#entitywrapper_info'][$key] ?? NULL;
  }

  /**
   * EXPERIMENTAL: Because it is not testet with all display functions and break the standard fields templates in older versions.<br /><br />
   * Set info for the entitywrapper_info theme
   *
   * @param string $key the key to set
   * @param $value the value to set
   * @param bool $merge merge the values of the key instead of override it
   * @return $this
   */
  public function setInfo(string $key, $value, bool $merge = FALSE): self {
    // if render array has no element, create element 0 with values
    $array = $this->getArrayCopy();
    if (!count(Element::children($array))) $this[] = $array;

    $this['#theme'] = 'entitywrapper_field';
    if ($merge && isset($this['#entitywrapper_info'][$key])) {
      $this['#entitywrapper_info'][$key] = array_merge_recursive($this['#entitywrapper_info'][$key], $value);
    } else {
      $this['#entitywrapper_info'][$key] = $value;
    }
    return $this;
  }

  /**
   * Get item info
   *
   * @param $delta
   * @param string $key
   * @return mixed
   */
  public function getItemInfo($delta, string $key) {
    return $this['#entitywrapper_info']['items'][$delta][$key] ?? NULL;
  }

  /**
   * EXPERIMENTAL: Because it is not testet with all display functions and break the standard fields templates in older versions.<br /><br />
   * Set item info for the entitywrapper_info theme
   *
   * @param string|int $delta render key for the item
   * @param string $key the key to set EXAMPLE: loading
   * @param $value the value to set EXAMPLE: lazy
   * @param bool $merge merge the values of the key instead of override it
   * @return $this
   */
  public function setItemInfo($delta, string $key, $value, bool $merge = FALSE): self {
    // if render array has no element, create element 0 with values
    $array = $this->getArrayCopy();
    if (!count(Element::children($array))) $this[] = $array;

    $this['#theme'] = 'entitywrapper_field';
    if ($merge && isset($this['#entitywrapper_info']['items'][$delta][$key])) {
      $this['#entitywrapper_info']['items'][$delta][$key] = array_merge_recursive($this['#entitywrapper_info']['items'][$delta][$key], $value);
    } else {
      $this['#entitywrapper_info']['items'][$delta][$key] = $value;
    }
    return $this;
  }

  /**
   * EXPERIMENTAL: Because it is not testet with all display functions and break the standard fields templates in older versions.<br /><br />
   * Set the wrapper for all items
   *
   * @param array $options = [
   *     'none' => TRUE,
   *     'element' => 'div',
   *     'class' => ['wrapper', 'wrapper--field'],
   *     'data-src' => '/path/to/src',
   * ]
   * @param bool $merge
   *
   * @return $this
   */
  public function setWrapper(array $options, bool $merge = TRUE): self {
    return $this->setInfo('wrapper', $options, $merge);
  }

  /**
   * EXPERIMENTAL: Because it is not testet with all display functions and break the standard fields templates in older versions.<br /><br />
   * Set the wrapper for every item
   *
   * @param array $options = [
   *     'none' => TRUE,
   *     'element' => 'div',
   *     'class' => ['wrapper', 'wrapper--field'],
   *     'data-src' => '/path/to/src',
   * ]
   * @param bool $merge
   *
   * @return $this
   */
  public function setItemWrapper(array $options, bool $merge = TRUE): self {
    return $this->setItemInfo('_all', 'wrapper', $options, $merge);
  }

  public function toRenderable(): array {
    return $this->getArrayCopy();
  }

  public function render(): MarkupInterface {
    $vars = $this->getArrayCopy();
    return $this->getRenderer()->render($vars);
  }

  public function toString(): string {
    return $this->render()->__toString();
  }

  public function __toString(): string {
    return $this->toString();
  }

}
