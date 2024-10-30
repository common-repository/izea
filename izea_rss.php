<?php

/**
 * Class IZEARSS
 *
 * This class encapsulates the non-per-post RSS and Atom feed functionality of the IZEA Plugin. Currently
 * it's goal is to simply put the XML namespace schema verification document into the feeds.
 *
 * You should  call this class' init() function when the plugin is created. This class then hooks into the
 * 'rss2_ns' Wordpress Action to modify the list of namespaces inserted into the feed.
 */
class IZEARSS {
  /** @var string $xmlns The XML Namespace string */
  private static $xmlns = 'xmlns:izea="http://xml.izea.com" ';

  /**
   * Call init() when the plugin is created to hook into the Wordpress actions that are required.
   */
  public function init() {
    add_action('rss2_ns', array($this, 'add_rss_namespace'));
    add_action('atom_ns', array($this, 'add_rss_namespace'));
  }

  /**
   * This function is the callback for the Wordpress 'rss2_ns' action.
   */
  public function add_rss_namespace() {
    echo self::$xmlns;
  }
}
