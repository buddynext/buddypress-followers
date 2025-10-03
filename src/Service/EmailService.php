<?php
/**
 * Email notification service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use function add_query_arg;
use function apply_filters;
use function bp_core_get_user_displayname;
use function bp_follow_get_user_url;
use function bp_get_settings_slug;
use function bp_get_notifications_slug;
use function bp_is_active;
use function bp_update_user_meta;
use function bp_get_user_meta;
use function bp_loggedin_user_id;
use function bp_displayed_user_id;
use function bp_send_email;
use function wp_parse_args;
use function in_array;
use function is_array;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles follow email notifications.
 *
 * @since 2.0.0
 */
class EmailService {
	/**
	 * Send notification email when a user gains a follower.
	 *
	 * Uses BuddyPress Core email system (bp_send_email) for consistent styling
	 * and customizable HTML templates.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args {
	 *     Optional. Array of arguments.
	 *
	 *     @type int $leader_id   User ID being followed. Default: displayed user ID.
	 *     @type int $follower_id User ID doing the following. Default: logged-in user ID.
	 * }
	 * @return bool True if email sent successfully, false otherwise.
	 */
	public function send_follow_email( $args = array() ) {
		$r = wp_parse_args(
			$args,
			array(
				'leader_id'   => bp_displayed_user_id(),
				'follower_id' => bp_loggedin_user_id(),
			)
		);

		if ( $r['follower_id'] === $r['leader_id'] ) {
			return false;
		}

		if ( 'no' === bp_get_user_meta( (int) $r['leader_id'], 'notification_starts_following', true ) ) {
			return false;
		}

		$has_notified = bp_get_user_meta( $r['follower_id'], 'bp_follow_has_notified', true );
		$has_notified = is_array( $has_notified ) ? $has_notified : array();

		if ( in_array( $r['leader_id'], $has_notified, true ) ) {
			return false;
		}

		$has_notified[] = $r['leader_id'];
		bp_update_user_meta( $r['follower_id'], 'bp_follow_has_notified', $has_notified );

		$follower_url = add_query_arg( 'bpf_read', 1, bp_follow_get_user_url( $r['follower_id'] ) );

		$unsubscribe_args = array(
			'user_id'           => $r['leader_id'],
			'notification_type' => 'bp-follow-new-follow',
		);

		$email_args = array(
			'tokens' => array(
				'follower.id'   => $r['follower_id'],
				'follower.name' => bp_core_get_user_displayname( $r['follower_id'] ),
				'follower.url'  => $follower_url,
				'leader.id'     => $r['leader_id'],
				'unsubscribe'   => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
		);

		// Allow filtering of email arguments.
		$email_args = apply_filters( 'bp_follow_notification_email_args', $email_args, $r );

		return (bool) bp_send_email( 'bp-follow-new-follow', (int) $r['leader_id'], $email_args );
	}
}
