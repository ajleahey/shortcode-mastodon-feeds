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

function mastodon_post_display_shortcode() {
  // Get your Mastodon posts
  $posts = get_mastodon_posts();

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
add_shortcode('mastodon-post-display', 'mastodon_post_display_shortcode');

function get_mastodon_posts() {
  // Get the RSS feed content
  $rss = file_get_contents('https://esq.social/@andrew.rss');

  // Load the RSS feed into a SimpleXML object
  $xml = simplexml_load_string($rss);

  // Initialize an empty array to store the posts
  $posts = [];

  // Loop through the items in the RSS feed
  foreach ($xml->channel->item as $item) {
    // Add the post to the array
    $posts[] = [
      'title' => (string) $item->title,
      'content' => (string) $item->description,
      'pub_date' => (string) $item->pubDate,
    ];
  }

  // Return the array of posts
  return $posts;
}
