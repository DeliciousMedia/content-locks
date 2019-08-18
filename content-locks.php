<?php
/**
 * Plugin Name:       Content Locks
 * Plugin URI:        http://github.com/deliciousmedia/content-locks/
 * Description:       Allows specified users to prevent certain pages, posts, attachments or custom post types from being edited or deleted.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Delicious Media Limited
 * Author URI:        https://www.deliciousmedia.co.uk/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       content-locks
 * Domain Path:       /languages
 *
 * @package content-locks
 */

define( 'CL_TAXONOMY', 'contentlocks' );

// Disallow direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include functionality.
require_once( dirname( __FILE__ ) . '/inc/functionality.php' );
require_once( dirname( __FILE__ ) . '/inc/settings.php' );
require_once( dirname( __FILE__ ) . '/inc/taxonomy.php' );
require_once( dirname( __FILE__ ) . '/inc/ui.php' );

// Activation hooks.
register_activation_hook( __FILE__, 'cl_set_default_settings' );

// Can't populate our taxonomy on plugin activation so set a flag which we'll pick up in cl_check_for_plugin_activation_flag().
register_activation_hook(
	__FILE__,
	function() {
		add_option( 'cl_plugin_activation_flag', 'yes' );
	}
);
