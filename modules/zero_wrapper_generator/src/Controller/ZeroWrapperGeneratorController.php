<?php

namespace Drupal\zero_wrapper_generator\Controller;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\loom_ajax\Ajax\LOOMAjaxCommand;
use Drupal\zero_wrapper_generator\Service\ZeroWrapperGeneratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ZeroWrapperGeneratorController extends ControllerBase {

  public function serve() {
    /** @var ZeroWrapperGeneratorService $generator */
    $generator = \Drupal::service('zero_wrapper_generator.service');

    $fields = $generator->getFieldList(Drupal::request()->get('entity_type'), Drupal::request()->get('bundle'));
    try {
      return $this->response(['content' => $generator->getGeneratedFile($fields)->preprocess()->getContent()], []);
    } catch (Exception $exception) {
      return $this->error($exception->getMessage(), $exception->getCode());
    }
  }

  public function error(string $message, int $code = 500): JsonResponse {
    return $this->response([
      'message' => $message,
    ], [
      'error' => TRUE,
      'code' => $code,
    ]);
  }

  protected function response($data = [], $meta = [], int $code = 200): JsonResponse {
    if (!isset($meta['error'])) $meta['error'] = FALSE;

    return new CacheableJsonResponse(['data' => $data, 'meta' => $meta], $code);
  }

}
