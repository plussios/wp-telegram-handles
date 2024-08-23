<?php
/**
 * @package Telegram_Handles
 * @version 1.0.0
 */
/*
Plugin Name: Telegram Handles
Description: Allows users to store up to 5 Telegram handles and provides shortcode and block editor support.
Version: 1.0.0
Author: Stan Sidel
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define( 'THP__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook(__FILE__, array('THP', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('THP', 'plugin_deactivation'));

require_once THP__PLUGIN_DIR . 'class.thp.php';
require_once THP__PLUGIN_DIR . 'class.thp-rest-api.php';
require_once THP__PLUGIN_DIR . 'class.thp-admin.php';

add_action( 'init', array( 'THP', 'init' ) );
add_action( 'rest_api_init', array( 'THP_REST_API', 'init' ) );

if ( is_admin() ) {
	require_once THP__PLUGIN_DIR . 'class.thp-admin.php';
	add_action( 'init', array( 'THP_Admin', 'init' ) );
}
