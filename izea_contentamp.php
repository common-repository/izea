<?php

/**
 * Class IZEAContentAmp
 *
 * This class encapsulates the Content Amp functionality of the IZEA Plugin. It's goal
 * is to create the settings an modify the RSS feed for those settings.
 *
 * You should  call this class' init() function when the plugin is created.
 */
class IZEAContentAmp {
  // values for the amplify by default setting and meta data...
  public static $AMPLIFY_BY_DEFAULT = 1;
  public static $DO_NOT_AMPLIFY_BY_DEFAULT = 0;

  // constants to help define the settings that the plugin defines...
  public static $option = 'izea_contentamp_option';
  private static $section = 'izea_contentamp_options_section';
  private static $section_title = 'ContentAmp Options';

  // constants for the amplify by default option field...
  private static $amplify_by_default_option = 'amplify_by_default';
  private static $amplify_by_default_title = 'Amplify New Posts';
  private static $amplify_by_default_desc = 'Should newly published Posts be Amplified by default?';

  // constants for the amplify by default meta data...
  public static $amplify_by_default_meta = 'izea_contentamp_meta_amplify_by_default';
  public static $amplify_by_default_meta_desc = "Amplify this Post with ContentAmp?";

  /**
   * Call init() when the plugin is created to hook into the Wordpress actions that are required.
   */
  public function init() {
    //hook into the rss & atom feed actions...
    add_action('rss2_item', array($this, 'add_rss_tag'));
    add_action('atom_entry', array($this, 'add_rss_tag'));

    //hook into settings based actions...
    add_action('admin_init', array($this, 'initialize_settings'));
  }

  /**
   * This function hooks into the Wordpress admin_init action and initializes options that
   * Content Amp feature of the IZEA Plugin needs in order to function.
   */
  public function initialize_settings() {
    //create the option if it does not already exist...
    if(false == get_option(self::$option)) {
      add_option(self::$option);
    }

    //add a settings section for the option...
    add_settings_section(
      self::$section,
      self::$section_title,
      array($this, 'render_settings_section'),
      self::$option
    );

    //add a settings field for the amplify by default setting...
    add_settings_field(
      self::$amplify_by_default_option,
      self::$amplify_by_default_title,
      array($this, 'render_default_field'),
      self::$option,
      self::$section,
      array(self::$amplify_by_default_desc)
    );

    //register the new option...
    register_setting(self::$option, self::$option);
  }

  /**
   * Renders the Content Amp settings section and provided as a callback in
   * initialize_settings.
   */
  public function render_settings_section() {}

  /**
   * This function renders the amplify by default field.
   *
   * @param $args Sent from call to add_settings_field in initialize_settings.
   */
  public function render_default_field($args) {
    $option_id = self::$option;
    $options = get_option($option_id);

    $field_id = self::$amplify_by_default_option;
    $name = $option_id."[".$field_id."]";
    $desc = $args[0];
    ?>
    <input type="checkbox" id="<?php echo $field_id;?>" name="<?php echo $name;?>" value="1" <?php checked(self::$AMPLIFY_BY_DEFAULT, $options[$field_id], true);?> />
    <label for="<?php echo $field_id; ?>"><?php echo $desc;?></label>
    <?php
  }

  /**
   * This function should be called as a result of the Wordpress 'save_post' action.
   * It currently does not hook directly into the action because of sensitivities
   * around verifying a nonce, and user permissions which are better served at the main
   * plugin level.
   */
  public function on_save_post($postId, $http_post) {
    //assume we are not going to amplify the post...
    $amplify = self::$DO_NOT_AMPLIFY_BY_DEFAULT;

    //look at post data that triggered the save to see of the setting was set...
    if(array_key_exists(self::$amplify_by_default_meta, $http_post)) {
      $amplify = self::$AMPLIFY_BY_DEFAULT;
    }

    //update the meta data for the post...
    update_post_meta($postId, self::$amplify_by_default_meta, $amplify);
  }

  /**
   * This function is the callback for the 'rss2_item' hook. It's job is to read the Content Amp
   * related settings, from post meta data or global options, and construct and echo the resulting
   * structured data into the feed.
   */
  public function add_rss_tag() {
    global $post;

    //get the amplify by default setting and translate it to the XML attribute...
    $amplify = $this->get_amplify_by_default_setting($post->ID);
    $amplify = ($amplify == self::$DO_NOT_AMPLIFY_BY_DEFAULT) ? 'false' : 'true';

    //echo the result...
    echo '<izea:contentamp amplify="'.$amplify.'" />';
  }

  /**
   * This function determines what the current value for the amplify by default setting is
   * for a give post.
   *
   * @param $postId The ID of the post in question
   * @return int One the constant values for the post.
   */
  public function get_amplify_by_default_setting($postId) {
    //default to not amplifying by default...
    $amplify = self::$DO_NOT_AMPLIFY_BY_DEFAULT;

    //first check the site wide option in case a post meta data value has not been set...
    $options = get_option(self::$option);
    if(array_key_exists(self::$amplify_by_default_option, $options)) {
      $amplify = intval($options[self::$amplify_by_default_option]);
    }

    //now override the global setting with the post meta data if it exists...
    $post_meta_data = get_post_meta($postId);
    if(array_key_exists(self::$amplify_by_default_meta, $post_meta_data)) {
      $amplify = intval($post_meta_data[self::$amplify_by_default_meta][0]);
    }

    return $amplify;
  }
}
