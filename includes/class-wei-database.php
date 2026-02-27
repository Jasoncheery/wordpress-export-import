<?php
/**
 * Database dump and restore helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Database {
	private $wpdb;
	private $batch_size = 100;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Export all WordPress tables to SQL
	 */
	public function export_to_sql( $file_path ) {
		$tables = $this->get_wp_tables();
		$handle = fopen( $file_path, 'w' );

		if ( ! $handle ) {
			throw new Exception( 'Cannot create SQL file' );
		}

		fwrite( $handle, "-- WordPress Database Export\n" );
		fwrite( $handle, "-- Generated: " . date( 'Y-m-d H:i:s' ) . "\n\n" );
		fwrite( $handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" );
		fwrite( $handle, "SET time_zone = \"+00:00\";\n\n" );

		foreach ( $tables as $table ) {
			$this->export_table( $handle, $table );
		}

		fclose( $handle );
	}

	/**
	 * Export single table
	 */
	private function export_table( $handle, $table ) {
		fwrite( $handle, "\n-- Table: {$table}\n" );
		fwrite( $handle, "DROP TABLE IF EXISTS `{$table}`;\n" );

		$create_table = $this->wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
		if ( $create_table ) {
			fwrite( $handle, $create_table[1] . ";\n\n" );
		}

		$offset = 0;
		while ( true ) {
			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM `{$table}` LIMIT %d OFFSET %d",
					$this->batch_size,
					$offset
				),
				ARRAY_A
			);

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$values = array();
				foreach ( $row as $value ) {
					if ( is_null( $value ) ) {
						$values[] = 'NULL';
					} else {
						$values[] = "'" . $this->wpdb->_real_escape( $value ) . "'";
					}
				}
				fwrite( $handle, "INSERT INTO `{$table}` VALUES (" . implode( ',', $values ) . ");\n" );
			}

			$offset += $this->batch_size;
		}
	}

	/**
	 * Import SQL file with chunked reading
	 */
	public function import_from_sql( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			throw new Exception( 'SQL file not found' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			throw new Exception( 'Cannot open SQL file' );
		}

		$buffer = '';
		$line_count = 0;

		while ( ! feof( $handle ) ) {
			$line = fgets( $handle );
			$line_count++;

			if ( empty( trim( $line ) ) || strpos( trim( $line ), '--' ) === 0 ) {
				continue;
			}

			$buffer .= $line;

			if ( strpos( $line, ';' ) !== false ) {
				$statement = trim( $buffer );
				if ( ! empty( $statement ) ) {
					$result = $this->wpdb->query( $statement );
					if ( false === $result && ! empty( $this->wpdb->last_error ) ) {
						fclose( $handle );
						throw new Exception( 'SQL import error at line ' . $line_count . ': ' . $this->wpdb->last_error );
					}
				}
				$buffer = '';
			}

			if ( $line_count % 100 === 0 ) {
				wp_cache_flush();
			}
		}

		fclose( $handle );
	}

	/**
	 * Split SQL into individual statements
	 */
	private function split_sql_statements( $sql ) {
		$statements = array();
		$buffer = '';
		$in_string = false;
		$string_char = '';

		for ( $i = 0; $i < strlen( $sql ); $i++ ) {
			$char = $sql[ $i ];

			if ( ! $in_string && ( $char === "'" || $char === '"' ) ) {
				$in_string = true;
				$string_char = $char;
			} elseif ( $in_string && $char === $string_char && $sql[ $i - 1 ] !== '\\' ) {
				$in_string = false;
			}

			$buffer .= $char;

			if ( ! $in_string && $char === ';' ) {
				$statements[] = $buffer;
				$buffer = '';
			}
		}

		if ( ! empty( trim( $buffer ) ) ) {
			$statements[] = $buffer;
		}

		return $statements;
	}

	/**
	 * Get all WordPress tables
	 */
	private function get_wp_tables() {
		$tables = $this->wpdb->get_col( 'SHOW TABLES' );
		$prefix = $this->wpdb->prefix;
		$wp_tables = array();

		foreach ( $tables as $table ) {
			if ( strpos( $table, $prefix ) === 0 ) {
				$wp_tables[] = $table;
			}
		}

		return $wp_tables;
	}

	/**
	 * Search and replace in database (for URL changes)
	 */
	public function search_replace( $search, $replace ) {
		$tables = $this->get_wp_tables();

		foreach ( $tables as $table ) {
			$columns = $this->wpdb->get_results( "DESCRIBE `{$table}`", ARRAY_A );

			foreach ( $columns as $column ) {
				$column_name = $column['Field'];
				$column_type = $column['Type'];

				if ( strpos( $column_type, 'char' ) !== false || strpos( $column_type, 'text' ) !== false ) {
					$this->wpdb->query(
						$this->wpdb->prepare(
							"UPDATE `{$table}` SET `{$column_name}` = REPLACE(`{$column_name}`, %s, %s)",
							$search,
							$replace
						)
					);
				}
			}
		}
	}
}
