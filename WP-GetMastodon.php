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

// WP plugin standard requires unique prefix to all functions. Suggesting "mastodonshortcode_"

// Checks if our functions exist and creates them if not.
if ( ! function_exists( 'mastodonshortcode_init' ) || ! function_exists( 'mastodonshortcode_display') || ! function_exists( 'mastodonshortcode_get_posts') || ! function_exists('mastodonshortcode_settings_field' ) ) {

  // registers a field in General Settings to define the RSS URL
  function mastodonshortcode_init() {
      register_setting( 'general', 'mastodonshortcode_url' );
      add_settings_field('mastodonshortcode_url', '<label for="mastodonshortcode_url">'.__('Verify Mastodon profile' , 'mastodonshortcode_url' ).'</label>' , 'mastodonshortcode_settings_field', 'general');
  }
  add_filter('admin_init', 'mastodonshortcode_init');

  //Exits name collision is found.
} else {
  exit("mastodonshortcode functions already exist");
}

// Set up the Settings field
function mastodonshortcode_settings_field() {
  $value = get_option( 'mastodonshortcode_url', '' );
  // input validation $pattern should accept any valid URL up to two sub-domains (https://subsubsub.subsub.sub.domain.tld/@user).
  $pattern = 'http(s?)(:\/\/)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)(([a-zA-z0-9\-_]+(\.))?)([a-zA-z0-9\-_]+)(\.)([a-zA-z0-9\-_]+)(\/)(@)([a-zA-z0-9\-_.]+)';
  // defines input field
  echo '<input type="url" id="mastodonshortcode_url" name="mastodonshortcode_url" value="' . esc_url($value) . '" pattern="'. esc_attr($pattern) .'" title="Mastodon profile URL must be in the form of https://domain.tld/@user" placeholder="https://mastodon.social/@user" style="width:30em;"/>';
}

function mastodonshortcode_get_posts() {
  // Define the RSS feed
  if ( ! empty( 'smverification_site_url' ) ) {
    $url = get_option( 'mastodonshortcode_url','' );
    $extension = '.rss';
    $rss = $url . $extension;
  } else {
    $rss = wp_rss( 'https://esq.social/@andrew.rss' );
  }
  // Fetch the RSS feed content
  if(function_exists('fetch_feed')) {
    include_once(ABSPATH . WPINC . '/feed.php');  // include the required file
    $feed = fetch_feed($rss); // specify the source feed
    $limit = $feed->get_item_quantity(7); // specify number of items
    $items = $feed->get_items(0, $limit); // create an array of items
  }

  foreach ($items as $item) {
    echo '<li><a href="' . esc_url($item->get_permalink()) . '" title="' . esc_html($item->get_date('j F Y @ g:i a')) . '">' . esc_html($item->get_title()) . '</a><br>' . esc_html($item->get_description()) . '</li>';
  }

  /*
  // Initialize an empty array to store the posts
  $posts = [];

  // Loop through the items in the RSS feed
  foreach ($items as $item) {
    // Add the post to the array
    $posts[] = [
      //'title' => (string) $item->title,
      'content' => $item->description,
      'pub_date' => $item->pubDate,
      'link' => $item->link
    ];
  }

  // Return the array of posts
  
  return $posts;
  */
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
    $html .= '<h3><strong>' . $post['title'] . '</strong></h3>';

    // Add the post content
    $html .= '<p>' . $post['content'] . '</p>';

    // Add the post publication date
    $html .= '<p><em>' . $post['pub_date'] . '</em></p>';

    $html .= '</div>';
  }

  // Close the container element
  $html .= '</div>';

  // Return the final HTML
  return $html;
}
add_shortcode('mastodon-post-display', 'mastodonshortcode_display');