<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

if ( ! defined( 'BOLDGRID_BACKUP_PATH' ) ) {
	define( 'BOLDGRID_BACKUP_PATH', dirname( dirname( __FILE__ ) ) );
}

// Require necessary files.
$files = array(
	'/admin/class-boldgrid-backup-admin.php',
	'/admin/class-boldgrid-backup-admin-core.php',
	'/admin/class-boldgrid-backup-admin-settings.php',
	'/admin/class-boldgrid-backup-admin-config.php',
	'/admin/class-boldgrid-backup-admin-test.php',
	'/admin/class-boldgrid-backup-admin-notice.php',
	'/admin/class-boldgrid-backup-admin-cron.php',
	'/admin/class-boldgrid-backup-admin-upload.php',
	'/admin/class-boldgrid-backup-admin-xhprof.php',
	'/admin/class-boldgrid-backup-admin-restore-helper.php',
	'/admin/class-boldgrid-backup-admin-restore-git.php',
	'/admin/class-boldgrid-backup-admin-filelist.php',
	'/admin/class-boldgrid-backup-admin-backup-dir.php',
	'/admin/class-boldgrid-backup-admin-home-dir.php',
	'/admin/class-boldgrid-backup-admin-compressors.php',
	'/admin/class-boldgrid-backup-admin-archive-browser.php',
	'/admin/class-boldgrid-backup-admin-archive.php',
	'/admin/class-boldgrid-backup-admin-archive-actions.php',
	'/admin/class-boldgrid-backup-admin-archives.php',
	'/admin/class-boldgrid-backup-admin-archives-all.php',
	'/admin/class-boldgrid-backup-admin-archive-log.php',
	'/admin/class-boldgrid-backup-admin-archive-details.php',
	'/admin/class-boldgrid-backup-admin-archive-fail.php',
	'/admin/class-boldgrid-backup-admin-wp-cron.php',
	'/admin/class-boldgrid-backup-admin-scheduler.php',
	'/admin/class-boldgrid-backup-admin-auto-rollback.php',
	'/admin/class-boldgrid-backup-admin-remote.php',
	'/admin/class-boldgrid-backup-admin-jobs.php',
	'/admin/class-boldgrid-backup-admin-email.php',
	'/admin/class-boldgrid-backup-admin-db-omit.php',
	'/admin/class-boldgrid-backup-admin-db-dump.php',
	'/admin/class-boldgrid-backup-admin-db-get.php',
	'/admin/class-boldgrid-backup-admin-utility.php',
	'/admin/class-boldgrid-backup-admin-folder-exclusion.php',
	'/admin/class-boldgrid-backup-admin-core-files.php',
	'/admin/class-boldgrid-backup-admin-in-progress.php',
	'/admin/class-boldgrid-backup-admin-go-pro.php',
	'/admin/class-boldgrid-backup-admin-tools.php',
	'/admin/class-boldgrid-backup-admin-time.php',
	'/admin/storage/local.php',
	'/admin/remote/ftp.php',
	'/admin/remote/sftp.php',
	'/admin/remote/ftp-hooks.php',
	'/admin/remote/ftp-page.php',
	'/vendor/phpseclib/phpseclib/phpseclib/Net/SSH2.php',
);
foreach( $files as $file ) {
	require_once BOLDGRID_BACKUP_PATH . $file;
}

/**
 * Debug to console.
 *
 * @since 1.6.0
 *
 * @param mixed
 */
function phpunit_error_log( $var ) {
	fwrite( STDERR, "\n" . print_r( $var, 1 ) . "\n" );
}

require $_tests_dir . '/includes/bootstrap.php';