<?php
/**
 * File: class-boldgrid-backup-admin-archive-browser.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/*
 * AJAX callback functions in this class have their nonce verified by authorize() in this class.
 *
 * phpcs:disable WordPress.VIP, WordPress.CSRF.NonceVerification.NoNonceVerification, WordPress.Security.NonceVerification.NoNonceVerification
 */

/**
 * Class: Boldgrid_Backup_Admin_Archive_Browser
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Archive_Browser {
	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Authorize an ajax request.
	 *
	 * Many of the ajax handlers in this method require the same
	 * current_user_can() and check_ajax_referer() checks.
	 *
	 * @since 1.6.0
	 */
	public function authorize() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if ( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce; security check failed.', 'boldgrid-backup' ) );
		}
	}

	/**
	 * Render and return html markup for a .sql file.
	 *
	 * When a user clicks to "View details" of a database dump, this
	 * method will create a table showing all the tables in that backup.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filepath Zip file.
	 * @param  string $file     Sql file name.
	 * @return string
	 */
	public function get_sql_details( $filepath, $file ) {
		$tables_with_records = $this->core->db_dump->get_insert_count( $filepath, $file );
		$prefixed_tables     = $this->core->db_get->prefixed_count();

		$in_backup  = __( '# Records in this backup', 'boldgrid-backup' );
		$in_current = __( '# Records in current database', 'boldgrid-backup' );

		$return = sprintf(
			'
			<table class="wp-list-table fixed striped widefat">
			<thead>
				<tr>
					<th>Table</th>
					<th>%1$s</th>
					<th class="bulk-action-notice">
						%2$s
						<span class="toggle-indicator"></span>
					</th>
				</tr>
			</thead>
			<tbody>',
			/* 1 */ $in_backup,
			/* 2 */ $in_current
		);

		foreach ( $prefixed_tables as $table => $record_count ) {
			$return .= sprintf(
				'<tr>
					<td>%1$s</td>
					<td>%2$s</td>
					<td>%3$s</td>
				</tr>',
				esc_html( $table ),
				isset( $tables_with_records[ $table ] ) ? $tables_with_records[ $table ] : '0',
				esc_html( $record_count )
			);
		}

		$return .= '</tbody></table>';

		return $return;
	}

	/**
	 * Allow the user to browse an archive file.
	 *
	 * Returns a formatted table to the browser.
	 *
	 * @since 1.5.3
	 */
	public function wp_ajax_browse_archive() {
		$error = __( 'Unable to get contents of archive file:', 'boldgrid-backup' );

		$this->authorize();

		$filename = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : false;
		$filepath = $this->core->backup_dir->get_path_to( $filename );
		if ( empty( $filename ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( $error . ' ' . __( 'Invalid archive filename.', 'boldgrid-backup' ) );
		}

		$this->core->archive->init( $filepath );

		$dump_file = $this->core->get_dump_file( $filepath );

		/*
		 * An array of files not to show in the archive browser.
		 * If this is our database dump file, skip over it. We have another section of the archive
		 * details page that will help with restoring a dump file.
		 */
		$no_show = array(
			basename( $dump_file ),
			basename( $this->core->archive->log_filepath ),
		);

		$dir = ! empty( $_POST['dir'] ) ? trim( strip_tags( $_POST['dir'] ) ) : null;

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$contents = $zip->browse( $filepath, $dir );

		$tr              = '';
		$empty_directory = '<tr><td colspan="3">' . __( 'Empty directory', 'boldgrid-backup' ) . '</td></tr>';

		$table = sprintf(
			'<table class="wp-list-table fixed striped remote-storage widefat">
				<thead>
					<tr>
						<th>%1$s</th>
						<th>%2$s</th>
						<th class="bulk-action-notice">
							%3$s
							<span class="toggle-indicator"></span>
						</th>
					</tr>
				</thead>
				<tbody>
			',
			__( 'Name', 'boldgrid-backup' ),
			__( 'Size', 'boldgrid-backup' ),
			__( 'Last Modified', 'boldgrid-backup' )
		);

		foreach ( $contents as $file ) {
			if ( in_array( basename( $file['filename'] ), $no_show, true ) ) {
				continue;
			}

			$tr .= include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser-entry.php';
		}

		$table .= empty( $tr ) ? $empty_directory : $tr;

		$table .= '</tbody></table>';

		wp_send_json_success( $table );
	}

	/**
	 * Show available actions for a single file.
	 *
	 * When the user clicks on a single file in a backup archive, show them
	 * what options they have available.
	 *
	 * @since 1.5.3
	 */
	public function wp_ajax_file_actions() {
		$this->authorize();

		$filename = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : false;
		$filepath = $this->core->backup_dir->get_path_to( $filename );
		$file     = ! empty( $_POST['file'] ) ? trim( strip_tags( $_POST['file'] ) ) : false;
		if ( empty( $filepath ) || empty( $file ) ) {
			wp_send_json_error( __( 'Invalid file / filepath.', 'boldgrid-backup' ) );
		}

		// Here's the default message.
		$upgrade_message = __( 'With BoldGrid Backup Premium, you can view and restore files from here.', 'boldgrid-backup' );

		/**
		 * Allow other plugins to add functionality.
		 *
		 * @since 1.5.3
		 *
		 * @param string $upgrade_message
		 * @param string $file            Example: wp-admin/import.php
		 */
		$upgrade_message = apply_filters( 'boldgrid_backup_file_actions', $upgrade_message, $file );

		wp_send_json_success( $upgrade_message );
	}

	/**
	 * Restore a database dump.
	 *
	 * This handles an ajax call for restoring a dump from the archive details
	 * page.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_restore_db() {
		$this->authorize();

		$filename = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : false;
		$filepath = $this->core->backup_dir->get_path_to( $filename );
		$file     = ! empty( $_POST['file'] ) ? trim( strip_tags( $_POST['file'] ) ) : false;
		if ( empty( $filepath ) || empty( $file ) ) {
			wp_send_json_error( __( 'Invalid file / filepath.', 'boldgrid-backup' ) );
		}

		$importer = new Boldgrid_Backup_Admin_Db_Import( $this->core );
		$success  = $importer->import_from_archive( $filepath, $file );

		if ( ! $success ) {
			$this->core->notice->add_user_notice(
				// translators: 1: Filename 2: File path.
				sprintf( __( 'Error, unable to import database %1$s from %2$s.', 'boldgrid-backup' ), $file, $filepath ),
				$this->core->notice->lang['dis_error']
			);
		} else {
			$this->core->notice->add_user_notice(
				// translators: 1: Filename 2: File path.
				sprintf( __( 'Success! Database %1$s imported from %2$s.', 'boldgrid-backup' ), $file, $filepath ),
				$this->core->notice->lang['dis_success']
			);
		}
	}

	/**
	 * View the details of a database.
	 *
	 * This method handles the ajax call of "View details" for a database on the
	 * archive details page.
	 *
	 * @since 1.6.0
	 */
	public function wp_ajax_view_db() {
		$this->authorize();

		$filename = ! empty( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : false;
		$filepath = $this->core->backup_dir->get_path_to( $filename );
		$file     = ! empty( $_POST['file'] ) ? trim( strip_tags( $_POST['file'] ) ) : false;
		if ( empty( $filename ) || empty( $filepath ) || empty( $file ) ) {
			wp_send_json_error( __( 'Invalid file / filepath.', 'boldgrid-backup' ) );
		}

		$table = $this->get_sql_details( $filepath, $file );

		if ( empty( $table ) ) {
			$error = $this->core->notice->get_notice_markup( 'notice notice-error is-dismissible', __( 'Error, unable to get details from this database backup.', 'boldgrid-backup' ) );
			wp_send_json_error( $error );
		} else {
			wp_send_json_success( $table );
		}
	}
}
