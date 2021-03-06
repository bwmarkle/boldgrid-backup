<?php
/**
 * File: boldgrid-backup.php
 *
 * The plugin bootstrap file.
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link https://www.boldgrid.com
 * @since 1.0.0
 * @package Boldgrid_Backup
 *
 *          @wordpress-plugin
 *          Plugin Name: BoldGrid Backup
 *          Plugin URI: https://www.boldgrid.com/boldgrid-backup/
 *          Description: BoldGrid Backup provides WordPress backup and restoration with update protection.
 *          Version: 1.7.0-alpha.1
 *          Author: BoldGrid
 *          Author URI: https://www.boldgrid.com/
 *          License: GPL-2.0+
 *          License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *          Text Domain: boldgrid-backup
 *          Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// Define version.
if ( ! defined( 'BOLDGRID_BACKUP_VERSION' ) ) {
	define( 'BOLDGRID_BACKUP_VERSION', implode( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
}

// Define boldgrid-backup path.
if ( ! defined( 'BOLDGRID_BACKUP_PATH' ) ) {
	define( 'BOLDGRID_BACKUP_PATH', dirname( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-boldgrid-backup-activator.php
 */
function activate_boldgrid_backup() {
	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-activator.php';
	Boldgrid_Backup_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-boldgrid-backup-deactivator.php
 */
function deactivate_boldgrid_backup() {
	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup-deactivator.php';
	Boldgrid_Backup_Deactivator::deactivate();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0
 */
function run_boldgrid_backup() {
	$plugin = new Boldgrid_Backup();
	$plugin->run();
}

/**
 * Load BoldGrid Backup.
 *
 * Before loading, ensure system meets minimium requirements:
 * # vendor folder exists. This is not a system requirement, but we want to make
 *   sure the user is NOT running a dev version with a missing vendor folder.
 *
 * @since 1.6.0
 *
 * @return bool
 */
function load_boldgrid_backup() {
	// Ensure we have our vendor/autoload.php file.
	$exists_composer = file_exists( BOLDGRID_BACKUP_PATH . '/composer.json' );
	$exists_autoload = file_exists( BOLDGRID_BACKUP_PATH . '/vendor/autoload.php' );
	if ( $exists_composer && ! $exists_autoload ) {
		add_action(
			'admin_init', function() {
				deactivate_plugins( 'boldgrid-backup/boldgrid-backup.php', true );

				add_action(
					'admin_notices', function() {
						?>
				<div class="notice notice-error is-dismissible"><p>
						<?php
						printf(
							// translators: 1: HTML strong open tag, 2: HTML strong close tag.
							esc_html__(
								'%1$sBoldGrid Backup%2$s has been deactivated because the vendor folder is missing. Please run %1$s%3$scomposer install%4$s%2$s, or contact your host for further assistance.',
								'boldgrid-backup'
							),
							'<strong>',
							'</strong>',
							'<em>',
							'</em>'
						);
						?>
					</p></div>
						<?php
					}
				);
			}
		);

		return false;
	}

	// Ensure we have our build directory with a required file in it.
	if ( ! file_exists( BOLDGRID_BACKUP_PATH . '/build/clipboard.min.js' ) ) {
		add_action(
			'admin_init', function() {
				deactivate_plugins( 'boldgrid-backup/boldgrid-backup.php', true );

				add_action(
					'admin_notices', function() {
						?>
				<div class="notice notice-error is-dismissible"><p>
						<?php
						printf(
							// translators: 1: HTML strong open tag, 2: HTML strong close tag.
							esc_html__(
								'%1$sBoldGrid Backup%2$s has been deactivated because the build folder is missing. Please run %1$s%3$syarn install%4$s%2$s and %1$s%3$sgulp%4$s%2$s, or contact your host for further assistance.',
								'boldgrid-backup'
							),
							'<strong>',
							'</strong>',
							'<em>',
							'</em>'
						);
						?>
					</p></div>
						<?php
					}
				);
			}
		);

		return false;
	}

	register_activation_hook( __FILE__, 'activate_boldgrid_backup' );
	register_deactivation_hook( __FILE__, 'deactivate_boldgrid_backup' );

	// Include the autoloader to set plugin options and create instance.
	$loader = require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Load Library.
	$load = new Boldgrid\Library\Util\Load(
		array(
			'type'            => 'plugin',
			'file'            => plugin_basename( __FILE__ ),
			'loader'          => $loader,
			'keyValidate'     => true,
			'licenseActivate' => false,
		)
	);

	return true;
}

/*
 * Load the plugin.
 *
 * Above is only:
 * # function declarations
 * # constant declarations
 *
 * The initial loading of this plugin is done below.
 *
 * Run the plugin only if on a wp-admin page or when DOING_CRON.
 */
if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || defined( 'WP_CLI' ) && WP_CLI ) {

	// If we could not load boldgrid_backup (missing system requirements), abort.
	if ( ! load_boldgrid_backup() ) {
		return;
	}

	require_once BOLDGRID_BACKUP_PATH . '/includes/class-boldgrid-backup.php';
	run_boldgrid_backup();
}
