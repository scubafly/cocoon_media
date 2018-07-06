<?php

namespace Drupal\cocoon_media_management\Form;

use Drupal\cocoon_media_management\CocoonController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

class CMMAddMediaForm extends ConfigFormBase {
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

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cocoon_media_management_add_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    $sets = $this->cocoonController->getSets();
    $radio_sets = [];
    $total_count = 0;
    foreach($sets as $set) {
      $radio_sets[$set['id']] = $set['title'] . ' (' . $set['file_count'] . ')';
      $total_count += $set['file_count'];
    }
    $radio_sets['all'] = 'All'. ' (' . $total_count . ')';

    // $form['custom'] = array(
    //   '#theme' => 'select_items_grid',
    //   '#source_text' => array(
    //     'some',
    //     ' text',
    //     ' and. dot me?',
    //     ' Why!',
    //   ),
    // );
    $form['cocoon_media_browser'] = array(
      '#type' => 'fieldset',
      '#title' => t('Cocoon Media Management Browse'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );
    // CMM Label
    $form['cocoon_media_browser']['othertable'] = array(
      '#type' => 'tablegridselect',
    );
    // CMM Label
    $form['cocoon_media_browser']['description'] = array(
      '#markup' => t("Browse and add Cocoon Media to your library.") . '<br/>',
    );

    // Add the follwing form elements only if the module API is configured.
    if(!empty($this->config->get('CMM.api_key'))
      && !empty($this->config->get('CMM.domain'))
      && !empty($this->config->get('CMM.username'))) {
       $form['cocoon_media_browser']['sets'] = array(
        '#type' => 'radios',
        '#title' => t('Select a set'),
        '#default_value' => 'all',
        '#options' => $radio_sets,
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
          'wrapper' => 'images-table',
          'effect' => 'fade',
        ),
      );

      $set = 'all';
      $values = $form_state->getValues();
      if(!empty($values)) {
        $set = $values['cocoon_media_browser']['sets'];
      }
      $form['cocoon_media_browser']['images_table'] = $this->buildTableSelect('images-table', $set);
    }
    else {
      // CMM Label
      $url = Link::createFromRoute('here','cocoon_media_management.admin_settings');
      $form['cocoon_media_browser']['api_not_configured'] = array(
        '#markup' => t("Please first add the configuration parameters here: "),
      );
      $form['cocoon_media_browser']['api_settings_link'] = $url->toRenderable();
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Downlad Media'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function retrieveRemoteFile($url, $local_url = '', $to_temp = false) {
    // Check the cache and download the file if needed.
    $parsed_url = parse_url($url);
    $cocoon_dir = 'cocoon_media_files';
    $cocon_media_directory = 'public://' . $cocoon_dir . '/';
    file_prepare_directory($cocon_media_directory, FILE_CREATE_DIRECTORY);
    if(empty($local_url)) {
      // $cocon_media_directory = $to_temp ? 'temporary://' : 'public://';
      $local_url = $cocon_media_directory . '/' . drupal_basename($parsed_url['path']);
    }
    return system_retrieve_file($url, $local_url, !$to_temp, FILE_EXISTS_REPLACE);
  }

