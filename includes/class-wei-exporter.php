<?php
/**
 * Site export service
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Exporter {
	private $temp_dir;
	private $database;
	private $archive;

	public function __construct() {
		$this->database = new WEI_Database();
		$this->archive = new WEI_Archive();
	}

	/**
	 * Export entire site
	 */
	public function export() {
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '512M' );

		$this->check_requirements();
		$this->temp_dir = $this->create_temp_directory();

		try {
			$manifest = $this->build_manifest();
			$this->export_database();
			$source_paths = $this->collect_wp_content();

			$zip_path = $this->temp_dir . '/site-export.zip';
			$this->archive->create_zip( $source_paths, $zip_path, $manifest );

			$download_name = 'wordpress-export-' . date( 'Y-m-d-His' ) . '.zip';
			$this->archive->stream_download( $zip_path, $download_name );

			$this->cleanup();
		} catch ( Exception $e ) {
			$this->cleanup();
			throw $e;
		}
	}

	/**
	 * Check system requirements
	 */
	private function check_requirements() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Exception( 'ZipArchive extension is required but not available on this server.' );
		}

		$upload_dir = wp_upload_dir();
		if ( ! wp_is_writable( $upload_dir['basedir'] ) ) {
			throw new Exception( 'Upload directory is not writable. Please check file permissions.' );
		}

		$free_space = @disk_free_space( $upload_dir['basedir'] );
		if ( $free_space !== false && $free_space < 100 * 1024 * 1024 ) {
			throw new Exception( 'Insufficient disk space. At least 100MB free space required.' );
		}
	}

	/**
	 * Build manifest with site metadata
	 */
	private function build_manifest() {
		global $wp_version;

		return array(
			'version' => WEI_VERSION,
			'timestamp' => time(),
			'site_url' => get_site_url(),
			'home_url' => get_home_url(),
			'wp_version' => $wp_version,
			'php_version' => PHP_VERSION,
			'table_prefix' => $GLOBALS['wpdb']->prefix,
			'active_plugins' => get_option( 'active_plugins', array() ),
			'active_theme' => get_stylesheet(),
		);
	}

	/**
	 * Export database to SQL file
	 */
	private function export_database() {
		$sql_path = $this->temp_dir . '/database.sql';
		$this->database->export_to_sql( $sql_path );
	}

	/**
	 * Collect wp-content directories
	 */
	private function collect_wp_content() {
		$wp_content_dir = WP_CONTENT_DIR;
		$source_paths = array();

		$source_paths['database.sql'] = $this->temp_dir . '/database.sql';

		$dirs_to_export = array(
			'plugins' => $wp_content_dir . '/plugins',
			'themes' => $wp_content_dir . '/themes',
			'uploads' => $wp_content_dir . '/uploads',
		);

		if ( is_dir( $wp_content_dir . '/mu-plugins' ) ) {
			$dirs_to_export['mu-plugins'] = $wp_content_dir . '/mu-plugins';
		}

		foreach ( $dirs_to_export as $local_name => $dir_path ) {
			if ( is_dir( $dir_path ) ) {
				$source_paths[ 'wp-content/' . $local_name ] = $dir_path;
			}
		}

		return $source_paths;
	}

	/**
	 * Create temporary directory
	 */
	private function create_temp_directory() {
		$upload_dir = wp_upload_dir();
		$temp_base = $upload_dir['basedir'] . '/wei-temp';

		if ( ! is_dir( $temp_base ) ) {
			wp_mkdir_p( $temp_base );
		}

		$temp_dir = $temp_base . '/' . uniqid( 'export-' );
		wp_mkdir_p( $temp_dir );

		return $temp_dir;
	}

	/**
	 * Cleanup temporary files
	 */
	private function cleanup() {
		if ( $this->temp_dir && is_dir( $this->temp_dir ) ) {
			$this->delete_directory( $this->temp_dir );
		}
	}

	/**
	 * Recursively delete directory
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = scandir( $dir );
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}

			$path = $dir . '/' . $item;
			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				unlink( $path );
			}
		}

		rmdir( $dir );
	}
}
