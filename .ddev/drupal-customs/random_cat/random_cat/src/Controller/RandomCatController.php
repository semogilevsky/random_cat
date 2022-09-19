<?php

namespace Drupal\random_cat\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Controller for CAT API.
 */
class RandomCatController extends ControllerBase {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * RandomCatController constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager service.
   */
  public function __construct(ClientInterface $http_client, SessionManagerInterface $session_manager) {
    $this->httpClient = $http_client;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('session_manager')
    );
  }

  /**
   * Get random cat url.
   *
   * @return string
   *   The random cat url.
   */
  public function getCatImage(string $id) {
    $data = $this->getCatData($id);

    return new JsonResponse($data, 200, []);
  }

  /**
   * Vote for cat.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Request result.
   */
  public function vote(string $cat_id, int $value) {
    if (empty($cat_id)) {
      return NULL;
    }

    $sub_id = $this->sessionManager->getId();

    $params = [
      'json' => [
        'image_id' => $cat_id,
        'sub_id' => $sub_id,
        'value' => $value,
      ],
      'headers' => [
        'x-api-key' => RC_API_KEY,
      ],
    ];

    $res = $this->httpClient->post(RC_API_ENDPOINT . '/votes', $params);

    if (!in_array($res->getStatusCode(), [200, 201])) {
      return NULL;
    }

    $count = $this->getVotesBySubId($sub_id, $cat_id);

    $resposne = new AjaxResponse();

    $resposne->addCommand(new HtmlCommand(
      '#rc-vote-result',
      $this->t('Vote is counted! Scores - %count', ['%count' => $count]))
    );

    return $resposne;
  }

  /**
   * Returns URL of a random cat picture.
   */
  private function getCatData($id = '') {
    $params = [
      'query' => [
        'limit' => 1,
        'size' => 'small',
      ],
      'headers' => [
        'x-api-key' => RC_API_KEY,
      ],
    ];

    if (!empty($id)) {
      $params['query']['image_id'] = $id;
    }

    $response = $this->httpClient->get(RC_API_ENDPOINT . '/images/search', $params);
    $data = (string) $response->getBody();
    $data = Json::decode($data);

    if (empty($data[0])) {
      return NULL;
    }

    $data = reset($data);

    if (empty($data['url'])) {
      return NULL;
    }

    return ['url' => $data['url'], 'id' => $data['id']];
  }

  /**
   * Returns votes count for given sub_id(user id) and cat id.
   */
  private function getVotesBySubId(string $sub_id, string $cat_id) {
    if (empty($sub_id) || empty($cat_id)) {
      return 0;
    }

    $page = 0;
    $params = [
      'query' => [
        'limit' => 50,
      ],
      'headers' => [
        'x-api-key' => RC_API_KEY,
      ],
    ];

    $count = 0;
    do {
      $params['query']['page'] = $page;

      $response = $this->httpClient->get(RC_API_ENDPOINT . '/votes', $params);
      $data = (string) $response->getBody();
      $data = Json::decode($data);

      foreach ($data as $vote) {
        if ($vote['image_id'] == $cat_id) {
          $count += $vote['value'];
        }
      }

      $page++;
    } while ($data);

    return $count;
  }

}
