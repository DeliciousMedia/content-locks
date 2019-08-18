<?php
/**
 *
 * Settings
 *
 * @package content-locks
 */

// Disallow direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds our options page to the admin menu.
 */
function cl_add_admin_menu() {
	add_options_page( 'Content Locks', 'Content Locks', 'manage_options', 'content_locks', 'cl_options_page' );
}
add_action( 'admin_menu', 'cl_add_admin_menu' );

/**
 * Registers our settings.
 */
function cl_settings_init() {
	register_setting( 'content_lock', 'cl_settings' );
	add_settings_section( 'cl_permissions', __( 'Who can lock content?', 'content-locks' ), 'cl_settings_section_content', 'content_lock' );
	add_settings_field( 'keymaster_role', __( 'User role:', 'content-locks' ), 'cl_keymaster_role_render', 'content_lock', 'cl_permissions' );
}
add_action( 'admin_init', 'cl_settings_init' );

/**
 * Renders the field to choose which role can add/remove content locks.
 */
function cl_keymaster_role_render() {
	$options = get_option( 'cl_settings' );
	?>
	<select name='cl_settings[keymaster_role]'>

		<?php
		$eligable_roles = cl_get_eligable_roles();
		foreach ( $eligable_roles as $role => $role_data ) :
		?>
			<option value='<?php echo esc_attr( $role ); ?>' <?php selected( $options['keymaster_role'], $role ); ?>><?php echo esc_html( $role_data['name'] ); ?></option>
		<?php endforeach ?>

	</select>

<?php

}

/**
 * Renders the settings introduction text.
 */
function cl_settings_section_content() {
	echo esc_html__( 'Select a user role who is able to lock/unlock individual content items.', 'content-locks' );
}

/**
 * Renders our options page.
 */
function cl_options_page() {

		?>
		<form action='options.php' method='post'>

			<h2>Content Locks Settings</h2>

			<?php
			settings_fields( 'content_lock' );
			do_settings_sections( 'content_lock' );
			submit_button();
			?>

		</form>
		<?php

}

/**
 * Sets our default settings, called on plugin activation.
 *
 * @return bool
 */
function cl_set_default_settings() {
	if ( ! empty( get_option( 'cl_settings' ) ) ) {
		return false;
	}
	$defaults = [
		'keymaster_role' => 'administrator',
	];

	update_option( 'cl_settings', apply_filters( 'cl_set_default_settings', $defaults ) );
	return true;
}

/**
 * Populates our taxonomy with the default content lock terms, flag is set during plugin activation.
 */
function cl_check_for_plugin_activation_flag() {
	if ( is_admin() && 'yes' === get_option( 'cl_plugin_activation_flag' ) ) {
		cl_populate_content_locks_taxonomy();
		delete_option( 'cl_plugin_activation_flag' );
	}
}
add_action( 'admin_init', 'cl_check_for_plugin_activation_flag' );

