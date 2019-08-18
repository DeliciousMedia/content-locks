<?php
/**
 *
 * Helpers
 *
 * @package content-locks
 */

// Disallow direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds post type support to post types we want to be able to lock content in.
 *
 * @return void
 */
function cl_add_postype_support() {
	$post_types = cl_get_eligable_post_types();
	foreach ( $post_types as $post_type ) {
		add_post_type_support( $post_type, 'contentlocks' );
		register_taxonomy_for_object_type( CL_TAXONOMY, $post_type );
	}
}
add_action( 'wp_loaded', 'cl_add_postype_support' );

/**
 * Returns a list of post types we want to be able to lock content in, by default anything public.
 *
 * @return array
 */
function cl_get_eligable_post_types() {
	$args = apply_filters( 'cl_get_eligable_post_types_args', [ 'public' => true ] );
	return (array) ( apply_filters( 'cl_get_eligable_post_types', get_post_types( $args ) ) );
}

/**
 * The wp_block post type isn't public, but we want to include it by default.
 * So, filter the post types to include it.
 *
 * @param  array $post_types Post types to apply content locks to.
 *
 * @return array
 */
function cl_include_wp_block_in_post_types( $post_types ) {
	$post_types = array_merge( $post_types, [ 'wp_block' ] );
	return $post_types;
}
add_filter( 'cl_get_eligable_post_types', 'cl_include_wp_block_in_post_types', 10, 1 );

/**
 * Returns an array of roles which can apply/remove content locks.
 * On most installs this will just offer administrator and editor.
 *
 * @return array
 */
function cl_get_eligable_roles() {
	global $wp_roles;
	$roles = $wp_roles->roles;
	$never_allowed = apply_filters( 'cl_get_eligable_roles_never_allowed', [ 'subscriber', 'contributor', 'author', 'customer' ] );
	foreach ( $never_allowed as $role ) {
		if ( isset( $roles[ $role ] ) ) {
			unset( $roles[ $role ] );
		}
	}
	return apply_filters( 'cl_get_eligable_roles', $roles );
}

/**
 * Assigns the manage_content_locks role to the role chosen in our options page when the option changes.
 *
 * @param  mixed  $old_value Previous option value.
 * @param  mixed  $new_value New option value.
 * @param  string $option   Option name.
 *
 * @return void
 */
function cl_assign_capability( $old_value, $new_value, $option ) {

	global $wp_roles;
	$capability = 'manage_content_locks';

	if ( $wp_roles->is_role( $new_value['keymaster_role'] ) ) {
			$wp_roles->add_cap( $new_value['keymaster_role'], $capability );
	}

	if ( $wp_roles->is_role( $old_value['keymaster_role'] ) ) {
			$wp_roles->remove_cap( $old_value['keymaster_role'], $capability );
	}
}
add_action( 'update_option_cl_settings', 'cl_assign_capability', 10, 3 );

/**
 * Enforces any content locks which are in place.
 *
 * @param  string $required_cap Required capability.
 * @param  string $cap          Capability requested.
 * @param  int    $user_id      User ID of user being checked.
 * @param  array  $args         Additional data.
 * @return string               Required capability.
 */
function cl_maybe_enforce_content_locks( $required_cap, $cap, $user_id, $args ) {

	if ( 'delete_post' == $cap ) {
		if ( cl_post_has_flag( $args[0], 'cl_no_delete' ) || cl_post_has_flag( $args[0], 'cl_no_edit' ) ) {
			$required_cap[] = 'do_not_allow';
		}
	}

	if ( 'edit_post' == $cap ) {
		if ( cl_post_has_flag( $args[0], 'cl_no_edit' ) ) {
			$required_cap[] = 'do_not_allow';
		}
	}

	return $required_cap;
}
add_filter( 'map_meta_cap', 'cl_maybe_enforce_content_locks', 10, 4 );

/**
 * Checks if a post has a content lock set.
 *
 * @param  int    $post_id Post ID to check.
 * @param  string $flag    Flag to check for.
 *
 * @return bool
 */
function cl_post_has_flag( $post_id, $flag ) {
	return ( in_array( $flag, wp_get_post_terms( $post_id, CL_TAXONOMY, [ 'fields' => 'slugs' ] ) ) );
}
