<?php

/**
 * Class IZEAPublished
 *
 * This class encapsulates the functionality of the greater IZEA Plugin that determines whether
 * or not a Post has ever been published before or not. In order to set the default state of
 * posts o plugin activation, it is recommended to add the following bootstrap code to the
 * main plugin file:
 *
 * function izea_plugin_install() {
 *   $published = get_posts(array('post_status' => 'publish'));
 *   foreach($published as $post) {
 *     update_post_meta($post->ID, IZEAPublished::$meta_field, IZEAPublished::$HAS_BEEN_PUBLISHED);
 *   }
 * } register_activation_hook(__FILE__, 'izea_plugin_install');
 *
 * You should also call this class' init() function when the plugin is created. Upon doing the above,
 * the plugin hooks into the 'publish_post' Wordpress Action to create a piece of meta-data about a post
 * signaling that it has been published at least once. This flag can be used throughout other components
 * of the IZEA plugin through calls to has_post_been_published().
 */
class IZEAPublished {
  /** @var string $meta_field The field in the Wordpress meta data that stores if a post has been published */
  public static $meta_field = 'izea_post_has_been_published';

  /** @var int $HAS_BEEN_PUBLISHED The value stored in the meta field if a post has been published before */
  public static $HAS_BEEN_PUBLISHED = 1;

  /**
   * Call init() when the plugin is created to hook into the Wordpress actions that are required.
   */
  public function init() {
    //hook into 'publish_post' so we can make when the post gets published the first time...
    add_action('publish_post', array($this, 'on_publish'));
  }

  /**
   * This function is the callback for the Wordpress 'publish_post' action.
   *
   * @param $postId The ID of the post being published
   * @param $post The Wordpress post object that is being published
   */
  public function on_publish($postId, $post) {
    //update the post meta data to indicate that this post has been published at least once.
    update_post_meta($postId, self::$meta_field, self::$HAS_BEEN_PUBLISHED);
  }

  /**
   * Function to determine, based on post meta data, if a post has been pubilshed at least
   * once before.
   *
   * @param $postId The ID pf the post in question
   * @return bool True if the post has been published at least once, false otherwise
   */
  public function has_post_been_published($postId) {
    $meta_data = get_post_meta($postId);
    if(array_key_exists(self::$meta_field, $meta_data)) {
      return intval($meta_data[self::$meta_field][0]) === self::$HAS_BEEN_PUBLISHED;
    } else {
      return false;
    }
  }
}
