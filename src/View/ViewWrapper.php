<?php

namespace Drupal\zero_entitywrapper\View;

use Drupal\views\ResultRow;
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
  }

  /**
   * @return ViewEntityInterface
   * @noinspection PhpIncompatibleReturnTypeInspection
   */
  public function entity() {
    return $this->entity;
  }

  public function executable(): ViewExecutable {
    if ($this->executable === NULL) {
      $this->executable = $this->entity()->getExecutable();
    }
    return $this->executable;
  }

  public function setPagerConfig(array $config): ViewWrapper {
    if (isset($config['page'])) $this->executable()->setCurrentPage($config['page']);
    if (isset($config['items'])) $this->executable()->setItemsPerPage($config['items']);
    if (isset($config['offset'])) $this->executable()->setOffset($config['offset']);
    return $this;
  }

  public function setDisplay(string $display = NULL): ViewWrapper {
    if ($display !== NULL) {
      $this->executable()->setDisplay($display);
    }
    return $this;
  }

  public function getDisplay(): string {
    return $this->executable()->current_display;
  }

  public function setFullPager(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): ViewWrapper {
    $pager = $this->executable()->getDisplay()->getOption('pager');
    $pager['type'] = 'full';
    $this->executable()->getDisplay()->setOption('pager', $pager);
    return $this->setRange($itemsPerPage, $page, $offset);
  }

  public function setRange(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): ViewWrapper {
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
   * @return ResultRow[]
   */
  public function getResults(): array {
    return $this->executed()->result;
  }

  /**
   * @return ContentWrapper|ContentWrapper[]
   * @noinspection PhpParamsInspection
   */
  public function getContentResults(): ContentWrapperCollection {
    $results = [];
    foreach ($this->getResults() as $row) {
      $results[] = ContentWrapper::create($row->_entity, $this);
    }
    return new ContentWrapperCollection($results, ['message' => 'Please use method <code>getContentResultsCollection()</code> instead of <code>getContentResults()</code> to use collection features.', 'lines' => ['Collection support will be removed at version 1.0.0']]);
  }

  public function getContentResultsCollection(): ContentWrapperCollection {
    $results = [];
    foreach ($this->getResults() as $row) {
      $results[] = ContentWrapper::create($row->_entity, $this);
    }
    return new ContentWrapperCollection($results);
  }

  public function getTotalItems(): int {
    return (int)$this->executed()->getPager()->getTotalItems();
  }

  public function getOffset(): int {
    return (int)$this->executable()->getOffset();
  }

  public function getItemsPerPage(): int {
    return (int)$this->executable()->getItemsPerPage();
  }

  public function getCurrentPage(): int {
    return (int)$this->executable()->getCurrentPage();
  }

  /**
   * @return array = [
   *     'offset' => 0,
   *     'items' => 0,
   *     'total' => 0,
   *     'current' => 0,
   *     'total_pages' => 0,
   *     'remain' => 0,
   * ]
   */
  public function getResultMeta(): array {
    $meta = [
      'offset' => $this->getOffset(),
      'items' => $this->getItemsPerPage(),
      'total' => $this->getTotalItems(),
      'current' => $this->getCurrentPage(),
      'page' => $this->getCurrentPage(),
    ];
    $meta['total_pages'] = ceil($meta['total'] / $meta['items']);
    $meta['remain'] = $meta['total'] - $meta['items'] * ($meta['current'] + 1);
    return $meta;
  }

  public function setArgs(array $args): ViewWrapper {
    $this->executable()->setArguments($args);
    return $this;
  }

  public function setExposedInput(array $input): ViewWrapper {
    $this->executable()->setExposedInput($input);
    return $this;
  }

  public function render(string $display = NULL, array $options = []): array {
    return $this->executable()->preview($display);
  }

  /**
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return callable
   */
  private function ensureTableFieldFilter($table = NULL, $field = NULL): callable {
    if (is_callable($table)) return $table;
    return function($handler) use ($table, $field) {
      if ($table !== NULL && $handler['table'] !== $table) return FALSE;
      if ($field !== NULL && $handler['field'] !== $field) return FALSE;
      return TRUE;
    };
  }

  /**
   * @param string $type
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeHandler(string $type, $table = NULL, string $field = NULL): ViewWrapper {
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
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeFilter($table = NULL, string $field = NULL): ViewWrapper {
    return $this->removeHandler('filter', $table, $field);
  }

  public function addFilter(string $table, string $field): ViewFilterWrapper {
    return new ViewFilterWrapper($this, $table, $field);
  }

  /**
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeSort($table = NULL, string $field = NULL): ViewWrapper {
    return $this->removeHandler('sort', $table, $field);
  }

  public function addSort(string $table, string $field): ViewSortWrapper {
    return new ViewSortWrapper($this, $table, $field);
  }

}
