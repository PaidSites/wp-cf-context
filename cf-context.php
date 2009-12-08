<?php
/*
Plugin Name: CF Context 
Plugin URI: http://crowdfavorite.com 
Description: Page/Post Context plugin 
Version: 1.1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}

function cfcn_get_context() {
	$context = array();
	$context = apply_filters('cfcn_context', $context);
	return $context;
}

// Add the local function filters
add_filter('cfcn_context', 'cfcn_add_categories', 10);
add_filter('cfcn_context', 'cfcn_add_tags', 10);
add_filter('cfcn_context', 'cfcn_add_author', 10);

function cfcn_add_categories($context) {
	global $post;
	if ($post->ID <= 0) { return $context; }
	
	$categories = wp_get_post_categories($post->ID);
	
	if (is_array($categories) && !empty($categories)) {
		foreach ($categories as $category) {
			$cat = get_category($category);
			if (count($categories) > 1) {
				$context['category'][] = $cat->slug;
			}
			else {
				$context['category'] = $cat->slug;
			}
		}
	}
	
	return $context;
}

function cfcn_add_tags($context) {
	global $post;
	if ($post->ID <= 0) { return $context; }
	
	$tags = wp_get_post_tags($post->ID);
	
	if (is_array($tags) && !empty($tags)) {
		foreach ($tags as $tag_id) {
			$tag = get_tag($tag_id);
			if (count($tags) > 1) {
				$context['tag'][] = $tag->slug;
			}
			else {
				$context['tag'] = $tag->slug;
			}
		}
	}
	
	return $context;
}

function cfcn_add_author($context) {
	global $authordata;
	
	if ($authordata->ID <= 0) { return $context; }
	
	$context['author'] = $authordata->user_login;
	
	return $context;
}

function cfcn_display() {
	$context = cfcn_get_context();
	
	echo '
	<div class="cfcn_context_addition" style="padding: 15px;">
		<h1>CF Context</h1>
		<p>The following items have been added by the CF Context plugin for addition for this page</p>
	';
	if (is_array($context) && !empty($context)) {
		foreach ($context as $key => $value) {
			if (is_array($value) && !empty($value)) {
				$values = '';
				$i = 1;
				foreach ($value as $key2 => $item) {
					$values .= urlencode($item);
					if ($i < count($value)) {
						$values .= ',';
					}
					$i++;
				}
				echo '
				<p>
					Name: '.$key.'<br />
					Value: '.$values.'<br />
				</p>
				';
			}
			else {
				echo '
				<p>
					Name: '.$key.'<br />
					Value: '.$value.'<br />
				</p>
				';
			}
		}
	}
	echo '
	</div>
	';
}
if (isset($_GET['cfcn_display']) && $_GET['cfcn_display'] == 'true') {
	add_action('wp_footer','cfcn_display');
}


function cfcn_cfox_options_help($html) {
	$html .= '
	<h3>CF Context</h3>
	<p>
		The CF Context plugin provides the ability to limit the display of ads in the ad system.  It does this by adding URL values that the OpenX system honors as limitations for a banner.  To View the Contextual items for a post or page, add ?cfcn_display to the end of the URL.<br /><br />
		Example:<br /><br />
		<code>
			'.trailingslashit(get_bloginfo('url')).'?cfcn_display=true
		</code>
	</p>
	';
	return $html;
}
add_filter('cfox_admin_page', 'cfcn_cfox_options_help');

?>