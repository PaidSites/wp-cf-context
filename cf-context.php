<?php
/*
Plugin Name: CF Context 2015
Plugin URI: http://crowdfavorite.com 
Description: Page/Post Context plugin 
Version: 1.3.3
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// Constants
define('CFCN_VERSION', '1.3.1');


function cfcn_get_context() {
	$context = array();
	$context = apply_filters('cfcn_context', $context);
	return $context;
}

// Add the local function filters
add_filter('cfcn_context', 'cfcn_add_post_type', 10);
add_filter('cfcn_context', 'cfcn_add_post_name', 10);
add_filter('cfcn_context', 'cfcn_add_author', 10);
add_filter('cfcn_context', 'cfcn_add_taxonomies', 10);

//Check for the agora_authentication_plugin class. If it exists, add auth_context functions to cfcn_context filter
if (class_exists('agora_authentication_plugin')) {
	add_filter('cfcn_context', 'afps_cf_context_user_updated', 10);
	add_filter('cfcn_context', 'afps_cf_context_post_updated', 10);
}
function cfcn_add_post_type($context) {
	global $post;
	return array_merge($context, array('post_type' => $post->post_type));
}

function cfcn_add_post_name($context) {
	global $post;
	return array_merge($context, array('post_name' => $post->post_name), array('post_slug' => $post->post_name), array('permalink' => get_permalink($post->ID)));
}

function cfcn_add_taxonomies($context) {
	global $post;
	$taxonomy_types = get_object_taxonomies($post);
	foreach ($taxonomy_types as $tax_type) {
		$taxonomy_objects = wp_get_post_terms($post->ID, $tax_type);
		if (!empty($taxonomy_objects)) {
			foreach ($taxonomy_objects as $object) {
				$context[$object->taxonomy][] = $object->name;
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

// For Integration into the CF Context Plugin
// New version of the CF Context plugin with filters
function afps_cf_context_user_updated($context) {
	$agora_user_subscriptions = agora()->user->get_subscriptions();

	// Add the subscription data
	if (is_array($agora_user_subscriptions) && !empty($agora_user_subscriptions)) {
		foreach ($agora_user_subscriptions as $key => $subscription) {
			$key = $subscription->pubcode;
			$context['afps_user_subscriptions'][] = $key;
			$context['afps_user_subscriptions-PubCode-'.$key] = $subscription->pubcode;
			$context['afps_user_subscriptions-CircStatus-'.$key] = $subscription->status;
		}
	}

	return $context;
}

function afps_cf_context_post_updated($context) {
	global $wp_query;

	if (!is_array($context['pubCodes'])) {
		$context['pubCodes'] = array();
	}

	foreach ($wp_query as $key => $item) {
		if ($key == 'post') {
			$pubCode_ids = agora()->authentication->get_post_authcodes($item->ID);
			$pubCodes = get_authcodes_by_name($pubCode_ids);
			if (is_array($pubCodes) && !empty($pubCodes)) {
				foreach ($pubCodes as $pubCode) {
					if (!in_array($pubCode->name,$context['pubCodes'])) {
						$context['pubCodes'][] = $pubCode->name;
					}
				}
			}
		}
		if ($key == 'posts' && is_array($item) && !empty($item)) {
			foreach ($item as $post) {
				$pubCode_ids = agora()->authentication->get_post_authcodes($item->ID);
				$pubCodes = get_authcodes_by_name($pubCode_ids);
				if (is_array($pubCodes) && !empty($pubCodes)) {
					foreach ($pubCodes as $pubCode) {
						if (!in_array($pubCode->name,$context['pubCodes'])) {
							$context['pubCodes'][] = $pubCode->name;
						}
					}
				}
			}
		}
	}

	if (empty($context['pubCodes'])) {
		unset($context['pubCodes']);
	}
	return $context;
}

function cfcn_build_context($params) {
	$contexts = cfcn_get_context();
	
	$contexts = apply_filters('cfcn_build_context', $contexts);
	
	if (is_array($contexts) && !empty($contexts)) {
		foreach ($contexts as $key => $value) {
			if (is_array($value) && !empty($value)) {
				$params .= '&'.urlencode($key).'=';
				$i = 1;
				foreach ($value as $key2 => $item) {
					$params .= urlencode($item);
					if ($i < count($value)) {
						$params .= ',';
					}
					$i++;
				}
			}
			else {
				$params .= '&'.urlencode($key).'='.urlencode($value);
			}
		}	
	}
	
	return $params;
}
add_filter('cfox_params', 'cfcn_build_context');

function cfcn_display() {
	echo '
	<div class="cfcn_context_addition" style="padding: 15px; color: black; background-color:#FFFFFF;">
		<h1>CF Context</h1>
		<p>The following items have been added by the CF Context plugin for addition for this page</p>
	';
	
	$context = apply_filters('cfcn_display', cfcn_get_context());
	if (is_array($context) && !empty($context)) {
		echo '<ul>';
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
			}
			else {
				$values = $value;
			}
			echo '
			<li>
				key: '.$key.'<br />
				value: '.$values.'<br />
			</li>
			';
		}
		echo '</ul>';
	}
	echo '
	</div>
	';
}
if (!empty($_GET['cfcn_display']) && $_GET['cfcn_display'] == 'true') {
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

// README HANDLING
add_action('admin_init','cfcn_add_readme');

/**
 * Enqueue the readme function
 */
function cfcn_add_readme() {
	if(function_exists('cfreadme_enqueue')) {
		cfreadme_enqueue('cf-context','cfcn_readme');
	}
}

/**
 * return the contents of the links readme file
 * replace the image urls with full paths to this plugin install
 *
 * @return string
 */
function cfcn_readme() {
	$file = realpath(dirname(__FILE__)).'/readme/readme.txt';
	if(is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|','![$1]('.WP_PLUGIN_URL.'/cf-context/readme/$2)',$markdown);
		return $markdown;
	}
	return null;
}

?>
