<?php

/**
 * @file
 * Definition of all admin pages.
 */

/**
 * Implements hook_admin().
 */
function cocoon_media_management_admin() {
  $form = array();
  $form['cocoon_media_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Cocoon Media Management Settings'),
    '#description' => t("Register, get your API key, and place it here."),
    '#collapsible' => FALSE,
    '#tree' => TRUE,
  );
  $form['cocoon_media_settings']['cocoon_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Cocoon API key'),
    '#default_value' => cocoon_media_management_get_setting('cocoon_api_key'),
    '#description' => t('Register on <a target="_blank" href="https://use-cocoon.nl/">use-cocoon.nl</a> and get your API key.'),
    '#required' => TRUE,
  );
  $form['cocoon_media_settings']['cocoon_domain'] = array(
    '#type' => 'textfield',
    '#title' => t('Cocoon Domain'),
    '#default_value' => cocoon_media_management_get_setting('cocoon_domain'),
    '#description' => t('Your Cocoon domain (is the first part of the url of your cocoon site)'),
    '#required' => TRUE,
  );
  $form['cocoon_media_settings']['cocoon_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Cocoon Username'),
    '#default_value' => cocoon_media_management_get_setting('cocoon_username'),
    '#description' => t('Your Cocoon Username'),
    '#required' => TRUE,
  );
  $form['cocoon_media_settings']['paging_size'] = array(
    '#type' => 'textfield',
    '#title' => t('Paging size'),
    '#default_value' => cocoon_media_management_get_setting('paging_size', 15),
    '#description' => t('How many items to show per page.'),
    '#required' => TRUE,
  );
  // CMM cache duration in seconds
  $form['cocoon_media_settings']['cache_duration'] = array(
    '#type' => 'textfield',
    '#title' => t('Cache duration'),
    '#default_value' => cocoon_media_management_get_setting('cache_duration', 60 * 5),
    '#description' => t('How long cached data will last (in seconds).'),
  );
  if(!empty(cocoon_media_management_get_setting('cocoon_api_key'))
      && !empty(cocoon_media_management_get_setting('cocoon_domain'))
      && !empty(cocoon_media_management_get_setting('cocoon_username'))) {
    $form['cocoon_media_settings']['cocoon_media_test_api'] = array(
      '#type' => 'submit',
      '#value' => t('Test API'),
      '#input' => TRUE,
      '#name' => 'op',
      '#button_type' => 'submit',
      '#executes_submit_callback' => FALSE,
      '#limit_validation_errors' => FALSE,
      '#process' => array('ajax_process_form'),
      '#theme_wrappers' => array('button'),
      '#ajax' => array(
        'callback' => 'cocoon_media_management_external_ajax_callback',
        'wrapper' => 'cocoon-output',
        'event' => 'click',
      ),
    );
    $form['cocoon_media_settings']['output'] = array(
      '#markup' => '<div id="cocoon-output"></div>',
    );
  }

  return system_settings_form($form);
}

/**
 * AJAX callback to test API settings
 * @param  array $form       the form array
 * @param  array $form_state the form state array
 * @return null             no output.
 */
function cocoon_media_management_external_ajax_callback($form, $form_state) {
  $cocoonController = new CocoonController(
    cocoon_media_management_get_setting('cocoon_domain'),
    cocoon_media_management_get_setting('cocoon_username'),
    cocoon_media_management_get_setting('cocoon_api_key'));
  $version = $cocoonController->getVersion();
  $output = '<b>Curren API version is: ' . $version . '</b>';

  // Return ajax.
  return array(
    '#type' => 'ajax',
    '#commands' => array(
      ajax_command_html('#cocoon-output', $output),
      array(
        'command' => 'afterAjaxScheduleAdded',
      ),
    ),
  );
}
