<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\zero_entitywrapper\Content\ContentWrapperCollection;
use Drupal\zero_entitywrapper\View\ViewFilterWrapper;
use Drupal\zero_entitywrapper\View\ViewSortWrapper;
use Drupal\zero_entitywrapper\View\ViewWrapper;

interface ViewWrapperInterface extends BaseWrapperInterface {

  /**
   * Get view executable
   *
   * @return ViewExecutable
   */
  public function executable(): ViewExecutable;

  /**
   * Set the pager config
   *
   * @param array $config = [
   *     'page' => 10,
   *     'items' => 20,
   *     'offset' => 0,
   * ]
   * @return ViewWrapper
   */
  public function setPagerConfig(array $config): ViewWrapperInterface;

  /**
   * Set the display
   *
   * @param string|NULL $display
   * @return $this
   */
  public function setDisplay(string $display = NULL): ViewWrapperInterface;

  /**
   * Get the name of the current display
   *
   * @return string
   */
  public function getDisplay(): string;

  /**
   * Set the pager to full pager
   *
   * @param int|NULL $itemsPerPage
   * @param int|NULL $page
   * @param int|NULL $offset
   * @return $this
   */
  public function setFullPager(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): ViewWrapperInterface;

  /**
   * Set the pager range
   *
   * @param int|NULL $itemsPerPage
   * @param int|NULL $page
   * @param int|NULL $offset
   * @return $this
   */
  public function setRange(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): ViewWrapperInterface;

  /**
   * Get the result of the view
   *
   * @return ResultRow[]
   */
  public function getResults(): array;

  /**
   * Get the result of the view as ContentWrapperInterface
   *
   * @return ContentWrapperInterface|ContentWrapperInterface[]|ContentWrapperCollection
   * @noinspection PhpParamsInspection
   */
  public function getContentResults(): ContentWrapperCollection;

  /**
   * Get the result of the view as ContentWrapperCollection
   *
   * @return ContentWrapperCollection
   */
  public function getContentResultsCollection(): ContentWrapperCollection;

  /**
   * Get the total number of items this view will have
   *
   * @return int
   */
  public function getTotalItems(): int;

  /**
   * Get the current offset
   *
   * @return int
   */
  public function getOffset(): int;

  /**
   * Get the items per page
   *
   * @return int
   */
  public function getItemsPerPage(): int;

  /**
   * Get the current page
   *
   * @return int
   */
  public function getCurrentPage(): int;

  /**
   * Get the result meta data
   *
   * @return array = [
   *     'offset' => 0,
   *     'items' => 0,
   *     'total' => 0,
   *     'current' => 0,
   *     'total_pages' => 0,
   *     'remain' => 0,
   * ]
   */
  public function getResultMeta(): array;

  /**
   * Set the view arguments
   *
   * @param array $args
   * @return $this
   */
  public function setArgs(array $args): ViewWrapperInterface;

  /**
   * Set the exposed input
   *
   * @param array $input
   * @return $this
   */
  public function setExposedInput(array $input): ViewWrapperInterface;

  /**
   * Render the view with display
   *
   * @see ViewExecutable::preview()
   *
   * @param string|NULL $display
   * @param array $options
   * @return array
   */
  public function render(string $display = NULL, array $options = []): array;

  /**
   * Remove a handler for the execution
   *
   * @param string $type
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeHandler(string $type, $table = NULL, string $field = NULL): ViewWrapperInterface;

  /**
   * Remove a filter for the execution
   *
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeFilter($table = NULL, string $field = NULL): ViewWrapperInterface;

  /**
   * Add a filter for the execution
   *
   * @param string $table
   * @param string $field
   * @return ViewFilterWrapper
   */
  public function addFilter(string $table, string $field): ViewFilterWrapper;

  /**
   * Remove a sort handler for the execution
   *
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return $this
   */
  public function removeSort($table = NULL, string $field = NULL): ViewWrapperInterface;

  /**
   * Add a sort operation
   *
   * @param string $table
   * @param string $field
   *
   * @return ViewSortWrapper
   */
  public function addSort(string $table, string $field): ViewSortWrapper;

}
