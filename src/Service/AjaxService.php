<?php
/**
 * AJAX service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use function apply_filters;
use function check_admin_referer;
use function esc_attr;
use function wp_send_json_success;
use function wp_unslash;
use function bp_follow_get_add_follow_button;
use function bp_loggedin_user_id;
use function bp_follow_start_following;
use function bp_follow_stop_following;
use function bp_follow_is_following;
use function bp_get_button;
use function __;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles AJAX follow/unfollow requests.
 */
class AjaxService {
	/**
	 * Process follow action.
	 */
	public function follow() {
		check_admin_referer( 'start_following' );

		$leader_id  = isset( $_POST['uid'] ) ? (int) wp_unslash( $_POST['uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$link_class = isset( $_POST['link_class'] ) ? str_replace( 'follow ', '', wp_unslash( $_POST['link_class'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$success = bp_follow_start_following( array(
			'leader_id'   => $leader_id,
			'follower_id' => bp_loggedin_user_id(),
		) );

		$output = $this->build_button_response( $leader_id, $link_class, $success ? 'unfollow' : 'error_follow' );

		$output = apply_filters( 'bp_follow_ajax_action_start_response', array(
			'button' => $output,
		), $leader_id );

		wp_send_json_success( $output );
	}

	/**
	 * Process unfollow action.
	 */
	public function unfollow() {
		check_admin_referer( 'stop_following' );

		$leader_id  = isset( $_POST['uid'] ) ? (int) wp_unslash( $_POST['uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$link_class = isset( $_POST['link_class'] ) ? str_replace( 'unfollow ', '', wp_unslash( $_POST['link_class'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$success = bp_follow_stop_following( array(
			'leader_id'   => $leader_id,
			'follower_id' => bp_loggedin_user_id(),
		) );

		$output = $this->build_button_response( $leader_id, $link_class, $success ? 'follow' : 'error_unfollow' );

		$output = apply_filters( 'bp_follow_ajax_action_stop_response', array(
			'button' => $output,
		), $leader_id );

		wp_send_json_success( $output );
	}

	/**
	 * Build button markup for response.
	 *
	 * @param int         $leader_id  Leader ID.
	 * @param string|bool $link_class Link class.
	 * @param string      $state      Response state.
	 * @return string
	 */
	protected function build_button_response( $leader_id, $link_class, $state ) {
		$args = array(
			'leader_id'   => $leader_id,
			'follower_id' => bp_loggedin_user_id(),
			'wrapper'     => false,
			'link_class'  => $link_class,
		);

		switch ( $state ) {
			case 'unfollow':
				return bp_follow_get_add_follow_button( $args );

			case 'follow':
				return bp_follow_get_add_follow_button( $args );

			case 'error_follow':
				return bp_follow_is_following( array(
					'leader_id'   => $leader_id,
					'follower_id' => bp_loggedin_user_id(),
				) )
					? bp_get_button( array_merge( array( 'link_text' => __( 'Already following', 'buddypress-followers' ) ), $args ) )
					: bp_get_button( array_merge( array( 'link_text' => __( 'Error following user', 'buddypress-followers' ) ), $args ) );

			case 'error_unfollow':
				return bp_follow_is_following( array(
					'leader_id'   => $leader_id,
					'follower_id' => bp_loggedin_user_id(),
				) )
					? bp_get_button( array_merge( array( 'link_text' => __( 'Error unfollowing user', 'buddypress-followers' ) ), $args ) )
					: bp_get_button( array_merge( array( 'link_text' => __( 'Not following', 'buddypress-followers' ) ), $args ) );
		}

		return '';
	}
}
