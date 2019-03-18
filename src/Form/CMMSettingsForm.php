<?php

namespace Drupal\cocoon_media_management\Form;

use Drupal\cocoon_media_management\CocoonController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CMMSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cocoon_media_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('cocoon_media.settings');
    $form['cocoon_media_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Cocoon Media Management Settings'),
      // '#description' => t("Register, get your API key, and place it here."),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );
    // CMM Label
    $form['cocoon_media_settings']['description'] = array(
      '#markup' => t("Register, get your API key, and place it here."),
    );
    // CMM API Key
    $form['cocoon_media_settings']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon API key'),
      '#default_value' => $config->get('cocoon_media.api_key'),
      '#description' => $this->t('Register on <a target="_blank" href="https://use-cocoon.nl/">use-cocoon.nl</a> and get your API key.'),
    );
    // CMM domain
    $form['cocoon_media_settings']['domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon Domain'),
      '#default_value' => $config->get('cocoon_media.domain'),
      '#description' => $this->t('Your Cocoon domain (is the first part of the url of your cocoon site)'),
    );
    // CMM username
    $form['cocoon_media_settings']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cocoon Username'),
      '#default_value' => $config->get('cocoon_media.username'),
      '#description' => $this->t('Your Cocoon Username'),
    );
    // CMM pagination size
    $form['cocoon_media_settings']['paging_size'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pager size'),
      '#default_value' => $config->get('cocoon_media.paging_size', 15),
      '#description' => $this->t('How many items per page'),
    );
    // CMM cache duration in seconds
    $form['cocoon_media_settings']['cache_duration'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cache duration'),
      '#default_value' => $config->get('cocoon_media.cache_duration', 60 * 5),
      '#description' => $this->t('How long cached data will last (in seconds).'),
    );

    if(!empty($config->get('cocoon_media.api_key'))
      && !empty($config->get('cocoon_media.domain'))
      && !empty($config->get('cocoon_media.username'))) {
    $form['cocoon_media_settings']['cocoon_media_test_api'] = array(
      '#type' => 'submit',
      '#value' => t('Test API'),
      '#name' => 'testapi',
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallbackTestApi'),
        'wrapper' => 'cocoon-output',
        'effect' => 'fade',
      ),
    );
    $form['cocoon_media_settings']['output'] = array(
      '#markup' => '<div id="cocoon-output"></div>',
    );
  }

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValue('cocoon_media_settings');
    $config = $this->config('cocoon_media.settings');
    $config->set('cocoon_media.api_key', $form_values['api_key']);
    $config->set('cocoon_media.domain', $form_values['domain']);
    $config->set('cocoon_media.username', $form_values['username']);
    $config->set('cocoon_media.paging_size', $form_values['paging_size']);
    $config->set('cocoon_media.cache_duration', $form_values['cache_duration']);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  public function ajaxCallbackTestApi(array &$form, FormStateInterface &$form_state) {
    $config = $this->config('cocoon_media.settings');
    $cocoonController = new CocoonController(
    $config->get('cocoon_media.domain'),
    $config->get('cocoon_media.username'),
    $config->get('cocoon_media.api_key'));
    $version = $cocoonController->getVersion();
    $output = '<b>Curren API version is: ' . $version . '</b>';

    return array(
      '#markup' => $output,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cocoon_media.settings',
    ];
  }

}
