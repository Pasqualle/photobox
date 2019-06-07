<?php

use Drupal\image\Entity\ImageStyle;

/**
 * Prepares variables for photobox image formatter templates.
 *
 * Default template: photobox-image-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 */
function template_preprocess_photobox_image_formatter(&$variables) {
  if ($variables['image_style']) {
    $variables['image'] = [
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    ];
  }
  else {
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (mb_strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $image_uri = $entity->getFileUri();
  }
  else {
    $image_uri = $item->uri;
  }
  $variables['image']['#uri'] = $image_uri;

  foreach (['width', 'height', 'alt'] as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

  $caption = '';
  if ($variables['caption_selection'] === 'title') {
    $caption = $item->title;
  }
  elseif ($variables['caption_selection'] === 'alt') {
    $caption = $item->alt;
  }
  if ($variables['large_image_style']) {
    $large_image_url = ImageStyle::load($variables['large_image_style'])->buildUrl($image_uri);
  }
  else {
    $large_image_url = file_create_url($image_uri);
  }
  $variables['caption'] = $caption;
  $variables['large_image_url'] = $large_image_url;
}
