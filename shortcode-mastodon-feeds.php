<?php
/*
 * Plugin Name: Shortcode Mastodon Feeds
 * Plugin URI: 
 * Description: Display your Mastodon posts on a WordPress page
 * Version: 0.0.0
 * Author: Andrew Leahey, Jay McKinnon
 * Author URI: https://github.com/ajleahey/shortcode-mastodon-feeds
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: shortcode-mastodon-feeds
 * GitHub Plugin URI: https://github.com/ajleahey/shortcode-mastodon-feeds
*/

// Checks if our functions exist and creates them if not.
if ( ! function_exists( 'shortcodemastodonfeeds_init' ) ||
     ! function_exists( 'shortcodemastodonfeeds_display') ||
     ! function_exists( 'shortcodemastodonfeeds_get_posts') ||
     ! function_exists( 'shortcodemastodonfeeds_settings_field' ) ||
     ! function_exists( 'shortcodemastodonfeeds_settings_count' ) 
  ) {

  // registers a field in General Settings to define the RSS URL
  function shortcodemastodonfeeds_init() {
      register_setting( 'general', 'shortcodemastodonfeeds_url' );
      register_setting( 'general', 'shortcodemastodonfeeds_count' );
      add_settings_field('shortcodemastodonfeeds_url', '<label for="shortcodemastodonfeeds_url">'.__('URL for Shortcode Mastodon Feeds' , 'shortcodemastodonfeeds_url' ).'</label>' , 'shortcodemastodonfeeds_settings_url', 'general');
      add_settings_field('shortcodemastodonfeeds_count', '<label for="shortcodemastodonfeeds_count">'.__('How many posts displayed by Shortcode Mastodon Feeds' , 'shortcodemastodonfeeds_count' ).'</label>' , 'shortcodemastodonfeeds_settings_count', 'general');
  }
  add_filter('admin_init', 'shortcodemastodonfeeds_init');
  shortcodemastodonfeeds_plugin_page();

  // Exits name collision is found.
} else {
  exit('shortcodemastodonfeeds function(s) already exist');
}

// Set up the URL Settings field
function shortcodemastodonfeeds_settings_url() {
  $value = get_option( 'shortcodemastodonfeeds_url', '' );
  // input validation $pattern should accept any valid URL up to two sub-domains (https://subsubsub.subsub.sub.domain.tld/@user).
  $pattern = 'http(s?)(:\/\/)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)([a-zA-z0-9\-_]+)(\.)([a-zA-z0-9\-_]+)(\/)(@)([a-zA-z0-9\-_.]+)';
  // defines input field
  echo '<a name="shortcodemastodonfeeds"></a><input type="url" id="shortcodemastodonfeeds_url" name="shortcodemastodonfeeds_url" value="' . sanitize_url($value) . '" pattern="'. esc_attr($pattern) 
    .'" title="Mastodon profile URL must be in the form of https://domain.tld/@user" placeholder="https://mastodon.social/@user" style="width:30em;"/>';
}

// Set up the post count Settings field
function shortcodemastodonfeeds_settings_count() {
  $value = get_option( 'shortcodemastodonfeeds_count', '' );
  // input validation $pattern should accept any valid URL up to two sub-domains (https://subsubsub.subsub.sub.domain.tld/@user).
  $pattern = '[0-9]';
  // defines input field
  echo '<input type="url" id="shortcodemastodonfeeds_count" name="shortcodemastodonfeeds_count" value="' . sanitize_text_field(absint($value)) . '" pattern="'. esc_attr($pattern) 
    .'" title="Mastodon profile URL must be in the form of https://domain.tld/@user" placeholder="https://mastodon.social/@user" style="width:30em;"/>';
}

