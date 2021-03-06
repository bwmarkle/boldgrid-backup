<?php
/**
 * File: class-boldgrid-backup-admin-archive.php
 *
 * @link       https://www.boldgrid.com
 * @since      1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Archive
 *
 * @since 1.5.3
 */
class Boldgrid_Backup_Admin_Archive {
	/**
	 * The core class object.
	 *
	 * @since  1.5.3
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Compressor used when creating archive.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    string
	 */
	public $compressor = null;

	/**
	 * Filename of this archive.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    string
	 */
	public $filename = null;

	/**
	 * Full filepath to the archive.
	 *
	 * Set in the init method.
	 *
	 * @since  1.5.3
	 * @access public
	 * @var    string
	 */
	public $filepath = null;

	/**
	 * The contents of the archive's log file.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    array
	 */
	public $log = array();

	/**
	 * The filename of this archive's log file.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    string
	 */
	public $log_filename = null;

	/**
	 * The filepath to this archive's log file.
	 *
	 * @since  1.6.0
	 * @access public
	 * @var    string
	 */
	public $log_filepath = null;

	/**
	 * URL to the details page of this backup.
	 *
	 * This property is available after calling init().
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    string
	 */
	public $view_details_url = '';

	/**
	 * Constructor.
	 *
	 * @since 1.5.3
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Delete an archive file.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath Absolute path to a backup file.
	 * @return bool
	 */
	public function delete( $filepath ) {
		$deleted = $this->core->wp_filesystem->delete( $filepath, false, 'f' );

		$this->core->archive_log->delete_by_zip( $filepath );

		return $deleted;
	}

	/**
	 * Get an attribute from the log.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $key Attributes are key / value pairs.
	 * @return mixed
	 */
	public function get_attribute( $key ) {
		return ! empty( $this->log[ $key ] ) ? $this->log[ $key ] : null;
	}

	/**
	 * Get an archive by name.
	 *
	 * Please see @return for more information on what an archive actually is.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @return array {
	 *     Details about an archive.
	 *
	 *     @type string $filepath    /home/user/boldgrid_backup/file.zip
	 *     @type string $filename    file.zip
	 *     @type string $filedate    1/2/2018 1:21 PM
	 *     @type int    $filesize    99152247
	 *     @type int    $lastmodunix 1514917311
	 *     @type int    $key         0
	 * }
	 */
	public function get_by_name( $filename ) {
		$return_archive = false;

		$archives = $this->core->get_archive_list();

		foreach ( $archives as $key => $archive ) {
			if ( $archive['filename'] === $filename ) {
				$archive['key'] = $key;
				$return_archive = $archive;
				break;
			}
		}

		return $return_archive;
	}

	/**
	 * Get one file from an archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $file      The file to get.
	 * @param  bool   $meta_only Whether to include the content of the file.
	 * @return array
	 */
	public function get_file( $file, $meta_only = false ) {
		if ( empty( $this->filepath ) || ! $this->is_archive( $this->filepath ) ) {
			return false;
		}

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );

		$file_contents = $zip->get_file( $this->filepath, $file );

		// If we only want the meta data, unset the content of the file.
		if ( $meta_only && ! empty( $file_contents[0]['content'] ) ) {
			unset( $file_contents[0]['content'] );
		}

