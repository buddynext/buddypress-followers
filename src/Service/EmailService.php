<?php
/**
 * Email notification service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use function add_query_arg;
use function apply_filters;
use function bp_core_get_core_userdata;
use function bp_core_get_user_displayname;
use function bp_follow_get_user_url;
use function bp_get_option;
use function bp_get_settings_slug;
use function bp_get_notifications_slug;
use function bp_is_active;
use function bp_update_user_meta;
use function bp_get_user_meta;
use function bp_loggedin_user_id;
use function bp_displayed_user_id;
use function wp_mail;
use function wp_parse_args;
use function wp_specialchars_decode;
use function in_array;
use function is_array;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles follow email notifications.
 */
class EmailService {
	/**
	 * Send notification email when a user gains a follower.
	 *
	 * @param array $args Arguments.
	 * @return bool
	 */
	public function send_follow_email( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
		) );

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

		$follower_name = wp_specialchars_decode( bp_core_get_user_displayname( $r['follower_id'] ), ENT_QUOTES );
		$follower_link = add_query_arg( 'bpf_read', 1, bp_follow_get_user_url( $r['follower_id'] ) );

		$leader_userdata = bp_core_get_core_userdata( $r['leader_id'] );
		$to              = apply_filters( 'bp_follow_notification_to', $leader_userdata->user_email );
		$subject         = apply_filters(
			'bp_follow_notification_subject',
			'[' . wp_specialchars_decode( bp_get_option( 'blogname' ), ENT_QUOTES ) . '] ' . sprintf( __( '%s is now following you', 'buddypress-followers' ), $follower_name ),
			$follower_name
		);

		$message = sprintf(
			__( "%s is now following your activity.\n\nTo view %s's profile: %s", 'buddypress-followers' ),
			$follower_name,
			$follower_name,
			$follower_link
		);

		if ( bp_is_active( 'settings' ) ) {
			$settings_link = bp_follow_get_user_url(
				$r['leader_id'],
				array( bp_get_settings_slug(), bp_get_notifications_slug() )
			);

			$message .= sprintf(
				__( "\n\n---------------------\nTo disable these notifications please log in and go to:\n%s", 'buddypress-followers' ),
				$settings_link
			);
		}

		$message = apply_filters( 'bp_follow_notification_message', wp_specialchars_decode( $message, ENT_QUOTES ), $follower_name, $follower_link );

		return (bool) wp_mail( $to, $subject, $message );
	}
}
