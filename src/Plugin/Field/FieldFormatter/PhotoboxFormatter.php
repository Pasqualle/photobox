<?php

namespace Drupal\photobox\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'photobox' image formatter.
 *
 * @FieldFormatter(
 *   id = "photobox",
 *   label = @Translation("Photobox"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class PhotoboxFormatter extends ImageFormatter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'large_image_style' => '',
        'image_style' => '',
        'gallery' => 'all',
        'caption' => '',
      ] + parent::defaultSettings();
  }

  /**
   * All properties which are related to image styles.
   */
  private function imageStyleRelatedProperties() {
    return [
      'large_image_style' => $this->t('Large image style'),
      'image_style' => $this->t('Image style')
    ];
  }

  /**
   * Available options for gallery display.
   */
  private function optionsGallery() {
    return [
      'all' => $this->t('All (Group all images on the page into 1 gallery)'),
      'entity' => $this->t('Same content (Group images by entity)'),
      'field' => $this->t('Same field name (Group images by field)'),
      'entity_field' => $this->t('Separate (Every image field is a different group)'),
    ];
  }

  /**
   * Available options for image caption display.
   */
  private function optionsCaption() {
    return [
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
    ];
  }

  public function settingsFormGalleryCustomValidate($element, FormStateInterface $form_state) {
    $submitted_value = $form_state->getValue($element['#parents']);
    if (!empty($submitted_value) && !preg_match('!^[a-z0-9_-]+$!', $submitted_value)) {
      $form_state->setError($element, t('%name must only contain lowercase letters, numbers, hyphen and underscores.', ['%name' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    foreach ($this->imageStyleRelatedProperties() as $key => $title) {
      $element[$key] = [
        '#title' => $title,
        '#type' => 'select',
        '#default_value' => $this->getSetting($key),
        '#empty_option' => $this->t('None (original image)'),
        '#options' => $image_styles,
        '#description' => $description_link->toRenderable() + [
            '#access' => $this->currentUser->hasPermission('administer image styles'),
          ],
      ];
    }

    $element['gallery'] = [
      '#title' => $this->t('Gallery (image grouping)'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('gallery'),
      '#required' => TRUE,
      '#options' => $this->optionsGallery(),
      '#description' => $this->t('How to group images on the page:<br />
<em>All</em>: Group all images on the page into 1 gallery<br />
<em>Same content</em>: Group images by entity<br />
<em>Same field name</em>: Group images by field<br />
<em>Separate</em>: Every image field is a different group. Useful only for multi value images.'),
    ];

    $element['caption'] = [
      '#title' => $this->t('Caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('caption'),
      '#empty_option' => $this->t('No caption'),
      '#options' => $this->optionsCaption(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);

    $key = $this->getSetting('image_style');
    $style = isset($image_styles[$key]) ? $image_styles[$key] : $this->t('Original image');
    $summary[] = $this->t('Small image style: @style', ['@style' => $style]);

    $key = $this->getSetting('large_image_style');
    $style = isset($image_styles[$key]) ? $image_styles[$key] : $this->t('Original image');
    $summary[] = $this->t('Large image style: @style', ['@style' => $style]);

    $gallery_options = $this->optionsGallery();
    $key = $this->getSetting('gallery');
    $gallery = isset($gallery_options[$key]) ? $gallery_options[$key] : $this->t('No gallery');
    $summary[] = $this->t('Gallery type: @gallery', ['@gallery' => $gallery]);

    $caption_options = $this->optionsCaption();
    $key = $this->getSetting('caption');
    $caption = isset($caption_options[$key]) ? $caption_options[$key] : $this->t('No caption');
    $summary[] = $this->t('Image caption: @caption', ['@caption' => $caption]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      switch ($this->getSetting('gallery')) {
        case 'entity':
          $gallery_id = 'gallery-' . $items->getEntity()->id();
          break;
        case 'field':
          $gallery_id = 'gallery-' . $items->getFieldDefinition()->getName();
          break;
        case 'entity_field':
          $gallery_id = 'gallery-' . $items->getEntity()->id() . '-' . $items->getFieldDefinition()->getName();
          break;
        default:
          $gallery_id = 'gallery-all';
      }

      $elements[$delta] = [
        '#theme' => 'photobox_image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
        '#large_image_style' => $this->getSetting('large_image_style'),
        '#image_style' => $this->getSetting('image_style'),
        '#gallery_id' => $gallery_id,
        '#caption_selection' => $this->getSetting('caption'),
      ];
    }
    $elements['#attached']['library'][] = 'photobox/libraries.jquery.photobox';
    $elements['#attached']['library'][] = 'photobox/drupal.photobox.integration';
    $config = $this->configFactory->get('photobox.settings');
    $elements['#attached']['drupalSettings']['photobox'] = [
      'history' => $config->get('history'),
      'loop' => $config->get('loop'),
      'thumbs' => $config->get('thumbs'),
      'zoomable' => $config->get('zoomable'),
    ];

    return $elements;

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    foreach ($this->imageStyleRelatedProperties() as $key => $title) {
      $style_id = $this->getSetting($key);
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach ($this->imageStyleRelatedProperties() as $key => $title) {
      $style_id = $this->getSetting($key);
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace the
          // image style with the replacement and signal that the formatter plugin
          // settings were updated.
          if ($replacement_id && ImageStyle::load($replacement_id)) {
            $this->setSetting($key, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }
}
