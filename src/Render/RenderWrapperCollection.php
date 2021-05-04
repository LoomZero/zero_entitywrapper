<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Render;

use ArrayObject;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderableInterface;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;

class RenderWrapperCollection extends ArrayObject implements RenderableInterface {

    /** @var BaseWrapper|null */
    private $wrapper;

    /**
     * ContentWrapperCollection constructor.
     *
     * @param array $array
     * @param BaseWrapper $wrapper
     */
    public function __construct(array $array = [], BaseWrapper $wrapper = NULL) {
        parent::__construct($array);
        $this->wrapper = $wrapper;
    }

    public function getWrapper(): ?BaseWrapper {
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
     * @param string $name
     * @param callable|* $value
     * @return RenderWrapperCollection
     */
    public function setItemData(string $name, $value): RenderWrapperCollection {
        $copy = $this->getArrayCopy();
        foreach (Element::children($copy) as $index) {
            $this[$index][$name] = $this->getValue($value, $this[$index], $index);
        }
        return $this;
    }

    /**
     * @param callable|array $value
     * @return RenderWrapperCollection
     */
    public function setItemAttributes($value): RenderWrapperCollection {
        $this['#item_attributes'] = $this->getValue($value);
        return $this;
    }

    public function addItemClass(string ...$classes): RenderWrapperCollection {
        if (empty($this['#item_attributes']['class'])) {
            $this['#item_attributes']['class'] = [];
        }
        foreach ($classes as $class) {
            $this['#item_attributes']['class'][] = $class;
        }
        return $this;
    }

    public function toRenderable(): array {
        return $this->getArrayCopy();
    }

}
