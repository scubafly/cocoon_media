<?php

/**
 * @file
 * Definition of CocoonMedia.
 */

/**
 * Media browser plugin for showing images from Cocoon.
 *
 * @deprecated
 */
class CocoonMedia extends MediaBrowserPlugin {

  /**
   * Implements MediaBrowserPluginInterface::access().
   */
  public function access($account = NULL) {
    return TRUE;
  }

  /**
   * Implements MediaBrowserPlugin::view().
   */
  public function view() {
    module_load_include('inc', 'file_entity', 'file_entity.pages');

    $build = array();
    $params = $this->params;
    $params['cocoon'] = TRUE;
    $build['form'] = drupal_get_form('media_pixabay_external', $params);

    return $build;
  }

}
