<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\zero_entitywrapper\Base\EntityWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;

abstract class BaseWrapper implements EntityWrapperInterface, RenderContextWrapperInterface {

  /** @var EntityInterface */
  protected $entity;
  /** @var array */
  protected $vars;
  /** @var RenderContextWrapper */
  protected $renderContext;
  /** @var BaseWrapper */
  protected $parent;

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

  public function render(string $view_mode = 'full'): array {
    return Drupal::entityTypeManager()
      ->getViewBuilder($this->type())
      ->view($this->entity(), $view_mode);
  }

  public function setRenderContext(array &$vars = NULL) {
    if ($vars !== NULL) {
      $this->vars = &$vars;
    }
  }

  public function renderContext(): RenderContextWrapper {
    $root = $this->root();
    if ($root->renderContext === NULL) {
      $root->renderContext = new RenderContextWrapper($root->vars);
    }
    return $root->renderContext;
  }

  /**
   * @param BaseWrapper $parent
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * @return BaseWrapper
   */
  public function parent() {
    return $this->parent;
  }

  /**
   * @return BaseWrapper
   */
  public function root() {
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

}