<?php

/**
 * @var array $vars
 * @var string $vars['type']
 * @var \Drupal\zero_entitywrapper\Base\ViewWrapperInterface $vars['view']
 * @var array $vars['options']
 */

/** @var \Drupal\zero_entitywrapper\Base\ViewWrapperInterface $view */
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

$view = $vars['view'];

$exposed_options = $view->getDisplayOption('exposed_form');
$exposed = $view->getExposedInput() + [
  'sort_by' => '',
  'sort_order' => '',
];

$factory = function(array $sort, string $order) use ($exposed): array {
  $url = Url::createFromRequest(Drupal::request());
  $query = $exposed;
  $query['sort_order'] = $order;
  $query['sort_by'] = $sort['expose']['field_identifier'];
  $url->setOption('query', $query);
  return [
    'identifier' => $sort['expose']['field_identifier'],
    'id' => $sort['expose']['field_identifier'] . '__' . $order,
    'text' => $sort['expose']['label'],
    'url' => $url,
    'sort' => $sort,
    'order' => $order,
    'attributes' => new Attribute([
      'class' => [
        'view-wrapper-sort__id-' . $sort['expose']['field_identifier'],
        'view-wrapper-sort--order-' . strtolower($order),
      ],
    ]),
  ];
};

// create the links
$vars['links'] = [];
foreach ($vars['view']->executable()->getHandlers('sort', $vars['view']->getDisplay()) as $sort) {
  // only use exposed sort handler
  if (!$sort['exposed']) continue;

  // generate the links
  $asc = $factory($sort, 'ASC');
  $desc = $factory($sort, 'DESC');

  // order the links after default order of handler
  if ($sort['order'] === 'ASC') {
    $vars['links'][$asc['id']] = $asc;
    $vars['links'][$desc['id']] = $desc;
  } else {
    $vars['links'][$desc['id']] = $desc;
    $vars['links'][$asc['id']] = $asc;
  }
}

// remove ignored links
if (!empty($vars['options']['ignore'])) {
  foreach ($vars['links'] as $id => $link) {
    if (in_array($id, $vars['options']['ignore'])) {
      unset($vars['links'][$id]);
    }
  }
}

// set the current link
$is_default = FALSE;
$sort_id = $exposed['sort_by'] . '__' . $exposed['sort_order'];
if (!isset($vars['links'][$sort_id])) {
  $is_default = TRUE;
  $sort_id = array_key_first($vars['links']);
}
$vars['current'] = $vars['links'][$sort_id];
unset($vars['links'][$sort_id]);

// create reset link
if (!$is_default) {
  $url = Url::createFromRequest(Drupal::request());
  $query = $exposed;
  unset($query['sort_by'], $query['sort_order']);
  $url->setOption('query', $query);
  $vars['reset'] = $url;
}
