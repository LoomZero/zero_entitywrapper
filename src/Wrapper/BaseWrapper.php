<?php
/** @noinspection PhpParamsInspection */

namespace Drupal\zero_entitywrapper\Wrapper;

use Drupal;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\zero_entitywrapper\Base\BaseWrapperExtensionInterface;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\RenderContextWrapperInterface;
use Drupal\zero_entitywrapper\Helper\WrapperHelper;
use Drupal\zero_entitywrapper\Service\WrapperExtenderManager;
use Drupal\zero_entitywrapper\Service\EntitywrapperService;
use Drupal\zero_entitywrapper\Exception\EntityWrapperException;
use Drupal\zero_entitywrapper\Service\WrapperExtenderPluginManager;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseWrapper implements BaseWrapperInterface {

  /** @var WrapperExtenderPluginManager */
  private $extenderManager;
  /** @var EntityInterface */
  protected $entity;
  /** @var array */
  protected $vars;
  /** @var BaseWrapperInterface */
  protected $parent;
  /** @var BaseWrapperExtensionInterface[] */
  protected $extenders = [];
  /** @var array */
  protected $configs = [];
  /** @var EntitywrapperService */
  protected $service = NULL;

  /**
   * Get link data render ready
   *
   * @param ?Link $link
   *
   * @return array = [
   *     'text' => $link->getText(),
   *     'url' => $link->getUrl()->toString(),
   *     'options' => [
   *        'attributes' => [':name' => 'value'],
   *     ],
   *     'attributes' => new Attributes($options['attributes'] ?? []),
   * ]
   */
  public static function extractLinkData(Link $link = NULL): array {
    if (empty($link)) return [];

    $options = $link->getUrl()->getOptions();

    return [
      'text' => $link->getText(),
      'url' => $link->getUrl()->toString(),
      'options' => $options,
      'attributes' => new Attribute($options['attributes'] ?? []),
    ];
  }

  /**
   * @param EntityInterface|string $entity_type
   * @param string|int|null $entity_id
   */
  public function __construct($entity_type, $entity_id = NULL) {
    if ($entity_type instanceof EntityInterface) {
      $this->entity = $entity_type;
    } else {
      $this->entity = Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      if ($this->entity === NULL) {
        throw new EntityWrapperException('Could not load entity ' . $entity_type . ' with id ' . $entity_id);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function entity(): EntityInterface {
    return $this->entity;
  }

  /**
   * @inheritDoc
   */
  public function getService(): EntitywrapperService {
    if ($this->service === NULL) {
      $this->service = Drupal::service('zero_entitywrapper.service');
    }
    return $this->service;
  }

  /**
   * @inheritDoc
   */
  public function getConfig(string $config) {
    return $this->configs[$config] ?? NULL;
  }

  /**
   * @inheritDoc
   */
  public function getConfigs(): array {
    return $this->configs;
  }

  /**
   * @inheritDoc
   */
  public function setConfig(string $config, $value = TRUE): self {
    $this->configs[$config] = $value;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setConfigs(array $configs): self {
    $this->configs = $configs;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function type(): string {
    return $this->entity()->getEntityTypeId();
  }

  /**
   * @inheritDoc
   */
  public function bundle(): string {
    return $this->entity()->bundle();
  }

  /**
   * @inheritDoc
   */
  public function getBundle(): ConfigEntityBundleBase {
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return Drupal::entityTypeManager()
      ->getStorage($this->entity()->getEntityType()->get('bundle_entity_type'))
      ->load($this->bundle());
  }

  /**
   * @inheritDoc
   */
  public function id() {
    return $this->entity()->id();
  }

  /**
   * @inheritDoc
   */
  public function extendPreprocess(string $template): self {
    WrapperHelper::extendPreprocess($this, $template);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function render(string $view_mode = 'full', array $options = []): array {
    $langcode = $this->langcode();
    if (isset($options['langcode'])) $langcode = $options['langcode'];

    return Drupal::entityTypeManager()
      ->getViewBuilder($this->type())
      ->view($this->entity(), WrapperHelper::checkViewMode($view_mode), $langcode);
  }

  /**
   * @inheritDoc
   */
  public function setRenderContext(array &$vars = NULL): self {
    if ($vars !== NULL) {
      $this->vars = &$vars;
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function &getRenderContext(): ?array {
    if ($this->parent === NULL) {
      return $this->vars;
    } else {
      return $this->root()->getRenderContext();
    }
  }

  /**
   * @inheritDoc
   */
  public function renderContext(): RenderContextWrapperInterface {
    /** @var RenderContextWrapperInterface $extension */
    $extension = $this->getExtension('render_context');
    return $extension;
  }

  /**
   * @inheritDoc
   */
  public function setParent(BaseWrapperInterface $parent = NULL): self {
    $this->parent = $parent;
    if ($parent !== NULL) {
      $this->setConfigs($parent->getConfigs());
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function parent(): ?BaseWrapperInterface {
    return $this->parent;
  }

  /**
   * @inheritDoc
   */
  public function root(): BaseWrapperInterface {
    $root = $this;
    while ($root->parent !== NULL) {
      $root = $root->parent;
    }
    return $root;
  }

  /**
   * @inheritDoc
   */
  public function getEntityMeta(): array {
    return [
      'entity_type' => $this->type(),
      'entity_bundle' => $this->bundle(),
      'entity_id' => $this->id(),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getExtension(string $name, ...$args): BaseWrapperExtensionInterface {
    if ($this->extenderManager === NULL) {
      $this->extenderManager = Drupal::service('plugin.manager.wrapper_extender');
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

  /**
   * @inheritDoc
   */
  public function language(): LanguageInterface {
    return $this->entity->language();
  }

  /**
   * @inheritDoc
   */
  public function langcode(): ?string {
    return $this->language()->getId();
  }

  /**
   * @inheritDoc
   */
  public function setLanguage($language): self {
    if ($this->entity instanceof TranslatableInterface && $this->entity->isTranslatable()) {
      if ($language instanceof LanguageInterface) $language = $language->getId();
      if ($this->entity->hasTranslation($language)) {
        $this->entity = $this->entity->getTranslation($language);
      }
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setCurrentLanguage(): self {
    return $this->setLanguage(Drupal::languageManager()->getCurrentLanguage());
  }

  /**
   * @inheritDoc
   */
  public function getMultiSite(Request $request = NULL): string {
    return WrapperHelper::getMultiSite($request);
  }

}
