<?php
/**
 * Archive creation and extraction helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Archive {
	/**
	 * Create ZIP archive
	 */
	public function create_zip( $source_paths, $destination, $manifest = array(), $exclude_paths = array() ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Exception( 'ZipArchive class not available' );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
			throw new Exception( 'Cannot create ZIP file' );
		}

		if ( ! empty( $manifest ) ) {
			$zip->addFromString( 'manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT ) );
		}

		foreach ( $source_paths as $local_name => $source_path ) {
			if ( is_file( $source_path ) ) {
				if ( ! $this->is_excluded( $source_path, $exclude_paths ) ) {
					$zip->addFile( $source_path, $local_name );
					if ( method_exists( $zip, 'setCompressionName' ) ) {
						$zip->setCompressionName( $local_name, ZipArchive::CM_STORE );
					}
				}
			} elseif ( is_dir( $source_path ) ) {
				$this->add_directory_to_zip( $zip, $source_path, $local_name, $exclude_paths );
			}
		}

		$zip->close();
	}

	/**
	 * Add directory recursively to ZIP
	 */
	private function add_directory_to_zip( $zip, $dir_path, $local_base, $exclude_paths = array() ) {
		$dir_path = rtrim( $dir_path, '/' );
		$local_base = rtrim( $local_base, '/' );

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir_path, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			$item_path = $item->getPathname();
			if ( $this->is_excluded( $item_path, $exclude_paths ) ) {
				continue;
			}
			$relative_path = substr( $item_path, strlen( $dir_path ) + 1 );
			$local_path = $local_base . '/' . $relative_path;

			if ( $item->isDir() ) {
				$zip->addEmptyDir( $local_path );
			} else {
				$zip->addFile( $item_path, $local_path );
				if ( method_exists( $zip, 'setCompressionName' ) ) {
					$zip->setCompressionName( $local_path, ZipArchive::CM_STORE );
				}
			}
		}
	}

	/**
	 * Check if a path should be excluded from archive.
	 */
	private function is_excluded( $path, $exclude_paths ) {
		$normalized_path = wp_normalize_path( realpath( $path ) ?: $path );
		foreach ( $exclude_paths as $exclude_path ) {
			$normalized_exclude = wp_normalize_path( realpath( $exclude_path ) ?: $exclude_path );
			if ( strpos( $normalized_path, untrailingslashit( $normalized_exclude ) ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Extract ZIP archive
	 */
	public function extract_zip( $zip_path, $destination ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Exception( 'ZipArchive class not available' );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $zip_path ) !== true ) {
			throw new Exception( 'Cannot open ZIP file' );
		}

		if ( ! $zip->extractTo( $destination ) ) {
			$zip->close();
			throw new Exception( 'Cannot extract ZIP file' );
		}

		$zip->close();
	}

	/**
	 * Read manifest from ZIP
	 */
	public function read_manifest( $zip_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Exception( 'ZipArchive class not available' );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $zip_path ) !== true ) {
			throw new Exception( 'Cannot open ZIP file' );
		}

		$manifest_content = $zip->getFromName( 'manifest.json' );
		$zip->close();

		if ( false === $manifest_content ) {
			throw new Exception( 'Manifest not found in ZIP' );
		}

		$manifest = json_decode( $manifest_content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Exception( 'Invalid manifest JSON' );
		}

		return $manifest;
	}

}
