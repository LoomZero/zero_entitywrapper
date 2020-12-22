<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperExtensionInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;
use Drupal\zero_entitywrapper\Service\WrapperExtenderManager;

abstract class BaseWrapper implements BaseWrapperInterface {

  /** @var WrapperExtenderManager */
  private $extenderManager;
  /** @var EntityInterface */
  protected $entity;
  /** @var array */
  protected $vars;
  /** @var BaseWrapper */
  protected $parent;
  /** @var BaseWrapperExtensionInterface[] */
  protected $extenders = [];

  /**
   * @param EntityInterface|string $entity_type
   * @param string|int|null $entity_id
   */
  public function __construct($entity_type, $entity_id = NULL) {
    if ($entity_type instanceof EntityInterface) {
      $this->entity = $entity_type;
    } else {
      $this->entity = Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
  }

  public function type(): string {
    return $this->entity()->getEntityTypeId();
  }

  public function bundle(): string {
    return $this->entity()->bundle();
  }

  public function id() {
    return $this->entity()->id();
  }

  public function extendPreprocess(string $template) {
    WrapperHelper::extendPreprocess($this, $template);
  }

  public function render(string $view_mode = 'full', array $options = []): array {
    return Drupal::entityTypeManager()
      ->getViewBuilder($this->type())
      ->view($this->entity(), $view_mode);
  }

  public function setRenderContext(array &$vars = NULL) {
    if ($vars !== NULL) {
      $this->vars = &$vars;
    }
  }

  public function &getRenderContext(): ?array {
    if ($this->parent === NULL) {
      return $this->vars;
    } else {
      return $this->root()->getRenderContext();
    }
  }

  public function renderContext(): RenderContextWrapperInterface {
    /** @var RenderContextWrapperInterface $extension */
    $extension = $this->getExtension('render_context');
    return $extension;
  }

  public function setParent(BaseWrapperInterface $parent = NULL) {
    $this->parent = $parent;
  }

  public function parent(): ?BaseWrapperInterface {
    return $this->parent;
  }

  /**
   * @return BaseWrapperInterface
   */
  public function root(): BaseWrapperInterface {
    $root = $this;
    while ($root->parent !== NULL) {
      $root = $root->parent;
    }
    return $root;
  }

  /**
   * @return array<string, int>
   */
  public function getEntityMeta(): array {
    return [
      'entity_type' => $this->type(),
      'entity_bundle' => $this->bundle(),
      'entity_id' => $this->id(),
    ];
  }

  public function getExtension(string $name, ...$args): BaseWrapperExtensionInterface {
    if ($this->extenderManager === NULL) {
      $this->extenderManager = Drupal::service('zero.entitywrapper.extender');
    }
    if (!isset($this->extenders[$name])) {
      $extension = $this->extenderManager->getExtension($this, $name, $args);
      if ($extension->cachable()) {
        $this->extenders[$name] = $extension;
      } else {
        return $extension;
      }
    }
    return $this->extenders[$name];
  }

}