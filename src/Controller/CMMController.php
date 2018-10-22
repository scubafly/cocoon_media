<?php

namespace Drupal\cocoon_media_management\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\cocoon_media_management\CocoonController;

class CMMController extends ControllerBase {
  // Default settings.
  protected $config;
  protected $cocoonController;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->config = $this->config('CMM.settings');
    $this->cocoonController = new CocoonController(
    $this->config->get('CMM.domain'),
    $this->config->get('CMM.username'),
    $this->config->get('CMM.api_key'));
  }

  public function getTagsAutocomplete(Request $req, $tag_name = '') {
    $params = $req->query->get('q');
    $tags_list = getCachedData('cocoon_media:all_tags', [$this->cocoonController, 'getTags']);
    $tagnames = [];
    // As using autocomplete in forms does not work properly with paths I am adding this 'trick':
    // if tag_name is empty but parameter is not then us parameter.
    $tag_name = $tag_name ? $tag_name : $params;
    foreach($tags_list as $tag) {
      if($tag['used'] > 0) {
        $string_found = $tag_name ? strpos($tag['name'], $tag_name) : true;
        if($string_found !== false){
          // $tagnames[$tag['id']] = $tag['name'];
          $tagnames[] = [
            'value' => $tag['name'],
            'label' => $tag['name'],
          ];
        }
      }
    }
    return new JsonResponse($tagnames);
  }

}
