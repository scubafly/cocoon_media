<?php

namespace Drupal\cocoon_media\Form;

use Drupal\cocoon_media\CocoonController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Class CMMAddMediaForm.
 *
 * @package Drupal\cocoon_media\Form
 */
class CMMAddMediaForm extends ConfigFormBase {
  // Default settings.
  protected $config;
  protected $cocoonController;
  protected $cacheDuration;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->config = $this->config('CMM.settings');
    $this->cocoonController = new CocoonController(
    $this->config->get('CMM.domain'),
    $this->config->get('CMM.username'),
    $this->config->get('CMM.api_key'));
    $this->cacheDuration = $this->config->get('CMM.cache_duration') ?: 60 * 5;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cocoon_media_add_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['cocoon_media_browser'] = array(
      '#type' => 'fieldset',
      '#title' => t('Cocoon Media Management Browse'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );
    // CMM Label.
    $form['cocoon_media_browser']['othertable'] = array(
      '#type' => 'tablegridselect',
    );
    // CMM Label.
    $form['cocoon_media_browser']['description'] = array(
      '#markup' => t("Browse and add Cocoon Media to your library.") . '<br/>',
    );

    // Add the follwing form elements only if the module API is configured.
    if (!empty($this->config->get('CMM.api_key'))
      && !empty($this->config->get('CMM.domain'))
      && !empty($this->config->get('CMM.username'))) {
      $form['cocoon_media_browser']['clear_cache'] = array(
        '#type' => 'button',
        '#value' => t('Refresh library'),
        '#ajax' => array(
          'callback' => array($this, 'refreshLibrary'),
          'wrapper' => 'edit-cocoon-media-browser',
          'effect' => 'fade',
          'prevent' => 'onfocus',
          'keypress' => TRUE,
        ),
      );
      $sets = $this->cocoonController->getSets();
      $radio_sets = [];
      $total_count = 0;
      foreach ($sets as $set) {
        $radio_sets[$set['id']] = $set['title'] . ' (' . $set['file_count'] . ')';
        $total_count += $set['file_count'];
      }
      $radio_sets['all'] = 'All (' . $total_count . ')';
      $form['cocoon_media_browser']['sets'] = array(
        '#type' => 'radios',
        '#title' => t('Select a set'),
        '#default_value' => 'all',
        '#options' => $radio_sets,
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
          'wrapper' => 'cocoon-results',
          'effect' => 'fade',
        ),
      );

      $set = 'all';
      $tag_name = '';
      $current_page = 0;
      $total_pages = 1;
      $values = $form_state->getValues();
      if (!empty($values)) {
        $set = $values['cocoon_media_browser']['sets'];
        $tag_name = $values['cocoon_media_browser']['tag_elements']['tagname'];
        $current_page = $values['cocoon_media_browser']['results']['pager_actions']['page'];
        if ($values['op'] == '>') {
          $current_page += 1;
        }
        if ($values['op'] == '<') {
          $current_page -= 1;
        }
      }
      $options = $this->buildOptionsElements($set, $tag_name);
      $options_chunk = array_chunk($options, $this->config->get('CMM.paging_size', 15), TRUE);
      $total_pages = count($options_chunk);
      $current_page = $current_page < 0 ? 0 : $current_page;
      $current_page = $current_page >= $total_pages ? $total_pages - 1 : $current_page;
      $form['cocoon_media_browser']['tag_elements'] = array(
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
      );
      $form['cocoon_media_browser']['tag_elements']['tagname'] = array(
        '#type' => 'textfield',
        '#placeholder' => t('Search by tag'),
        '#autocomplete_route_name' => 'cocoon_media.tag_autocomplete',
        // '#autocomplete_route_parameters' =>
        // array('tag_name' => strval($tag_name)),
        '#size' => '20',
        '#maxlength' => '60',
      );
      $form['cocoon_media_browser']['tag_elements']['tag_search'] = array(
        '#type' => 'button',
        '#value' => t('Search'),
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
          'wrapper' => 'cocoon-results',
          'effect' => 'fade',
          'prevent' => 'onfocus',
          'keypress' => TRUE,
        ),
      );

      $form['cocoon_media_browser']['results'] = array(
        '#prefix' => '<div id="cocoon-results">',
        '#suffix' => '</div>',
      );

      $ajax_call = array(
        'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
        'wrapper' => 'cocoon-results',
        'effect' => 'fade',
        'progress' => array(
          'message' => '',
        ),
      );

