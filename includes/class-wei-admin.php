<?php
/**
 * Admin UI and form handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEI_Admin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_wei_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_wei_import', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
	}

	public function add_admin_menu() {
		add_management_page(
			__( 'Export/Import Site', 'wordpress-export-import' ),
			__( 'Export/Import', 'wordpress-export-import' ),
			'manage_options',
			'wei-export-import',
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'wordpress-export-import' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Export Site', 'wordpress-export-import' ); ?></h2>
				<p><?php esc_html_e( 'Export your entire WordPress site including database, plugins, themes, uploads, and users.', 'wordpress-export-import' ); ?></p>
				<?php $last_export = get_option( 'wei_last_export', array() ); ?>
				<?php if ( ! empty( $last_export['file_url'] ) && ! empty( $last_export['file_name'] ) && ! empty( $last_export['file_path'] ) && file_exists( $last_export['file_path'] ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Latest export:', 'wordpress-export-import' ); ?></strong>
						<a class="button button-secondary" href="<?php echo esc_url( $last_export['file_url'] ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Download ZIP', 'wordpress-export-import' ); ?>
						</a>
						<code><?php echo esc_html( $last_export['file_name'] ); ?></code>
					</p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wei_export_action', 'wei_export_nonce' ); ?>
					<input type="hidden" name="action" value="wei_export">
					<?php submit_button( __( 'Export Site', 'wordpress-export-import' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Import Site', 'wordpress-export-import' ); ?></h2>
				<div class="notice notice-error inline">
					<p><strong><?php esc_html_e( 'WARNING:', 'wordpress-export-import' ); ?></strong> <?php esc_html_e( 'Import will COMPLETELY REPLACE all database content and wp-content files. Always export your destination site first as a backup!', 'wordpress-export-import' ); ?></p>
				</div>
				<p><?php esc_html_e( 'Upload a site export ZIP file to restore it on this WordPress installation.', 'wordpress-export-import' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wei_import_action', 'wei_import_nonce' ); ?>
					<input type="hidden" name="action" value="wei_import">
					<p>
						<input type="file" name="wei_import_file" accept=".zip" required>
					</p>
					<?php submit_button( __( 'Import Site', 'wordpress-export-import' ), 'primary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'wordpress-export-import' ) );
		}

		check_admin_referer( 'wei_export_action', 'wei_export_nonce' );

		try {
			$exporter = new WEI_Exporter();
			$result = $exporter->export();
			update_option( 'wei_last_export', $result, false );
			$this->set_notice( 'success', __( 'Export completed. Use the "Download ZIP" button on this page.', 'wordpress-export-import' ) );
		} catch ( Exception $e ) {
			$this->set_notice( 'error', $e->getMessage() );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=wei-export-import' ) );
		exit;
	}

	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions', 'wordpress-export-import' ) );
		}

		check_admin_referer( 'wei_import_action', 'wei_import_nonce' );

		if ( empty( $_FILES['wei_import_file'] ) || $_FILES['wei_import_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->set_notice( 'error', __( 'File upload failed', 'wordpress-export-import' ) );
			wp_safe_redirect( admin_url( 'tools.php?page=wei-export-import' ) );
			exit;
		}

		try {
			$importer = new WEI_Importer();
			$importer->import( $_FILES['wei_import_file']['tmp_name'] );
			$this->set_notice( 'success', __( 'Site imported successfully!', 'wordpress-export-import' ) );
		} catch ( Exception $e ) {
			$this->set_notice( 'error', $e->getMessage() );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=wei-export-import' ) );
		exit;
	}

	private function set_notice( $type, $message ) {
		set_transient( 'wei_admin_notice', array( 'type' => $type, 'message' => $message ), 30 );
	}

	public function show_notices() {
		$notice = get_transient( 'wei_admin_notice' );
		if ( $notice ) {
			delete_transient( 'wei_admin_notice' );
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}
	}
}
