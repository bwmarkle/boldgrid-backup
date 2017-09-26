<?php

/**
 * Display a single provider on the settings page.
 *
 * This page returns the html markup needed for the <tr> #storage_locations
 * table.
 *
 * @since 1.5.2
 *
 * @param  array $location {
 *     A remote storage location / provider.
 *
 *     @type string $title     Amazon S3
 *     @type string $key       amazon_s3
 *     @type string $configure A url to configure the provider, such as
 *                             admin.php?page=boldgrid-backup-amazon-s3
 *     @type bool   $enabled   Whether or not this provider is enabled.
 * }
 * @return string
 */

/**
 * Determine if a remote storage provider is properly setup.
 *
 * @since 1.5.2
 */
$is_setup = apply_filters( 'boldgrid_backup_is_setup_' . $location['key'], false );

$configure_link = '<a href="%1$s&TB_iframe=true&width=600&height=550" class="thickbox">%2$s</a>';

if( $is_setup ) {
	$configure = sprintf( '&#10003; %1$s', __( 'Configured', 'boldgrid-backup' ) );
	$configure .= ' (' . sprintf( $configure_link, $location['configure'], __( 'update', 'boldgrid-backup' ) ) . ')';
} elseif ( ! empty( $location['configure' ] ) ) {
	$configure .= sprintf( $configure_link, $location['configure'], __( 'Configure', 'boldgrid-backup' ) );
} else {
	$configure = '';
}

$disabled = $is_setup ? '' : 'disabled';

$checked = isset( $location['enabled'] ) && true === $location['enabled'] ? 'checked' : '';

return sprintf( '
	<tr data-key="%4$s">
		<td>
			<input type="checkbox" name="storage_location[%4$s]" value="1" %3$s %5$s> <strong>%1$s</strong>
		</td>
		<td class="storage">
			%2$s
		</td>
	</tr>
	',
	$location['title'],
	$configure,
	$disabled,
	$location['key'],
	$checked
);

?>