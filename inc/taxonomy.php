<?php
/**
 *
 * Taxonomy
 *
 * @package content-locks
 */

// Disallow direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register custom taxonomy to hold locking details for posts.
 */
function cl_register_content_locks_taxonomy() {

	$labels = [
		'name'                       => _x( 'Content Locks', 'Taxonomy General Name', 'content-locks' ),
		'singular_name'              => _x( 'Content Lock', 'Taxonomy Singular Name', 'content-locks' ),
		'menu_name'                  => __( 'Content Locks', 'content-locks' ),
		'all_items'                  => __( 'All Locks', 'content-locks' ),
		'parent_item'                => __( 'Parent Lock', 'content-locks' ),
		'parent_item_colon'          => __( 'Parent Lock:', 'content-locks' ),
		'new_item_name'              => __( 'New Lock Name', 'content-locks' ),
		'add_new_item'               => __( 'Add New Lock', 'content-locks' ),
		'edit_item'                  => __( 'Edit Lock', 'content-locks' ),
		'update_item'                => __( 'Update Lock', 'content-locks' ),
		'view_item'                  => __( 'View Lock', 'content-locks' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'content-locks' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'content-locks' ),
		'choose_ from _most_used'    => __( 'Choose from the most used', 'content-locks' ),
		'popular_items'              => __( 'Popular Locks', 'content-locks' ),
		'search_items'               => __( 'Search Locks', 'content-locks' ),
		'not_found'                  => __( 'Not Found', 'content-locks' ),
		'no_terms'                   => __( 'No items', 'content-locks' ),
		'items_list'                 => __( 'Locks list', 'content-locks' ),
		'items_list_navigation'      => __( 'Locks list navigation', 'content-locks' ),
	];

	$args = [
		'labels'            => $labels,
		'hierarchical'      => false,
		'public'            => false,
		'show_ui'           => false,
		'show_admin_column' => false,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'rewrite'           => false,
		'show_in_rest'      => false,
	];
	register_taxonomy( CL_TAXONOMY, [ '' ], $args );

}
add_action( 'init', 'cl_register_content_locks_taxonomy' );


/**
 * Populate our taxonomy with our terms.
 */
function cl_populate_content_locks_taxonomy() {

	$terms = [
		'cl_no_delete' => 'Prevent Deletion',
		'cl_no_edit'   => 'Prevent Editing & Deletion',
	];

	foreach ( $terms as $term => $description ) {
		if ( ! term_exists( $term, CL_TAXONOMY ) ) {
			$inserted_term = wp_insert_term(
				$term,
				CL_TAXONOMY,
				[
					'slug'        => $term,
					'description' => $description,
				]
			);
		}
	}
}
