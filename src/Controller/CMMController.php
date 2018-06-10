<?php

namespace Drupal\cocoon_media_management\Controller;

use Drupal\Core\Controller\ControllerBase;

class CMMController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Test page...'),
    ];
  }

}
