<?php
/**
 * Content Following Action Handlers.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle author follow/unfollow actions.
 *
 * @since 2.1.0
 */
function bp_follow_handle_author_actions() {
	if ( ! isset( $_GET['action'] ) ) {
		return;
	}

	$action = sanitize_key( $_GET['action'] );

	if ( ! in_array( $action, array( 'follow', 'unfollow' ), true ) ) {
		return;
	}

	if ( ! isset( $_GET['author_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}

	$author_id = absint( $_GET['author_id'] );

	// Verify nonce.
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bp_follow_author_' . $action ) ) {
		bp_core_add_message( __( 'Security check failed.', 'buddypress-followers' ), 'error' );
		return;
	}

	// Get post types.
	$post_types = isset( $_GET['post_types'] ) ? sanitize_text_field( wp_unslash( $_GET['post_types'] ) ) : 'post';
	$post_types = array_map( 'trim', explode( ',', $post_types ) );

	$service = bp_follow_get_author_service();

	if ( 'follow' === $action ) {
		if ( $service->follow_author( $author_id, bp_loggedin_user_id(), $post_types ) ) {
			bp_core_add_message( __( 'You are now following this author.', 'buddypress-followers' ) );
		} else {
			bp_core_add_message( __( 'Failed to follow author.', 'buddypress-followers' ), 'error' );
		}
	} else {
		if ( $service->unfollow_author( $author_id, bp_loggedin_user_id(), $post_types ) ) {
			bp_core_add_message( __( 'You are no longer following this author.', 'buddypress-followers' ) );
		} else {
			bp_core_add_message( __( 'Failed to unfollow author.', 'buddypress-followers' ), 'error' );
		}
	}

	// Redirect back.
	wp_safe_redirect( wp_get_referer() );
	exit;
}
add_action( 'bp_actions', 'bp_follow_handle_author_actions', 5 );

/**
 * Handle term follow/unfollow actions.
 *
 * @since 2.1.0
 */
function bp_follow_handle_term_actions() {
	if ( ! isset( $_GET['action'] ) ) {
		return;
	}

	$action = sanitize_key( $_GET['action'] );

	if ( ! in_array( $action, array( 'follow', 'unfollow' ), true ) ) {
		return;
	}

	if ( ! isset( $_GET['term_id'] ) || ! isset( $_GET['taxonomy'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}

	$term_id  = absint( $_GET['term_id'] );
	$taxonomy = sanitize_key( $_GET['taxonomy'] );

	// Verify nonce.
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bp_follow_term_' . $action ) ) {
		bp_core_add_message( __( 'Security check failed.', 'buddypress-followers' ), 'error' );
		return;
	}

	$service = bp_follow_get_category_service();

	if ( 'follow' === $action ) {
		if ( $service->follow_term( $term_id, $taxonomy, bp_loggedin_user_id() ) ) {
			bp_core_add_message( __( 'You are now following this category.', 'buddypress-followers' ) );
		} else {
			bp_core_add_message( __( 'Failed to follow category.', 'buddypress-followers' ), 'error' );
		}
	} else {
		if ( $service->unfollow_term( $term_id, $taxonomy, bp_loggedin_user_id() ) ) {
			bp_core_add_message( __( 'You are no longer following this category.', 'buddypress-followers' ) );
		} else {
			bp_core_add_message( __( 'Failed to unfollow category.', 'buddypress-followers' ), 'error' );
		}
	}

	// Redirect back.
	wp_safe_redirect( wp_get_referer() );
	exit;
}
add_action( 'bp_actions', 'bp_follow_handle_term_actions', 5 );
