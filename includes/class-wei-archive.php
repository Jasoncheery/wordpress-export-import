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
	public function create_zip( $source_paths, $destination, $manifest = array() ) {
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
				$zip->addFile( $source_path, $local_name );
			} elseif ( is_dir( $source_path ) ) {
				$this->add_directory_to_zip( $zip, $source_path, $local_name );
			}
		}

		$zip->close();
	}

	/**
	 * Add directory recursively to ZIP
	 */
	private function add_directory_to_zip( $zip, $dir_path, $local_base ) {
		$dir_path = rtrim( $dir_path, '/' );
		$local_base = rtrim( $local_base, '/' );

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir_path, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			$item_path = $item->getPathname();
			$relative_path = substr( $item_path, strlen( $dir_path ) + 1 );
			$local_path = $local_base . '/' . $relative_path;

			if ( $item->isDir() ) {
				$zip->addEmptyDir( $local_path );
			} else {
				$zip->addFile( $item_path, $local_path );
			}
		}
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

	/**
	 * Stream ZIP download to browser
	 */
	public function stream_download( $file_path, $download_name ) {
		if ( ! file_exists( $file_path ) ) {
			throw new Exception( 'File not found' );
		}

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		readfile( $file_path );
	}
}
