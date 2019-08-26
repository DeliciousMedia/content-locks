<?php
/**
 *
 * User Interface
 *
 * @package content-locks
 */

// Disallow direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds the metabox to post edit screens.
 */
function cl_register_post_metaboxes() {

	if ( ! current_user_can( 'manage_content_locks' ) ) {
		return;
	}

	add_meta_box(
		'cl_post_locks',
		__( 'Content Locks', 'content-locks' ),
		'cl_post_locks_metabox_render',
		cl_get_eligable_post_types(),
		'side',
		'default',
		[
			'__block_editor_compatible_meta_box' => true,
		]
	);
}
add_action( 'add_meta_boxes', 'cl_register_post_metaboxes' );


/**
 * Renders our metabox.
 *
 * @param  object $post Current post object.
 *
 * @return void.
 */
function cl_post_locks_metabox_render( $post ) {

	wp_nonce_field( 'cl_post_locks_action', 'cl_post_locks_nonce' );
	$content_lock_options = get_terms( CL_TAXONOMY, [ 'hide_empty' => false ] );

?>
<select name='post_content_locks' id='post_content_locks'>

	<?php
		$terms = wp_get_object_terms( $post->ID, CL_TAXONOMY );
		?>
		<option class='content_lock_option' value=''
		<?php
		if ( ! count( $terms ) ) {
			echo 'selected'; }
?>
>None</option>
		<?php
		foreach ( $content_lock_options as $content_lock_option ) {
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) && ! strcmp( $content_lock_option->slug, $terms[0]->slug ) ) {
				echo "<option class='content_lock_option' value='" . esc_attr( $content_lock_option->slug ) . "' selected>" . esc_html( $content_lock_option->description ) . "</option>\n";
			} else {
				echo "<option class='content_lock_option' value='" . esc_attr( $content_lock_option->slug ) . "'>" . esc_html( $content_lock_option->description ) . "</option>\n";
			}
		}
	?>
</select>
<?php
}

/**
 * Saves our metabox information.
 *
 * @param  int $post_id Post ID being saved.
 */
function cl_post_locks_metabox_save( $post_id ) {
	// Ignore autosaves and revisions.
	if ( ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || empty( $_POST ) ) ) {
		return false;
	}

	if ( isset( $_POST['cl_post_locks_nonce'] ) && ! wp_verify_nonce( $_POST['cl_post_locks_nonce'], 'cl_post_locks_action' ) ) {

		wp_die( esc_html__( 'You are not allowed to do that.', 'content-locks' ) );
	}

	if ( array_key_exists( 'post_content_locks', $_POST ) && '' != array_key_exists( 'post_content_locks', $_POST ) ) {
		$term = sanitize_text_field( $_POST['post_content_locks'] );

		if ( ! term_exists( $term, CL_TAXONOMY ) ) {
			  return new WP_Error( 'Content Locks ', __( 'Invalid locking option.', 'content-locks' ) );
		}

		wp_set_object_terms( $post_id, $term, CL_TAXONOMY, false );

	}
}
add_action( 'save_post', 'cl_post_locks_metabox_save' );
add_action( 'edit_attachment', 'cl_post_locks_metabox_save' ); // Need to hook into attachments specifically.

/**
 * Adds a 'remove edit lock' to the post actions for posts which aren't editable.
 *
 * @param  array  $actions Post row actions.
 * @param  object $post    Post object.
 *
 * @return array
 */
function cl_maybe_add_unlock_post_action( $actions, $post ) {

	if ( ! current_user_can( 'manage_content_locks' ) ) {
		return $actions;
	}

	if ( cl_post_has_flag( $post->ID, 'cl_no_edit' ) ) {

		$actions = array_merge(
			$actions,
			[
				'update' => sprintf(
					'<a href="%s">Remove Edit Lock</a>',
					wp_nonce_url(
						sprintf( 'edit.php?post_type=%s&action=cl_remove_edit_lock&post_id=%d', $post->post_type, $post->ID ),
						'cl_remove_edit_lock',
						'cl_remove_edit_lock_nonce'
					)
				),
			]
		);
	}

	return $actions;
}
add_filter( 'post_row_actions', 'cl_maybe_add_unlock_post_action', 10, 2 );
add_filter( 'media_row_actions', 'cl_maybe_add_unlock_post_action', 10, 2 );

/**
 * Removes post edit lock when a user clicks the 'remove edit lock' link.
 */
function cl_remove_post_edit_lock() {

	if ( isset( $_GET['action'] ) && 'cl_remove_edit_lock' === $_GET['action'] ) {
		if ( ! isset( $_GET['cl_remove_edit_lock_nonce'] ) || ! wp_verify_nonce( $_GET['cl_remove_edit_lock_nonce'], 'cl_remove_edit_lock' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'content-locks' ) );
		}

		if ( ! current_user_can( 'manage_content_locks' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'content-locks' ) );
		}
		wp_remove_object_terms( intval( $_GET['post_id'] ), 'cl_no_edit', CL_TAXONOMY );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . $_GET['post_type'] . '&cl_confirm=removed' ) );
		exit;
	}
}
add_action( 'init', 'cl_remove_post_edit_lock', 999 );

/**
 * Adds an admin notice to confirm edit lock removal.
 */
function cl_remove_edit_lock_confirm() {
	if ( isset( $_GET['cl_confirm'] ) && 'removed' === $_GET['cl_confirm'] ) {
		add_action( 'admin_notices', 'cl_remove_edit_lock_confirm_notice' );
	}

}
add_action( 'init', 'cl_remove_edit_lock_confirm', 1 );

/**
 * Renders the admin notice confirming edit lock removal.
 */
function cl_remove_edit_lock_confirm_notice() {
	$class = 'notice notice-success is-dismissible';
	$message = __( 'Edit lock removed', 'content-locks' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
