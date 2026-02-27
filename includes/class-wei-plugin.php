<?php
/**
 * Main plugin orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		require_once WEI_PLUGIN_DIR . 'includes/class-wei-database.php';
		require_once WEI_PLUGIN_DIR . 'includes/class-wei-archive.php';
		require_once WEI_PLUGIN_DIR . 'includes/class-wei-exporter.php';
		require_once WEI_PLUGIN_DIR . 'includes/class-wei-importer.php';
		require_once WEI_PLUGIN_DIR . 'includes/class-wei-admin.php';
	}

	private function init_hooks() {
		if ( is_admin() ) {
			WEI_Admin::instance();
		}
	}
}
