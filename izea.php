<?php
/**
 * Plugin Name: IZEA
 * Plugin URI: http://www.izea.com
 * Description: IZEA Wordpress Plugin
 * Version: 1.0.0
 * Author: IZEA, Inc.
 * Author URI: http://www.izea.com
 * License: GPL2
 */

//TODO: host xmlns

require_once("izea_published.php");
require_once("izea_rss.php");
require_once("izea_contentamp.php");

function izea_plugin_install() {
  $published = get_posts(array('post_status' => 'publish'));
  foreach($published as $post) {
    update_post_meta($post->ID, IZEAPublished::$meta_field, IZEAPublished::$HAS_BEEN_PUBLISHED);
  }
}
register_activation_hook(__FILE__, 'izea_plugin_install');

add_action('plugins_loaded', array(IZEAPlugin::get_instance(), 'init'));
class IZEAPlugin {
  private static $menu_title = 'IZEA Options';
  private static $menu_name = 'IZEA';
  private static $menu_capability = 'administrator';
  private static $menu_pageslug = 'izea';

  private static $meta_box_id = 'izea-meta-box';

  private static $izea_options_nonce = 'izea_options_nonce';

  protected static $instance = NULL;
  public static function get_instance() {
    NULL === self::$instance and self::$instance = new self;
    return self::$instance;
  }

  /** @var IZEAPublished $fPublished */
  private $fPublished = null;

  /** @var IZEARSS $fRss */
  private $fRss = null;

  /** @var IZEAContentAmp $fContenAmp */
  private $fContentAmp = null;

  /**
   * Initializes the main IZEA Plugin and all features involved. This function
   * is hooked into the 'plugins_loaded' Wordpress action as a bootstrapping step
   * to ensure everything is hooked up correctly.
   */
  public function init() {
    $this->fPublished = new IZEAPublished();
    $this->fPublished->init();

    $this->fRss = new IZEARSS();
    $this->fRss->init();

    $this->fContentAmp = new IZEAContentAmp();
    $this->fContentAmp->init();

    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('add_meta_boxes_post', array($this, 'add_meta_boxes'));
    add_action('save_post', array($this, 'save_post'));
  }

  /**
   * This function hooks into the 'admin_menu' Wordpress action and configures
   * the menu for the IZEA Plugin.
   */
  public function add_admin_menu() {
    add_menu_page(
      self::$menu_title,
      self::$menu_name,
      self::$menu_capability,
      self::$menu_pageslug,
      array($this, 'render_admin_menu'),
      plugin_dir_url(__FILE__).'logo.png'
    );
  }

  /**
   * Renders the admin menu for the IZEA Plugin and supplied as a callback in
   * add_admin_menu.
   */
  public function render_admin_menu() {
    ?>
    <div class="wrap">
      <h2><?php echo self::$menu_title; ?></h2>
      <form method="post" action="options.php">
        <?php settings_fields(IZEAContentAmp::$option); ?>
        <?php do_settings_sections(IZEAContentAmp::$option); ?>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  /**
   * Adds a meta box to a post to allow the user set options for the IZEA Plugin. This
   * function hooks into the 'add_meta_boxes_post' Wordpress action.
   *
   * @param $post The post to add a meta box for.
   */
  public function add_meta_boxes($post) {
    add_meta_box(
      self::$meta_box_id,
      self::$menu_name,
      array($this, 'render_izea_meta_box'),
      'post',
      'normal',
      'default'
    );
  }

  /**
   * This function renders the meta box for the IZEA Plugin and should contain all options
   * across all features of the plugin.
   *
   * @param $post The Wordpress post object for the post the box is being rendered for.
   * @param $box
   */
  public function render_izea_meta_box($post, $box) {
    //get the amplify by default setting for this post as well as the id and the description...
    $amplify_by_default = $this->fContentAmp->get_amplify_by_default_setting($post->ID);
    $amplify_by_default_id = IZEAContentAmp::$amplify_by_default_meta;
    $amplify_by_default_desc = IZEAContentAmp::$amplify_by_default_meta_desc;

    //render the part of the meta box for content amp...
    if(!$this->fPublished->has_post_been_published($post->ID)) {
      ?>
      <?php wp_nonce_field(basename(__FILE__), self::$izea_options_nonce); ?>
      <input type="checkbox" id="<?php echo $amplify_by_default_id; ?>" name="<?php echo $amplify_by_default_id; ?>" value="1" <?php checked(IZEAContentAmp::$AMPLIFY_BY_DEFAULT, $amplify_by_default, true); ?> />
      <label for="<?php echo $amplify_by_default_id; ?>"><?php echo $amplify_by_default_desc; ?></label>
      <?php
    } else {
      ?>
      <p><em>It looks like this post was previously published. Please visit <a href="https://www.izea.com">izea.com</a> to Amplify this Post.</em></p>
      <?php
    }
  }

  /**
   * This function hooks into Wordpress' 'save_post' action to allow individual features to take action. It
   * is currently in the main plugin file so that nonce's and user permissions can be checked before deferring
   * to the individual feature components.
   *
   * @param $post_id The id of the post being saved.
   * @param $post The Wordpress post object representing the post being saved.
   * @return mixed
   */
  public function save_post($post_id, $post) {
    if(!isset($_POST[self::$izea_options_nonce]) || !wp_verify_nonce($_POST[self::$izea_options_nonce], basename( __FILE__))) {
      return $post_id;
    }

    if(!current_user_can('edit_posts')) {
      return $post_id;
    }

    //call the ContentAmp save callback...
    $this->fContentAmp->on_save_post($post_id, $_POST);
  }
}

