<?php

/**
 * @file
 * Contains \Drupal\vimeo_link_formatter\Plugin\Field\FieldFormatter\VimeoLinkFieldFormatter.
 */

namespace Drupal\vimeo_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Unicode;


/**
 * Plugin implementation of the 'vimeo_link_formatter_player' formatter.
 *
 * @FieldFormatter(
 *   id = "vimeo_link_formatter_player",
 *   label = @Translation("Vimeo Player"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class VimeoLinkFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
      $defaults = vimeo_link_formatter_default_settings_player();
    return $defaults + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
      $defaults = vimeo_link_formatter_default_settings_player();


      $elements['width'] = array(
          '#title' => t('Width'),
          '#size' => 10,
          '#description' => t('The width of the player in pixels or relative to the container element.  Do not include "<em>px</em>".  E.g. "<em>500</em>" or "<em>100%</em>".  Defaults to "<em>!default</em>".', array('!default' => $defaults['width'])),
      );

      // Height textbox.
      $elements['height'] = array(
          '#title' => t('Height'),
          '#size' => 10,
          '#description' => t('The height of the player in pixels or relative to the container element.  Do not include "<em>px</em>".  E.g. "<em>280</em>" or "<em>56%</em>".  Defaults to "<em>!default</em>".', array('!default' => $defaults['height'])),
      );

      // Color textbox.
      // Inline CSS is not translatable.
      $style = 'font-weight: bold; padding-left: 0.2em; padding-right: 0.2em;';
      $example_style = "background-color: #F90; color: black; $style";
      $default_style = "background-color: #{$defaults['color']} ; color: white; $style;";

      $variables['style'] = new Attribute();
      $variables['style']['default'] = array('style' => $default_style);
      $variables['style']['example'] = array('style' => $example_style);


      $vars = array(
          '!default' => $defaults['color'],
          '!default_style' => $variables['style']['default'],
          '!example_style' => $variables['style']['example'],
      );

      $elements['color'] = array(
          '#title' => t('Color'),
          '#size' => 10,
          '#description' => t('The color of links and controls (on hover) of the player, such as the title and byline.  CSS colors are not valid.  Six digit hexadecimal colors <em>without</em> the hash/pound character ("<em>#</em>") are valid.  E.g. <code !example_style>FF9900</code>. Defaults to <code !default_style>!default</code>.', $vars),
      );

      // Video information.
      $elements['portrait'] = array(
          '#type' => 'checkbox',
          '#title' => t('Display Portrait'),
          '#description' => t("Display the video submitter's picture or avatar."),
      );

      $elements['title'] = array(
          '#type' => 'checkbox',
          '#title' => t('Display Title'),
          '#description' => t('Display the name of the video.'),
      );

      $elements['byline'] = array(
          '#type' => 'checkbox',
          '#title' => t('Display Byline'),
          '#description' => t('Display who the video is by.'),
      );

      // Autoplay checkbox.
      $elements['autoplay'] = array(
          '#type' => 'checkbox',
          '#title' => t('Autoplay'),
          '#description' => t('Automatically play the video on load.  This also causes the portrait, title and byline to be hidden.'),
      );

      // Loop textbox.
      $elements['loop'] = array(
          '#type' => 'checkbox',
          '#title' => t('Loop'),
          '#description' => t('Play the video repeatedly.'),
      );

      // js api.
      $elements['js_api'] = array(
          '#type' => 'checkbox',
          '#title' => t('Enable javascript API'),
          '#description' => t('Enable the vimeo javascript api (this has performance implications-- don\'t use unless you know what you\'re doing. See <a href="!url">Vimeo Player Javascript API</a> for more information).', array('!url' => 'http://vimeo.com/api/docs/player-js')),
      );


      // Set some Form API attributes that apply to all elements.
      foreach (array_keys($elements) as $key) {
          // Textboxes are smaller than default.  #size is ignored for checkboxes.
          $elements[$key]['#size'] = '10';

          // Default to #type => textfield if #type is not already set.
          if (!isset($elements[$key]['#type'])) {
              $elements[$key]['#type'] = 'textfield';
          }

          // Set #default_value too.
          if ($this->getSetting($key)) {
              $elements[$key]['#default_value'] = $this->getSetting($key);
          }
      }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
      $cp = array();
      $cs = '<b>Configurations</b><br>';
      $parameters = array(
          'title' => $vimeo_title = $this->getSetting('title'),
          'byline' => $this->getSetting('byline'),
          'portrait' => $this->getSetting('portrait'),
          'showinfo' => $this->getSetting('color'),
          'autoplay' => $this->getSetting('autoplay'),
          'loop' => $this->getSetting('loop'),
          'width' => $this->getSetting('width'),
          'height' => $this->getSetting('height'),
          'js_api' => $this->getSetting('js_api'),
          'border' => $this->getSetting('border'),
      );
      foreach ($parameters as $key=>$value) {
          if ($value) {
              $cp['@'.$key] = ucfirst($key);
              $cs .= '@'.$key.' ';
          }
      }



    $summary[] = t($cs, $cp);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();
    $field_name = $items->getFieldDefinition()->getName();
    $field_data_array = $items->getEntity()->toArray();
    $video_id = vimeo_link_formatter_id($field_data_array[$field_name][0]['uri']);
    foreach ($items as $delta => $item) {
      $element[$delta] = array(
          '#theme' => 'vimeo_video',
          '#video_id' => $video_id['id'],
          '#entity_title' => $items->getEntity()->label(),
          '#settings' => $settings,
      );
    }
    return $element;
  }

}
