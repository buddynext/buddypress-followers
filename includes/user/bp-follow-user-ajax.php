<?php
/**
 * BP Follow AJAX Functions
 *
 * @package BP-Follow
 * @subpackage AJAX
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the BP Follow Ajax actions.
 *
 * @since 1.3.0
 */
function bp_follow_register_ajax_action() {
	if ( ! function_exists( 'bp_ajax_register_action' ) ) {
		return;
	}

	bp_ajax_register_action( 'bp_follow' );
	bp_ajax_register_action( 'bp_unfollow' );
}
add_action( 'bp_init', 'bp_follow_register_ajax_action' );

/**
 * AJAX callback when clicking on the "Follow" button to follow a user.
 *
 * @uses check_admin_referer() Checks to make sure the WP security nonce matches.
 * @uses bp_follow_start_following() Starts a user following another user.
 * @uses bp_follow_is_following() Checks to see if a user is following another user already.
 */
function bp_follow_ajax_action_start() {
	$service = bp_follow_service( '\\Followers\\Service\\AjaxService' );

	if ( ! $service ) {
		wp_send_json_success( array( 'button' => '' ) );
	}

	$service->follow();
}
add_action( 'wp_ajax_bp_follow', 'bp_follow_ajax_action_start' );

/**
 * AJAX callback when clicking on the "Unfollow" button to unfollow a user.
 *
 * @uses check_admin_referer() Checks to make sure the WP security nonce matches.
 * @uses bp_follow_stop_following() Stops a user following another user.
 * @uses bp_follow_is_following() Checks to see if a user is following another user already.
 */
function bp_follow_ajax_action_stop() {
	$service = bp_follow_service( '\\Followers\\Service\\AjaxService' );

	if ( ! $service ) {
		wp_send_json_success( array( 'button' => '' ) );
	}

	$service->unfollow();
}
add_action( 'wp_ajax_bp_unfollow', 'bp_follow_ajax_action_stop' );
