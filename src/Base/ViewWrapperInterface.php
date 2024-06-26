<?php

namespace Drupal\zero_entitywrapper\Base;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\zero_entitywrapper\Content\ContentWrapperCollection;
use Drupal\zero_entitywrapper\View\ViewFilterWrapper;
use Drupal\zero_entitywrapper\View\ViewSortWrapper;

interface ViewWrapperInterface extends BaseWrapperInterface {

  /**
   * Set the fixed state, the wrapper will throw an exception when a "set" method will be called in fixed state.
   *
   * @param bool $fixed
   * @returns self
   */
  public function setFixed(bool $fixed): self;

  /**
   * get the fixed state
   *
   * @returns bool
   */
  public function getFixed(): bool;

  /**
   * Get view executable
   *
   * @return ViewExecutable
   */
  public function executable(): ViewExecutable;

  /**
   * Get the administrative label of the view
   * 
   * @return String
   */
  public function getLabel(): string;

  /**
   * Get the display title of the view.
   * Make sure you have set the display before using this method.
   * 
   * @return String
   */
  public function getTitle(): string;

  /**
   * Set the pager config
   *
   * @param array $config = [
   *     'page' => 10,
   *     'items' => 20,
   *     'offset' => 0,
   * ]
   * @return self
   */
  public function setPagerConfig(array $config): self;

  /**
   * Set the display
   *
   * @param string|NULL $display
   * @return self
   */
  public function setDisplay(string $display = NULL): self;

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
   * @return self
   */
  public function setFullPager(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): self;

  /**
   * Set the pager to show all items pager
   *
   * @param int|NULL $offset
   * @return self
   */
  public function setShowAllPager(int $offset = NULL): self;

  /**
   * Set the pager range
   *
   * @param int|NULL $itemsPerPage
   * @param int|NULL $page
   * @param int|NULL $offset
   * @return self
   */
  public function setRange(int $itemsPerPage = NULL, int $page = NULL, int $offset = NULL): self;

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
   */
  public function getContentResults(): ContentWrapperCollection;

  /**
   * Get the result of the view as ContentWrapperCollection
   *
   * @param bool $returnArray return always an array after the first collection method
   * @return ContentWrapperInterface|ContentWrapperInterface[]|ContentWrapperCollection
   */
  public function getContentResultsCollection(bool $returnArray = FALSE): ContentWrapperCollection;

  /**
   * Set the language code for the results array used by getContentResults()
   *
   * @see ViewWrapperInterface::getContentResults()
   * @see ViewWrapperInterface::getContentResultsCollection()
   *
   * @param LanguageInterface|string $language
   * @return self
   */
  public function setResultLanguage($language = NULL): self;

  /**
   * Get the language code for the results array used by getContentResults()
   *
   * @see ViewWrapperInterface::getContentResults()
   * @see ViewWrapperInterface::getContentResultsCollection()
   *
   * @return string|null
   */
  public function getResultLanguage(): ?string;

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
   * @return self
   */
  public function setArgs(array $args): self;

  /**
   * Set the exposed input
   *
   * @param array $input
   * @return self
   */
  public function setExposedInput(array $input): self;

  /**
   * Get the exposed input of the view
   *
   * @return array
   */
  public function getExposedInput(): array;

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
   * @return self
   */
  public function removeHandler(string $type, $table = NULL, string $field = NULL): self;

  /**
   * Remove a filter for the execution
   *
   * @param string|callable|null $table
   * @param string|null $field
   *
   * @return self
   */
  public function removeFilter($table = NULL, string $field = NULL): self;

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
   * @return self
   */
  public function removeSort($table = NULL, string $field = NULL): self;

  /**
   * Add a sort operation
   *
   * @param string $table
   * @param string $field
   *
   * @return ViewSortWrapper
   */
  public function addSort(string $table, string $field): ViewSortWrapper;

  /**
   * Get a render element to show an exposed sort field
   *
   * @param string $type
   * @param array $options = [
   *   'ignore' => ['title__ASC'],
   * ]
   *
   * @return array
   */
  public function getSortInput(string $type = 'links', array $options = []): array;

  /**
   * Get a URL from the view display if possible.
   *
   * @param array $options
   *
   * @return Url|null
   */
  public function url(array $options = []): ?Url;

  /**
   * Get a Link from the view display if possible.
   *
   * @param string $text
   * @param array $options
   *
   * @return Link|null
   */
  public function link(string $text, array $options = []): ?Link;

  /**
   * Get the display option
   *
   * @param string $option
   *
   * @return mixed
   */
  public function getDisplayOption(string $option);

  /**
   * Get the count of the current view query.
   * 
   * @see ViewWrapperInterface::getSelect()
   * @see ViewWrapperInterface::reset()
   * 
   * @return int
   */
  public function getSelectCount(): int;

  /**
   * Get a select query from current view. Make sure to use reset for multi invokation.
   * 
   * @see ViewWrapperInterface::reset()
   * 
   * @return SelectInterface
   */
  public function getSelect(): SelectInterface;

  /**
   * Reset the view executable. Only the display will be reapplied, other configs will be removed.
   * 
   * @return self
   */
  public function reset(): self;

}
