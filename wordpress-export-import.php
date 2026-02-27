<?php
/**
 * Plugin Name: WordPress Export Import
 * Plugin URI: https://github.com/Jasoncheery/wordpress-export-import
 * Description: Simple full-site export/import plugin for cloning WordPress sites
 * Version: 1.0.0
 * Author: Jason Cheery
 * License: GPL v2 or later
 * Text Domain: wordpress-export-import
 * Requires at least: 5.0
 * Requires PHP: 7.2.24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WEI_VERSION', '1.0.0' );
define( 'WEI_PLUGIN_FILE', __FILE__ );
define( 'WEI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WEI_PLUGIN_DIR . 'includes/class-wei-plugin.php';

function wei_init() {
	WEI_Plugin::instance();
}
add_action( 'plugins_loaded', 'wei_init' );