		return $file_contents;
	}

	/**
	 * Init.
	 *
	 * @since 1.6.0
	 *
	 * @param string $filepath File path.
	 */
	public function init( $filepath ) {
		$filepath = strip_tags( $filepath );

		if ( ! empty( $this->filepath ) && $filepath === $this->filepath ) {
			return;
		}

		$this->reset();

		$this->filepath = $filepath;
		$this->filename = basename( $this->filepath );

		$this->log_filepath = $this->core->archive_log->path_from_zip( $this->filepath );
		$this->log_filename = basename( $this->log_filepath );

		// If the archive's log file does not exist, extract it.
		$have_log = $this->core->wp_filesystem->exists( $this->log_filepath );
		if ( ! $have_log ) {
			$have_log = $this->core->archive_log->restore_by_zip( $this->filepath );
		}

		if ( $have_log ) {
			$this->log = $this->core->archive_log->get_by_zip( $this->filepath );
		}

		/*
		 * Init our compressor.
		 *
		 * If there is no log file, this archive was created with version < 1.6
		 * and the only compressor was ZipArchive.
		 */
		$this->compressor = ! empty( $this->log['compressor'] ) ? $this->log['compressor'] : 'php_zip';

		$this->view_details_url = admin_url( 'admin.php?page=boldgrid-backup-archive-details&filename=' . $this->filename );
	}

	/**
	 * Determine if a zip file is in our archive.
	 *
	 * @since 1.5.3
	 *
	 * @param  string $filepath File path.
	 * @return bool
	 */
	public function is_archive( $filepath ) {
		$archives = $this->core->get_archive_list();

		if ( empty( $archives ) ) {
			return false;
		}

		foreach ( $archives as $archive ) {
			if ( $filepath === $archive['filepath'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a backup belongs to this site.
	 *
	 * This method takes into account a site's $backup_identifier and compares
	 * it to a backup's filename.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $filename Filename.
	 * @return bool
	 */
	public function is_site_archive( $filename ) {
		$backup_identifier = $this->core->get_backup_identifier();

		// End in zip.
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( 'zip' !== $extension ) {
			return false;
		}

		// Include the backup identifier.
		if ( false === strpos( $filename, $backup_identifier ) ) {
			return false;
		}

		// Begin with 'boldgrid-backup-'.
		if ( 0 !== strpos( $filename, 'boldgrid-backup-' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether or not this archive is stored on the web server (local).
	 *
	 * This is similar to self::is_stored_remotely. While that method is an
	 * expensive operation, this one is not. However, for consistency, this too
	 * will be a method rather than a class property.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_stored_locally() {
		$this->core->archives_all->init();

		return isset( $this->core->archives_all->archives[ $this->filename ]['on_web_server'] ) &&
			true === $this->core->archives_all->archives[ $this->filename ]['on_web_server'];
	}

	/**
	 * Determine whether or not this archive is stored remotely somewhere.
	 *
	 * This is an expensive operation, so we are not using this as a class
	 * property / initializing during init.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_stored_remotely() {
		$this->core->archives_all->init();

		return isset( $this->core->archives_all->archives[ $this->filename ]['on_remote_server'] ) &&
			true === $this->core->archives_all->archives[ $this->filename ]['on_remote_server'];
	}

	/**
	 * Reset this class.
	 *
	 * @since 1.6.0
	 */
	public function reset() {
		$this->filename     = null;
		$this->filepath     = null;
		$this->log_filepath = null;
		$this->log_filename = null;
		$this->log          = array();
		$this->compressor   = null;
	}

	/**
	 * Set an attribute in the log.
	 *
	 * @since 1.7.0
	 *
	 * @param  string $key   The key.
	 * @param  string $value The value.
	 * @return bool
	 */
	public function set_attribute( $key, $value ) {
		$this->log[ $key ] = $value;

		return $this->core->archive_log->write( $this->log );
	}

	/**
	 * Update an archive's timestamp based on the time in the log.
	 *
	 * For example, if the archive was created at 10am and you uploaded it to
	 * an FTP server at 12pm, the FTP server may set the timestamp to 12pm. Then
	 * you download from FTP to web server at 2pm, and the archive's timestamp
	 * is now 2pm. This is all confusing. This method will get the archive's
	 * timestamp from the log and configure the last modified appropriately.
	 */
	public function update_timestamp() {
		// If we don't have what we need, abort.
		if ( empty( $this->filepath ) || empty( $this->log['lastmodunix'] ) ) {
			return false;
		}

		return $this->core->wp_filesystem->touch( $this->filepath, $this->log['lastmodunix'] );
	}

	/**
	 * Validate a download link request.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Archive::get_by_name()
	 *
	 * @param  string $filename
	 * @return array
	 */
	public function validate_link_request( $filename ) {
		$result['is_valid'] = true;

		// Verify access permissions.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$result['errors'][] = __( 'Insufficient permission', 'boldgrid-backup' );
		}

		// Validate archive filename.
		if ( empty( $filename ) ) {
			$result['errors'][] = __( 'Invalid archive filename', 'boldgrid-backup' );
		}

		// Check WP_Filesystem method; ensure it is "direct".
		if ( 'direct' !== get_filesystem_method() ) {
			$result['errors'][] = __(
				'Filesystem access method is not "direct"',
				'boldgrid-backup'
			);
		}

		// Get archive details.
		$archive = $this->get_by_name( $filename );

		// Check if archive file was found.
		if ( empty( $archive ) ) {
			$result['errors'][] = __( 'Archive file not found', 'boldgrid-backup' );
		}

		$expires = strtotime( '+' . $this->core->configs['public_link_lifetime'] );

		if ( ! $expires || $expires < time() ) {
			$result['errors'][] = __(
				'Invalid "public_link_lifetime" configuration setting',
				'boldgrid-backup'
			);
		}

		if ( ! empty( $result['errors'] ) ) {
			$result['is_valid'] = false;
		}

		return $result;
	}

	/**
	 * Generate a public link to download an archive file.
	 *
	 * The link is only valid for a limited time, which is configurable in a configuration file.
	 *
	 * @since 1.7.0
	 *
	 * @see Boldgrid_Backup_Admin_Archive::validate_link_request()
	 * @see Boldgrid_Backup_Authentication::create_token()
	 *
	 * @param  string $filename
	 * @return string
	 */
	public function generate_download_link( $filename ) {
		$validation_results = $this->validate_link_request( $filename );

		if ( $validation_results['is_valid'] ) {
			$expires = strtotime( '+' . $this->core->configs['public_link_lifetime'] );
			$token   = Boldgrid_Backup_Authentication::create_token( $filename, $expires );

			$response['download_url'] = get_site_url(
				null,
				'wp-admin/admin-ajax.php?action=boldgrid_backup_download&t=' . $token
			);

			$response['expires_when'] = human_time_diff(
				$expires,
				current_time( 'timestamp', true )
			);
		} else {
			$response['error'] = implode( '<br />', $validation_results['errors'] );
		}

		return $response;
	}
}
