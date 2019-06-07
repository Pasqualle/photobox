<?php

namespace Drupal\photobox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'photobox.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photobox_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('photobox.settings');
    $form['history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Browser history'),
      '#description' => $this->t('Enable/disable HTML5 history using hash urls.'),
      '#default_value' => $config->get('history'),
    ];
    $form['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#description' => $this->t('Loop back to last image before the first one and to the first image after last one.'),
      '#default_value' => $config->get('loop'),
    ];
    $form['thumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Thumbs'),
      '#description' => $this->t('Show thumbnail images in the gallery at the bottom.'),
      '#default_value' => $config->get('thumbs'),
    ];
    $form['zoomable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zoom'),
      '#description' => $this->t('Enable/Disable mousewheel zooming over images.'),
      '#default_value' => $config->get('zoomable'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('photobox.settings')
      ->set('history', $form_state->getValue('history'))
      ->set('loop', $form_state->getValue('loop'))
      ->set('thumbs', $form_state->getValue('thumbs'))
      ->set('zoomable', $form_state->getValue('zoomable'))
      ->save();
  }

}