// Add link to Settings on Plugin page
function shortcodemastodonfeeds_plugin_page() {
  $plugin_file = basename( plugin_dir_path( __FILE__ ) ) . 'shortcode-mastodon-feeds.php';

  function shortcodemastodonfeeds_settings_link( $plugin_actions, $plugin_file ) {
    $new_actions = array();
    $new_actions['shortcodemastodonfeeds_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'shortcodemastodonfeeds_url' ), esc_url( admin_url( 'options-general.php#shortcodemastodonfeeds' ) ) );
    return array_merge( $new_actions, $plugin_actions );
  }
  add_filter( 'plugin_action_links', 'shortcodemastodonfeeds_settings_link', 'shortcode-mastodon-feeds.php', 2 );
}

function shortcodemastodonfeeds_get_posts() {
  // Define the RSS feed
  if ( ! empty( 'smverification_site_url' ) ) {
    $url = get_option( 'shortcodemastodonfeeds_url','' );
    $extension = '.rss';
    $rss = $url . $extension;
    $rss = preg_replace( "/.rss.rss/", ".rss", $rss );
  } else {
    $rss = wp_rss( 'https://esq.social/@andrew' );
  }

  // get the user-defined number of posts, defaults to 10.
  if ( ! empty( 'shortcodemastodonfeeds_count' ) ) {
    $count = get_option( 'shortcodemastodonfeeds_count','' );
    if ($count > 0) {
      $count = $count;
    }
  } else {
    $count = '10';
  }

  // wp_kses() uses $allowedtags to sanitize values The Wordpress Way.
  $allowedtags = array(
    'p' => array(),
  );

  // Fetch the RSS feed content
  if(function_exists('fetch_feed')) {
    include_once(ABSPATH . WPINC . '/feed.php');          // include the required file
    $feed_safe = fetch_feed($rss);                        // fetch feed, sanitized by core's WP_SimplePie_Sanitize_KSES
    $limit = $feed_safe->get_item_quantity($count);       // specify number of items
    $items = $feed_safe->get_items(0, $limit);            // create an array of items
  }

  // Initialize an empty array to store the posts
  $posts = [];

  // Loop through the items in the RSS feed
  foreach ($items as $item) {
    // Add the post to the array
    $content_safe = wp_kses($item->get_description(), $allowedtags);                    // sanitize the content
    $content_safe = preg_replace('/<\/p><p>/','<br>',$content_safe);                    // replace line in-text <p> tags with <br>
    $content_safe = preg_replace('/<p>/','',$content_safe);                             // remove leading <p> tags from content
    $content_safe = preg_replace('/<\/p>/','',$content_safe);                           // remove trailing <p> tags from content
    $url_pattern = '/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';       // regex matching URLs
    $content_safe = preg_replace($url_pattern, ' <a href="$0">$0</a>', $content_safe);  // replace URL with link

    $posts[] = [
      // Mastodon RSS feeds have no title
      // 'title' => (string) $item->title,
      'content' => $content_safe, 
      'pub_date' => esc_html($item->get_date('j F Y @ g:i a')),
      'link' => esc_url($item->get_permalink())
    ];
  }

  // Return the array of posts
  return $posts;
}

function shortcodemastodonfeeds_display() {
  // Get your Mastodon posts
  $posts = shortcodemastodonfeeds_get_posts();

  // Check if there are any posts to display
  if (empty($posts)) {
    return 'No Mastodon posts to display.';
  }

  // Create a container element for the posts
  $html = '<div id="mastodon-post-display">';

  // Loop through the posts and create a list of post elements
  foreach ($posts as $post) {
    $html .= '<div class="mastodon-post">';

    // Add the post title in bold
    // $html .= '<h3><strong>' . $post['title'] . '</strong></h3>';

    // Add the post content
    $html .= '<p>' . $post['content'] . '</p>';

    // Add the post publication date
    $html .= '<p><em><a href=' . $post['link'] . '>' . $post['pub_date'] . '</a></em></p>';

    $html .= '</div>';
  }

  // Close the container element
  $html .= '</div>';

  // Return the final HTML
  return $html;
}
add_shortcode('shortcode-mastodon-feeds', 'shortcodemastodonfeeds_display');