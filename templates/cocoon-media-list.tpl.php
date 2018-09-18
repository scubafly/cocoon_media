<?php

/** 
* @file 
* Default theme implementation to print Cocoon Media in a grid-like list. 
* * Available variables: 
* - source_text 
* * @see template_preprocess_cocoon_media_management() 
* * @ingroup themeable */
?>

<div class="js-form-item form-item js-form-type-select-grid form-type-select-grid js-form-item-cocoon-media-browser-sets form-item-cocoon-media-browser-sets">
  <?php if (is_array($source_text)): ?>
  <label>The items:</label>
    <?php foreach ($source_text as $key => $item): ?>
      <div class="js-form-item form-item js-form-type-select-grid form-type-select-grid js-form-item-select-grid form-item-select-grid">
        <input data-drupal-selector="edit-select-grid-<?php print $item; ?>" id="edit-select-grid-<?php print $item; ?>" name="cocoon_media_browser[sets]" value="<?php print $item; ?>" class="form-radio" type="radio">
        <label for="edit-select-grid-<?php print $item; ?>" class="option"><?php print $item; ?></label>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <?php print $source_text; ?>
  <?php endif; ?>
</div>