      $form['cocoon_media_browser']['results'] = array_merge($form['cocoon_media_browser']['results'], $this->buildAjaxPager($ajax_call, $current_page, $total_pages));
      $form['cocoon_media_browser']['results']['images_table'] = $this->buildTableSelect('images-table', $options_chunk[$current_page]);
    }
    else {
      // CMM Label.
      $url = Link::createFromRoute('here', 'cocoon_media.admin_settings');
      $form['cocoon_media_browser']['api_not_configured'] = array(
        '#markup' => $this->t("Please first add the configuration parameters here: "),
      );
      $form['cocoon_media_browser']['api_settings_link'] = $url->toRenderable();
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Media'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * TODO add function description.
   *
   * @param string $url
   *   TODO add url description.
   * @param string $local_url
   *   TODO add description.
   * @param bool $to_temp
   *   TODO add description.
   *
   * @return string
   *   TODO add description.
   */
  public function retrieveRemoteFile($url, $local_url = '', $to_temp = FALSE) {
    // Check the cache and download the file if needed.
    $parsed_url = parse_url($url);
    $cocoon_dir = 'cocoon_media_files';
    $cocoon_media_directory = 'public://' . $cocoon_dir . '/';
    file_prepare_directory($cocoon_media_directory, FILE_CREATE_DIRECTORY);
    if (empty($local_url)) {
      // $cocoon_media_directory = $to_temp ? 'temporary://' : 'public://';.
      $local_url = $cocoon_media_directory . '/' . drupal_basename($parsed_url['path']);
    }
    return system_retrieve_file($url, $local_url, !$to_temp, FILE_EXISTS_REPLACE);
  }

  /**
   * TODO add function description.
   *
   * @param array $image_info
   *   TODO add description.
   * @param string $prefix
   *   TODO add description.
   *
   * @return string
   *   TODO add description.
   */
  public function remoteThumbToLocal(array $image_info, $prefix) {
    $local_path = '';
    $filename = $prefix . $image_info['filename'] . '.' . $image_info['extension'];
    if (!empty($filename)) {
      $local_path = 'public://cocoon_media_files/' . $filename;
      if (!file_exists($local_path)) {
        $thumb_info = $this->cocoonController->getThumbInfo($image_info['id']);
        if (empty($thumb_info['web'])) {
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
    $selected_images = $values['cocoon_media_browser']['results']['images_table'];
    $filenames = '';
    foreach ($selected_images as $selected_image_id) {
      if ($selected_image_id) {
        $file_info = $this->cocoonController->getThumbInfo($selected_image_id);
        if (!empty($file_info['faultstring'])) {
          drupal_set_message($this->t("The File(s) cannot be added to the media library. Error message: " . $file_info['faultstring']), 'error');
          return;
        }
        $url = $file_info['path'];
        // Check the cache and download the file if needed.
        $file = $this->retrieveRemoteFile($url);
        if (empty($file)) {
          drupal_set_message($this->t("The File(s) cannot be added to the media library."), 'error');
          return;
        }
        $media_bundle = 'file';
        $field_media_name = 'field_media_file';
        $field_media_arr = [
          'target_id' => $file->id(),
          'title' => $this->t($file_info['name']),
        ];

        if ($file_info['ext'] === 'jpg' ||
            $file_info['ext'] === 'jpeg' ||
            $file_info['ext'] === 'png' ||
            $file_info['ext'] === 'gif' ||
            $file_info['ext'] === 'tiff' ||
            $file_info['ext'] === 'bmp'
        ) {
          $media_bundle = 'image';
          $field_media_name = 'field_media_image';
          $field_media_arr['alt'] = $file_info['name'];
        }

        if ($file_info['ext'] === 'mp4'||
            $file_info['ext'] === 'avi'||
            $file_info['ext'] === 'flv'||
            $file_info['ext'] === 'mov') {
          $media_bundle = 'video';
          $field_media_name = 'field_media_video_file';
          $field_media_arr['alt'] = $file_info['name'];
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
        $filenames .= $file_info['name'] . ', ';
      }
    }
    // Redirecting to the media library page.
    $media_url = Url::fromRoute('entity.media.collection');
    $form_state->setRedirectUrl($media_url);
    $filenames = substr($filenames, 0, -2);
    // Adding custom message.
    drupal_set_message($this->t("The File(s) <i>$filenames</i> has been added to the media library."));
  }

  function getFilesByTag($tag_name) {
    $tags_images_list = [];
    $tags_list = null;
    $matches = [];
    $tags_list = getCachedData('cocoon_media:all_tags', [$this->cocoonController, 'getTags'], [], $this->cacheDuration);
    foreach ($tags_list as $tag) {
      $string_found = $tag_name ? strpos($tag['name'], $tag_name) : TRUE;
      if ($string_found !== FALSE) {
        $matches[$tag['id']] = SafeMarkup::checkPlain($tag['name'])->__toString();
      }
    }
    foreach ($matches as $tag_id => $tag) {
      $tag_files = getCachedData('cocoon_media:tag_' . $tag_id, [$this->cocoonController, 'getFilesByTag'], [$tag_id], $this->cacheDuration);
      $tags_images_list = array_merge($tags_images_list, $tag_files);
    }
    return $tags_images_list;
  }

  function buildAjaxPager($ajax_callback, $current_page = 0, $total_pages = 0) {
    $form_ajax_pager['pager_actions'] = array(
      '#type' => 'actions',
      '#weight' => 0,
    );
    $form_ajax_pager['pager_actions']['prev'] = array(
      '#type' => 'button',
      '#value' => '<',
      '#ajax' => $ajax_callback,
    );
    $form_ajax_pager['pager_actions']['page'] = array(
      '#type' => 'hidden',
      '#value' => $current_page,
    );
    $form_ajax_pager['pager_actions']['pagenum'] = array(
      '#type' => 'button',
      '#value' => $current_page + 1 . ' of ' . $total_pages,
      '#disabled' => TRUE,
    );
    $form_ajax_pager['pager_actions']['next'] = array(
      '#type' => 'button',
      '#value' => '>',
      '#ajax' => $ajax_callback,
    );

    return $form_ajax_pager;
  }

  function buildSingleOptionElement($image_info) {
    $thumb_url = '/' . drupal_get_path('module', 'cocoon_media')
      . '/images/generic.png';
    $thumb = $this->remoteThumbToLocal($image_info, 'thumb_');
    if (!empty($thumb)) {
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
      . round($image_info['size'] / 1024, 2)
      . 'KB</p>',
    ];
    $rendered_item = \Drupal::service('renderer')->renderPlain($elm);
    return $rendered_item;
  }

  public function buildOptionsElements($set_id, $tag_name = null) {
    $image_list = [];
    $sets_image_list = [];
    if (!empty($set_id)) {
      if ($set_id !== 'all') {
        $sets_image_list = getCachedData('cocoon_media:set_' . $set_id, [$this->cocoonController, 'getFilesBySet'], [$set_id], $this->cacheDuration);
      }
      else {
        foreach ($this->cocoonController->getSets() as $set) {
          $sets_image_list = array_merge($sets_image_list, getCachedData('cocoon_media:set_' . $set['id'], [$this->cocoonController, 'getFilesBySet'], [$set['id']], $this->cacheDuration));
        }
      }
    }
    $image_list = $sets_image_list;

    $tags_images_list = $this->getFilesByTag($tag_name);
    if ($tag_name == null && $set_id == 'all') {
      $image_list = array_merge($sets_image_list, $tags_images_list);
    }
    else if($tag_name != null && $set_id == 'all') {
      $image_list = $tags_images_list;
    }
    else if($tag_name && $set_id !== 'all') {
      $image_list = array();
      foreach($sets_image_list as $set_image){
        foreach($tags_images_list as $tag_image) {
          if($tag_image['id'] == $set_image['id'])
          {
            $image_list[$set_image['id']] = $tag_image;
          }
        }
      }
    }

    $options = [];
    foreach ($image_list as $idx => $image_info) {
      $rendered_item = getCachedData('cocoon_media:option_item_' . $image_info['id'], [$this, 'buildSingleOptionElement'], [$image_info]);
      $options[$image_info['id']] = [
        'media_item' => $rendered_item,
      ];
    }
    return $options;
  }

  public function buildTableSelect($class_id, $options) {
    $header = [
      'media_item' => t('Media File'),
    ];
    $table = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No media found'),
      '#multiple' => TRUE,
      '#attributes' => ['id' => $class_id],
      '#cache' => [
        // Cached for one day.
        'max-age' => 60 * 60 * 24,
      ],
      '#attached' => array(
        'library' => array('cocoon_media/tablegrid-selet'),
      ),
    );
    return $table;
  }

  public function ajaxCallbackGetFilesBySet(array &$form, FormStateInterface &$form_state) {
    return $form['cocoon_media_browser']['results'];
  }

  public function refreshLibrary(array &$form, FormStateInterface &$form_state) {
    drupal_flush_all_caches();
    return $form['cocoon_media_browser'];
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
