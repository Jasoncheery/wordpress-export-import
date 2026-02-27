<?php
/**
 * Site import service
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Importer {
	private $temp_dir;
	private $database;
	private $archive;
	private $manifest;

	public function __construct() {
		$this->database = new WEI_Database();
		$this->archive = new WEI_Archive();
	}

	/**
	 * Import site from ZIP
	 */
	public function import( $zip_path ) {
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '512M' );

		$this->check_requirements();
		$this->temp_dir = $this->create_temp_directory();

		try {
			$this->update_progress( 'Validating import file...' );
			$this->validate_zip( $zip_path );

			$this->update_progress( 'Extracting archive...' );
			$this->extract_archive( $zip_path );

			$this->update_progress( 'Enabling maintenance mode...' );
			$this->enable_maintenance_mode();

			$this->update_progress( 'Restoring wp-content files...' );
			$this->restore_wp_content();

			$this->update_progress( 'Restoring database...' );
			$this->restore_database();

			$this->update_progress( 'Updating site URLs...' );
			$this->update_site_urls();

			$this->update_progress( 'Clearing caches...' );
			$this->clear_caches();

			$this->update_progress( 'Finalizing...' );
			$this->disable_maintenance_mode();
			$this->cleanup();
			$this->clear_progress();
		} catch ( Exception $e ) {
			$this->disable_maintenance_mode();
			$this->cleanup();
			$this->clear_progress();
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

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( 'Insufficient permissions to perform import.' );
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
	 * Update import progress
	 */
	private function update_progress( $message ) {
		set_transient( 'wei_import_progress', $message, 300 );
	}

	/**
	 * Clear import progress
	 */
	private function clear_progress() {
		delete_transient( 'wei_import_progress' );
	}

	/**
	 * Validate ZIP and read manifest
	 */
	private function validate_zip( $zip_path ) {
		$this->manifest = $this->archive->read_manifest( $zip_path );

		if ( empty( $this->manifest['version'] ) ) {
			throw new Exception( 'Invalid export file: missing version' );
		}

		if ( empty( $this->manifest['site_url'] ) ) {
			throw new Exception( 'Invalid export file: missing site URL' );
		}
	}

	/**
	 * Extract archive to temp directory
	 */
	private function extract_archive( $zip_path ) {
		$this->archive->extract_zip( $zip_path, $this->temp_dir );

		if ( ! file_exists( $this->temp_dir . '/database.sql' ) ) {
			throw new Exception( 'Database file not found in export' );
		}
	}

	/**
	 * Enable maintenance mode
	 */
	private function enable_maintenance_mode() {
		$maintenance_file = ABSPATH . '.maintenance';
		$maintenance_content = '<?php $upgrading = ' . time() . '; ?>';
		file_put_contents( $maintenance_file, $maintenance_content );
	}

	/**
	 * Disable maintenance mode
	 */
	private function disable_maintenance_mode() {
		$maintenance_file = ABSPATH . '.maintenance';
		if ( file_exists( $maintenance_file ) ) {
			unlink( $maintenance_file );
		}
	}

	/**
	 * Restore wp-content directories
	 */
	private function restore_wp_content() {
		$wp_content_dir = WP_CONTENT_DIR;
		$source_wp_content = $this->temp_dir . '/wp-content';

		if ( ! is_dir( $source_wp_content ) ) {
			throw new Exception( 'wp-content directory not found in export' );
		}

		$dirs_to_restore = array( 'plugins', 'themes', 'uploads', 'mu-plugins' );

		foreach ( $dirs_to_restore as $dir_name ) {
			$source_dir = $source_wp_content . '/' . $dir_name;
			$dest_dir = $wp_content_dir . '/' . $dir_name;

			if ( is_dir( $source_dir ) ) {
				if ( is_dir( $dest_dir ) ) {
					$this->delete_directory( $dest_dir );
				}

				$this->copy_directory( $source_dir, $dest_dir );
			}
		}
	}

	/**
	 * Restore database
	 */
	private function restore_database() {
		$sql_file = $this->temp_dir . '/database.sql';
		$this->database->import_from_sql( $sql_file );
	}

	/**
	 * Update site URLs if different
	 */
	private function update_site_urls() {
		$old_url = $this->manifest['site_url'];
		$new_url = get_site_url();

		if ( $old_url !== $new_url ) {
			$old_url = rtrim( $old_url, '/' );
			$new_url = rtrim( $new_url, '/' );

			update_option( 'siteurl', $new_url );
			update_option( 'home', $new_url );

			$this->database->search_replace( $old_url, $new_url );
		}
	}

	/**
	 * Clear all caches
	 */
	private function clear_caches() {
		wp_cache_flush();
		delete_transient( 'wei_admin_notice' );

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
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

		$temp_dir = $temp_base . '/' . uniqid( 'import-' );
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
	 * Copy directory recursively
	 */
	private function copy_directory( $source, $destination ) {
		if ( ! is_dir( $source ) ) {
			return;
		}

		wp_mkdir_p( $destination );

		$items = scandir( $source );
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}

			$source_path = $source . '/' . $item;
			$dest_path = $destination . '/' . $item;

			if ( is_dir( $source_path ) ) {
				$this->copy_directory( $source_path, $dest_path );
			} else {
				copy( $source_path, $dest_path );
			}
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
