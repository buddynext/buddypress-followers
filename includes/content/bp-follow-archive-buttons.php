<?php
/**
 * Follow buttons for WordPress archive pages (author and category pages).
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Removed duplicate button functions to keep it simple - only using the title filter below

/**
 * AJAX handler for following/unfollowing from archive pages.
 * Simple approach: Just handle the follow/unfollow action.
 */
function bp_follow_archive_ajax_handler() {
	// Suppress PHP notices/warnings during AJAX to prevent breaking JSON
	error_reporting( E_ERROR | E_PARSE );

	check_ajax_referer( 'bp_follow_archive_nonce', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to follow.', 'buddypress-followers' ) ) );
	}

	$follow_type = isset( $_POST['follow_type'] ) ? sanitize_text_field( $_POST['follow_type'] ) : '';
	$item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
	$action = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : ''; // 'follow' or 'unfollow'

	// Validate required parameters
	if ( empty( $follow_type ) || empty( $item_id ) || empty( $action ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request parameters.', 'buddypress-followers' ) ) );
	}

	$success = false;
	$new_count = 0;

	if ( 'author' === $follow_type ) {
		$service = bp_follow_get_author_service();

		if ( 'follow' === $action ) {
			// Check if already following
			if ( $service->is_following_author( $item_id, $user_id ) ) {
				$success = true; // Already following, consider it a success
			} else {
				$success = $service->follow_author( $item_id, $user_id );
			}
		} else {
			$success = $service->unfollow_author( $item_id, $user_id );
		}

		$new_count = bp_follow_get_author_followers_count( $item_id );

	} elseif ( 'term' === $follow_type || 'category' === $follow_type ) {
		// Handle both 'term' and 'category' as follow_type for backwards compatibility
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : 'category';
		$service = bp_follow_get_category_service();

		if ( 'follow' === $action ) {
			// Check if already following
			if ( $service->is_following_term( $item_id, $taxonomy, $user_id ) ) {
				$success = true; // Already following, consider it a success
			} else {
				$success = $service->follow_term( $item_id, $taxonomy, $user_id );
			}
		} else {
			$success = $service->unfollow_term( $item_id, $taxonomy, $user_id );
		}

		$new_count = bp_follow_get_term_followers_count( $item_id, $taxonomy );
	}

	if ( $success ) {
		wp_send_json_success( array(
			'message' => 'follow' === $action ? __( 'Now following!', 'buddypress-followers' ) : __( 'Unfollowed.', 'buddypress-followers' ),
			'action' => $action,
			'new_count' => $new_count,
			'new_count_text' => sprintf( _n( '%s follower', '%s followers', $new_count, 'buddypress-followers' ), number_format_i18n( $new_count ) )
		) );
	} else {
		wp_send_json_error( array( 'message' => __( 'Action failed. Please try again.', 'buddypress-followers' ) ) );
	}
}
add_action( 'wp_ajax_bp_follow_archive_action', 'bp_follow_archive_ajax_handler' );

/**
 * Enqueue scripts and styles for archive pages.
 * Simple approach: Load for all logged-in users on frontend.
 */
function bp_follow_archive_enqueue_assets() {
	// Only for logged-in users on frontend
	if ( ! is_user_logged_in() || is_admin() ) {
		return;
	}

	// Enqueue styles
	wp_enqueue_style(
		'bp-follow-archive',
		BP_FOLLOW_URL . 'assets/css/archive-follow-buttons.css',
		array( 'dashicons' ),
		'1.0.3'
	);

	// Enqueue scripts
	wp_enqueue_script(
		'bp-follow-archive',
		BP_FOLLOW_URL . 'assets/js/archive-follow-buttons.js',
		array( 'jquery' ),
		'1.0.1',
		true
	);

	// Localize script
	wp_localize_script(
		'bp-follow-archive',
		'bpFollowArchive',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bp_follow_archive_nonce' ),
			'strings' => array(
				'follow'         => __( 'Follow', 'buddypress-followers' ),
				'following'      => __( 'Following', 'buddypress-followers' ),
				'unfollow'       => __( 'Unfollow', 'buddypress-followers' ),
			)
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bp_follow_archive_enqueue_assets', 10 );

/**
 * Add follow button using filter (most compatible method).
 * Simple approach: Show buttons for all logged-in users.
 */
function bp_follow_filter_archive_title( $title ) {
	// Only for logged-in users
	if ( ! is_user_logged_in() ) {
		return $title;
	}

	$button_html = '';

	if ( is_author() ) {
		$author_id = get_queried_object_id();
		$user_id = get_current_user_id();

		if ( $author_id !== $user_id ) {
			$is_following = bp_follow_is_following_author( $user_id, $author_id );
			$follower_count = bp_follow_get_author_followers_count( $author_id );

			$button_html = sprintf(
				'<div class="bp-follow-title-button">
					<button type="button" class="bp-follow-btn %s bp-follow-archive-button" data-follow-type="author" data-item-id="%d">
						<span class="dashicons %s"></span><span class="button-text">%s</span>
					</button>
					<span class="bp-follow-btn bp-follow-count">%s</span>
				</div>',
				$is_following ? 'following' : 'not-following',
				$author_id,
				$is_following ? 'dashicons-yes' : 'dashicons-plus-alt2',
				$is_following ? __( 'Following', 'buddypress-followers' ) : __( 'Follow', 'buddypress-followers' ),
				sprintf( _n( '%s follower', '%s followers', $follower_count, 'buddypress-followers' ), number_format_i18n( $follower_count ) )
			);
		}

	} elseif ( is_category() || is_tag() ) {
		$term = get_queried_object();
		$term_id = $term->term_id;
		$taxonomy = $term->taxonomy;
		$user_id = get_current_user_id();

		$is_following = bp_follow_is_following_term( $user_id, $term_id, $taxonomy );
		$follower_count = bp_follow_get_term_followers_count( $term_id, $taxonomy );

		$button_html = sprintf(
			'<div class="bp-follow-title-button">
				<button type="button" class="bp-follow-btn %s bp-follow-archive-button" data-follow-type="term" data-item-id="%d" data-taxonomy="%s">
					<span class="dashicons %s"></span><span class="button-text">%s</span>
				</button>
				<span class="bp-follow-btn bp-follow-count">%s</span>
			</div>',
			$is_following ? 'following' : 'not-following',
			$term_id,
			$taxonomy,
			$is_following ? 'dashicons-yes' : 'dashicons-plus-alt2',
			$is_following ? __( 'Following', 'buddypress-followers' ) : __( 'Follow', 'buddypress-followers' ),
			sprintf( _n( '%s follower', '%s followers', $follower_count, 'buddypress-followers' ), number_format_i18n( $follower_count ) )
		);
	}

	if ( $button_html ) {
		return '<div class="bp-follow-archive-title-wrapper">' . $title . $button_html . '</div>';
	}

	return $title;
}
add_filter( 'get_the_archive_title', 'bp_follow_filter_archive_title', 100 );