  public function remoteThumbToLocal($image_info, $prefix, $is_temp = false) {
    $local_path = '';
    $filename = $prefix . $image_info['filename'] . '.' . $image_info['extension'];
    if(!empty($filename)) {
      $local_path = 'public://cocoon_media_files/' . $filename;
      if(!file_exists($local_path)) {
        $thumb_info = $this->cocoonController->getThumbInfo($image_info['id']);
        if(!empty($thumb_info['web'])) {
          $local_file = $this->retrieveRemoteFile($thumb_info['web'], $local_path, $is_temp);
        }
        else {
          $local_path = '';
        }
      }
    }
    return $local_path;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_image_id = $values['cocoon_media_browser']['images_table'];
    if($selected_image_id) {
      $file_info = $this->cocoonController->getThumbInfo($selected_image_id);
      $url = $file_info['path'];
      // Check the cache and download the file if needed.
      $file = $this->retrieveRemoteFile($url);
      $media_bundle = 'file';
      if ( $file_info['ext'] === 'jpg' ||
           $file_info['ext'] === 'png' ||
           $file_info['ext'] === 'gif' ||
           $file_info['ext'] === 'tiff' ||
           $file_info['ext'] === 'bmp'
      ) {
        $media_bundle = 'image';
        $field_media_name = 'field_media_image';
        $field_media_arr = [
          'target_id' => $file->id(),
          'alt' => $file_info['name'],
          'title' => t($file_info['name']),
        ];
      }
      else if($file_info['ext'] === 'mp4'||
           $file_info['ext'] === 'avi'||
           $file_info['ext'] === 'flv'||
           $file_info['ext'] === 'mov') {
        $media_bundle = 'video';
        $field_media_name = 'field_media_video_file';
        $field_media_arr = [
          'target_id' => $file->id(),
          'alt' => $file_info['name'],
          'title' => t($file_info['name']),
        ];
      }
      else {
        $media_bundle = 'file';
        $field_media_name = 'field_media_file';
        $field_media_arr = [
          'target_id' => $file->id(),
          'title' => t($file_info['name']),
        ];

      }
      // Create media entity with saved file.
      $image_media = Media::create([
        'bundle' => $media_bundle,
        'uid' => \Drupal::currentUser()->id(),
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'name' => $file_info['name'],
        $field_media_name => $field_media_arr,
       ]);
      $image_media->save();
    }
    // Redirecting to the media library page.
    $media_url = Url::fromRoute('entity.media.collection');
    $form_state->setRedirectUrl($media_url);
    $filename = $file_info['name'];
    // Adding custom message.
    drupal_set_message($this->t("The File <i>$filename</i> has been added to the media library."));
  }

  public function buildOptionsElements($set_id) {
    $image_list = [];
    if(!empty($set_id)) {
      if($set_id !== 'all')
      {
        $image_list = $this->cocoonController->getFilesBySet($set_id);
      }
      else {
        foreach($this->cocoonController->getSets() as $set) {
          $image_list = array_merge($image_list, $this->cocoonController->getFilesBySet($set['id']));
        }
      }
    }
    $options = [];
    foreach($image_list as $idx => $image_info) {
      $thumb_url = '/' . drupal_get_path('module', 'cocoon_media_management')
      . '/images/generic.png';
      $thumb = $this->remoteThumbToLocal($image_info, 'thumb_', true);
      if(!empty($thumb)) {
        $thumb_url = file_create_url($thumb);
      }
      $elm = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
      ];
      $elm['id'] = [
        '#type' => 'hidden',
        '#value' => $image_info['id'],
      ];
      $elm['thumb'] = [
        '#type' => 'label',
        '#title_display' => 'before',
        '#title' => '&nbsp;',
        '#attributes' => [
          'class' => 'media-thumb',
          'style' => "background-image:url(" . $thumb_url . ")",
        ],
        // '#markup' => "<div class='media-thumb' style='background-image:url($thumb_url)'></div>",
      ];
      $elm['title'] = [
        '#type' => 'label',
        '#title_display' => 'before',
        '#title' => $image_info['title'],
        '#attributes' => ['class' => 'media-title'],
      ];
      $elm['file_details'] = [
        '#markup' => '<p><b>Extension: </b>'
                    . $image_info['extension']
                    . '<br/><b>Size: </b>'
                    . round($image_info['size']/1024, 2)
                    . 'KB</p>',
      ];

      $options[$image_info['id']] = [
        'media_item' => \Drupal::service('renderer')->renderPlain($elm),
      ];
    }
    return $options;
  }

  public function buildTableSelect($class_id, $set_id) {
    $header = [
        'media_item' => t('Media File'),
    ];
    $options = $this->buildOptionsElements($set_id);
    $table = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No media found'),
      '#multiple' => FALSE,
      '#attributes' => ['id' => $class_id],
      '#cache' => [
        'max-age' => 60 * 60 * 24, // Cached for one day.
      ],
      '#attached' => array(
        'library' => array('cocoon_media_management/tablegrid-selet')
      ),
    );
    return $table;
  }

  public function ajaxCallbackGetFilesBySet(array &$form, FormStateInterface &$form_state) {
    return $form['cocoon_media_browser']['images_table'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'CMM.settings',
    ];
  }

}
