<?php
/*
 * Plugin Name: Mastodon Post Display
 * Plugin URI: 
 * Description: Display your Mastodon posts on a WordPress page
 * Version: 0.0.0
 * Author: Andrew Leahey
 * Author URI: http://andrew.legal
 * License: 
 * License URI: 
 * Text Domain: wp-mastodonposts
 * GitHub Plugin URI: https://github.com/ajleahey/wp-mastodonposts
*/

// Checks if our functions exist and creates them if not.
if ( ! function_exists( 'mastodonshortcode_init' ) ||
     ! function_exists( 'mastodonshortcode_display') ||
     ! function_exists( 'mastodonshortcode_get_posts') ||
     ! function_exists( 'mastodonshortcode_settings_field' ) ||
     ! function_exists( 'mastodonshortcode_settings_count' ) 
  ) {

  // registers a field in General Settings to define the RSS URL
  function mastodonshortcode_init() {
      register_setting( 'general', 'mastodonshortcode_url' );
      register_setting( 'general', 'mastodonshortcode_count' );
      add_settings_field('mastodonshortcode_url', '<label for="mastodonshortcode_url">'.__('URL for Mastodon shortcode' , 'mastodonshortcode_url' ).'</label>' , 'mastodonshortcode_settings_url', 'general');
      add_settings_field('mastodonshortcode_count', '<label for="mastodonshortcode_count">'.__('How many posts displayed by Mastodon shortcode' , 'mastodonshortcode_count' ).'</label>' , 'mastodonshortcode_settings_count', 'general');
  }
  add_filter('admin_init', 'mastodonshortcode_init');

  // Exits name collision is found.
} else {
  exit('mastodonshortcode function(s) already exist');
}

// Set up the URL Settings field
function mastodonshortcode_settings_url() {
  $value = get_option( 'mastodonshortcode_url', '' );
  // input validation $pattern should accept any valid URL up to two sub-domains (https://subsubsub.subsub.sub.domain.tld/@user).
  $pattern = 'http(s?)(:\/\/)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)([a-zA-z0-9\-_]+)(\.)([a-zA-z0-9\-_]+)(\/)(@)([a-zA-z0-9\-_.]+)';
  // defines input field
  echo '<input type="url" id="mastodonshortcode_url" name="mastodonshortcode_url" value="' . esc_url($value) . '" pattern="'. esc_attr($pattern) 
    .'" title="Mastodon profile URL must be in the form of https://domain.tld/@user" placeholder="https://mastodon.social/@user" style="width:30em;"/>';
}

// Set up the post count Settings field
function mastodonshortcode_settings_count() {
  $value = get_option( 'mastodonshortcode_count', '' );
  // input validation $pattern should accept any valid URL up to two sub-domains (https://subsubsub.subsub.sub.domain.tld/@user).
  $pattern = '[0-9]';
  // defines input field
  echo '<input type="url" id="mastodonshortcode_count" name="mastodonshortcode_count" value="' . absint($value) . '" pattern="'. esc_attr($pattern) 
    .'" title="Mastodon profile URL must be in the form of https://domain.tld/@user" placeholder="https://mastodon.social/@user" style="width:30em;"/>';
}


function mastodonshortcode_get_posts() {
  // Define the RSS feed
  if ( ! empty( 'smverification_site_url' ) ) {
    $url = get_option( 'mastodonshortcode_url','' );
    $extension = '.rss';
    $rss = $url . $extension;
    $rss = preg_replace( "/.rss.rss/", ".rss", $rss );
  } else {
    $rss = wp_rss( 'https://esq.social/@andrew' );
  }

  // get the user-defined number of posts, defaults to 10.
  if ( ! empty( 'mastodonshortcode_count' ) ) {
    $count = get_option( 'mastodonshortcode_count','' );
  } else {
    $count = '10';
  }

  // Fetch the RSS feed content
  if(function_exists('fetch_feed')) {
    include_once(ABSPATH . WPINC . '/feed.php');   // include the required file
    $feed = fetch_feed($rss);                      // specify the source feed
    $limit = $feed->get_item_quantity($count);          // specify number of items
    $items = $feed->get_items(0, $limit);          // create an array of items
  }

  // Initialize an empty array to store the posts
  $posts = [];

  // Loop through the items in the RSS feed
  foreach ($items as $item) {
    // Add the post to the array
    $content = strip_tags($item->get_description());                                  // sanitize input
    $content_safe = esc_attr(preg_replace( "/http/", " http", $content ));            // add space before URLs concatenated with preceeding text by strip_tags()
    $url_pattern = '/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';     // regex matching URLs
    $content_safe= preg_replace($url_pattern, '<a href="$0">$0</a>', $content_safe);  // replace URL with link

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

function mastodonshortcode_display() {
  // Get your Mastodon posts
  $posts = mastodonshortcode_get_posts();

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
add_shortcode('mastodon-post-display', 'mastodonshortcode_display');