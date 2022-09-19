<?php

namespace Drupal\random_cat\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

/**
 * Search cat by breed form.
 */
class SearchCatByBreedForm extends FormBase {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SearchCatByBreedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ClientInterface $http_client, RendererInterface $renderer) {
    $this->httpClient = $http_client;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_cat_by_breed';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $breeds = $this->getBreedsList();

    $form['breeds'] = [
      '#type' => 'select',
      '#title' => $this->t("Select cat's breed"),
      '#options' => $breeds,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::getCatsByBreed',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'search-results',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Searching for cats ...'),
        ],
      ],
      '#suffix' => '<div id="search-results"></div>',
    ];

    $form['#attached'] = [
      'library' => ['random_cat/rabdom_cat.core'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Returns cats breeds list.
   */
  private function getBreedsList() {
    $params = [
      'headers' => [
        'x-api-key' => RC_API_KEY,
      ],
    ];

    $response = $this->httpClient->get(RC_API_ENDPOINT . '/breeds', $params);
    $data = (string) $response->getBody();
    $data = Json::decode($data);

    if (empty($data)) {
      return [];
    }

    $options = [];

    foreach ($data as $breed) {
      if (empty($breed['id']) || empty($breed['name'])) {
        continue;
      }

      $options[$breed['id']] = $breed['name'];
    }

    return $options;
  }

  /**
   * Returns cats breeds list.
   */
  public function getCatsByBreed(array &$form, FormStateInterface $form_state) {
    $breed = $form_state->getValue('breeds');
    if (empty($breed)) {
      return;
    }

    $params = [
      'query' => [
        'limit' => 3,
        'size' => 'small',
        'breed_id' => $breed,
      ],
      'headers' => [
        'x-api-key' => RC_API_KEY,
      ],
    ];

    $response = $this->httpClient->get(RC_API_ENDPOINT . '/images/search', $params);
    $data = (string) $response->getBody();
    $data = Json::decode($data);

    if (empty($data)) {
      return [];
    }

    $template_vars = [
      '#image_ids' => [],
    ];

    foreach ($data as $image) {
      if (empty($template_vars['#name']) && !empty($image['breeds'][0]['name'])) {
        $template_vars['#name'] = $image['breeds'][0]['name'];
      }
      if (empty($template_vars['#description']) && !empty($image['breeds'][0]['description'])) {
        $template_vars['#description'] = $image['breeds'][0]['description'];
      }
      if (empty($template_vars['#wikipedia_url']) && !empty($image['breeds'][0]['wikipedia_url'])) {
        $template_vars['#wikipedia_url'] = Url::fromUri($image['breeds'][0]['wikipedia_url']);
      }
      if (empty($template_vars['#temperament']) && !empty($image['breeds'][0]['temperament'])) {
        $template_vars['#temperament'] = $image['breeds'][0]['temperament'];
      }

      if (!empty($image['id'])) {
        $template_vars['#image_ids'][] = $image['id'];
      }
    }

    $build = [
      '#theme' => 'cat_search_results',
    ] + $template_vars;

    $markup = $this->renderer->render($build);

    return ['#markup' => '<div id="search-results">' . $markup . '</div>'];
  }

}
