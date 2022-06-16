<?php

namespace Drupal\zero_entitywrapper\View;

use Drupal;
use Drupal\Core\Language\LanguageInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\zero_entitywrapper\Base\BaseWrapperInterface;
use Drupal\zero_entitywrapper\Base\ViewWrapperInterface;
use Drupal\zero_entitywrapper\Content\ContentWrapper;
use Drupal\zero_entitywrapper\Content\ContentWrapperCollection;
use Drupal\zero_entitywrapper\Wrapper\BaseWrapper;

class ViewWrapper extends BaseWrapper implements ViewWrapperInterface {

  /** @var ViewExecutable */
  private $executable;
  /** @var string */
  private $resultLangcode = NULL;

  public static function create(string $value, BaseWrapperInterface $parent = NULL): ViewWrapper {
    [ $view, $display ] = explode(':', $value);
    return new ViewWrapper($view, $display, $parent);
  }

  /**
   * @param string|ViewExecutable|ViewEntityInterface $entity
   * @param string|null $display
   */
  public function __construct($entity, string $display = NULL, BaseWrapperInterface $parent = NULL) {
    if ($entity instanceof ViewExecutable) {
      $this->executable = $entity;
      $entity = $entity->storage;
    }
    if (is_string($entity)) {
      parent::__construct('view', $entity);
    } else {
      parent::__construct($entity);
    }
    $this->setDisplay($display);
    $this->setParent($parent);
    if ($parent === NULL) {
      $this->setResultLanguage();
    } else {
      $this->setResultLanguage($parent->language());
    }
  }

  /**
   * @inheritDoc
   */
  public function executable(): ViewExecutable {
    if ($this->executable === NULL) {
      $this->executable = $this->entity()->getExecutable();
    }
    return $this->executable;
  }

  /**
   * @inheritDoc
   */
  public function setPagerConfig(array $config): self {
    if (isset($config['page'])) $this->executable()->setCurrentPage($config['page']);
    if (isset($config['items'])) $this->executable()->setItemsPerPage($config['items']);
    if (isset($config['offset'])) $this->executable()->setOffset($config['offset']);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setDisplay(string $display = NULL): self {
    if ($display !== NULL) {
      $this->executable()->setDisplay($display);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDisplay(): string {
    return $this->executable()->current_display;
  }

  /**
   * @inheritDoc
   */
  public function setFullPager(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): self {
    $pager = $this->executable()->getDisplay()->getOption('pager');
    $pager['type'] = 'full';
    $this->executable()->getDisplay()->setOption('pager', $pager);
    return $this->setRange($itemsPerPage, $page, $offset);
  }

  /**
   * @inheritDoc
   */
  public function setRange(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): self {
    if ($itemsPerPage !== NULL) $this->executable()->setItemsPerPage($itemsPerPage);
    if ($page !== NULL) $this->executable()->setCurrentPage($page);
    if ($offset !== NULL) $this->executable()->setOffset($offset);
    return $this;
  }

  private function executed(): ViewExecutable {
    if (!$this->executable()->executed) {
      $this->executable()->execute();
    }
    return $this->executable();
  }

  /**
   * @inheritDoc
   */
  public function getResults(): array {
    return $this->executed()->result;
  }

  /**
   * @inheritDoc
   * @noinspection PhpParamsInspection
   */
  public function getContentResults(): ContentWrapperCollection {
    $results = [];
    foreach ($this->getResults() as $row) {
      $results[] = ContentWrapper::create($row->_entity, $this)
        ->setLanguage($this->getResultLanguage());
    }
    return new ContentWrapperCollection($results, ['message' => 'Please use method <code>getContentResultsCollection()</code> instead of <code>getContentResults()</code> to use collection features.', 'lines' => ['Collection support will be removed at version 1.0.0']]);
  }

  /**
   * @inheritDoc
   * @noinspection PhpParamsInspection
   */
  public function getContentResultsCollection(): ContentWrapperCollection {
    $results = [];
    foreach ($this->getResults() as $row) {
      $results[] = ContentWrapper::create($row->_entity, $this)
        ->setLanguage($this->getResultLanguage());
    }
    return new ContentWrapperCollection($results);
  }

  /**
   * @inheritDoc
   */
  public function setResultLanguage($language = NULL): self {
    if ($language === NULL) $language = Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($language instanceof LanguageInterface) {
      $this->resultLangcode = $language->getId();
    } else {
      $this->resultLangcode = $language;
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getResultLanguage(): ?string {
    return $this->resultLangcode;
  }

  /**
   * @inheritDoc
   */
  public function getTotalItems(): int {
    return (int)$this->executed()->getPager()->getTotalItems();
  }

  /**
   * @inheritDoc
   */
  public function getOffset(): int {
    return (int)$this->executable()->getOffset();
  }

  /**
   * @inheritDoc
   */
  public function getItemsPerPage(): int {
    return (int)$this->executable()->getItemsPerPage();
  }

  /**
   * @inheritDoc
   */
  public function getCurrentPage(): int {
    return (int)$this->executable()->getCurrentPage();
  }

  /**
   * @inheritDoc
   */
  public function getResultMeta(): array {
    $meta = [
      'offset' => $this->getOffset(),
      'items' => $this->getItemsPerPage(),
      'total' => $this->getTotalItems(),
      'current' => $this->getCurrentPage(),
      'page' => $this->getCurrentPage(),
    ];
    $meta['total_pages'] = (int)ceil($meta['total'] / $meta['items']);
    $meta['remain'] = $meta['total'] - $meta['items'] * ($meta['current'] + 1);
    return $meta;
  }

  /**
   * @inheritDoc
   */
  public function setArgs(array $args): self {
    $this->executable()->setArguments($args);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setExposedInput(array $input): self {
    $this->executable()->setExposedInput($input);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function render(string $display = NULL, array $options = []): array {
    return $this->executable()->preview($display);
  }

  private function ensureTableFieldFilter($table = NULL, $field = NULL): callable {
    if (is_callable($table)) return $table;
    return function($handler) use ($table, $field) {
      if ($table !== NULL && $handler['table'] !== $table) return FALSE;
      if ($field !== NULL && $handler['field'] !== $field) return FALSE;
      return TRUE;
    };
  }

  /**
   * @inheritDoc
   */
  public function removeHandler(string $type, $table = NULL, string $field = NULL): self {
    $function = $this->ensureTableFieldFilter($table, $field);

    $handlers = $this->executable()->getHandlers($type);
    foreach ($handlers as $handler) {
      if ($function($handler)) {
        $this->executable()->removeHandler($this->getDisplay(), $type, $handler['id']);
      }
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeFilter($table = NULL, string $field = NULL): self {
    return $this->removeHandler('filter', $table, $field);
  }

  /**
   * @inheritDoc
   */
  public function addFilter(string $table, string $field): ViewFilterWrapper {
    return new ViewFilterWrapper($this, $table, $field);
  }

  /**
   * @inheritDoc
   */
  public function removeSort($table = NULL, string $field = NULL): self {
    return $this->removeHandler('sort', $table, $field);
  }

  /**
   * @inheritDoc
   */
  public function addSort(string $table, string $field): ViewSortWrapper {
    return new ViewSortWrapper($this, $table, $field);
  }

}
