<?php
/**
 * Email digest service for followers.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use function bp_core_get_user_displayname;
use function bp_follow_get_user_url;
use function bp_get_user_meta;
use function bp_update_user_meta;
use function bp_send_email;
use function add_query_arg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles email digest notifications for followers.
 *
 * Sends daily or weekly summaries of new followers to reduce email fatigue.
 *
 * @since 2.0.0
 */
class DigestService {
	/**
	 * Queue a follower for digest notification.
	 *
	 * Instead of sending immediate emails, followers are queued for digest.
	 *
	 * @since 2.0.0
	 *
	 * @param int $leader_id   User ID being followed.
	 * @param int $follower_id User ID doing the following.
	 * @return bool True if queued successfully.
	 */
	public function queue_follower( $leader_id, $follower_id ) {
		// Get current digest queue for this user.
		$queue = bp_get_user_meta( $leader_id, 'bp_follow_digest_queue', true );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}

		// Add follower to queue with timestamp.
		$queue[ $follower_id ] = current_time( 'timestamp' );

		// Save updated queue.
		return bp_update_user_meta( $leader_id, 'bp_follow_digest_queue', $queue );
	}

	/**
	 * Send digest email to a user.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id User ID to send digest to.
	 * @return bool True if email sent successfully.
	 */
	public function send_digest( $user_id ) {
		// Get queued followers.
		$queue = bp_get_user_meta( $user_id, 'bp_follow_digest_queue', true );
		if ( empty( $queue ) || ! is_array( $queue ) ) {
			return false;
		}

		// Check user's digest preference.
		$preference = bp_get_user_meta( $user_id, 'notification_follows_digest', true );
		if ( 'no' === $preference ) {
			return false;
		}

		// Get digest frequency (daily or weekly).
		$frequency = bp_get_user_meta( $user_id, 'notification_follows_digest_frequency', true );
		if ( empty( $frequency ) ) {
			$frequency = 'weekly';
		}

		// Check if it's time to send digest.
		$last_sent = bp_get_user_meta( $user_id, 'bp_follow_digest_last_sent', true );
		if ( ! $this->should_send_digest( $last_sent, $frequency ) ) {
			return false;
		}

		// Build follower list for email.
		$follower_names = array();
		$follower_links = array();
		$count          = count( $queue );

		foreach ( array_keys( $queue ) as $follower_id ) {
			$follower_names[] = bp_core_get_user_displayname( $follower_id );
			$follower_links[] = bp_follow_get_user_url( $follower_id );
		}

		// Prepare email tokens.
		$unsubscribe_args = array(
			'user_id'           => $user_id,
			'notification_type' => 'bp-follow-digest',
		);

		$email_args = array(
			'tokens' => array(
				'follower.count'       => $count,
				'follower.names'       => implode( ', ', array_slice( $follower_names, 0, 5 ) ),
				'follower.names_full'  => implode( "\n", $follower_names ),
				'follower.links'       => implode( "\n", $follower_links ),
				'digest.period'        => $frequency === 'daily' ? __( 'today', 'buddypress-followers' ) : __( 'this week', 'buddypress-followers' ),
				'followers.url'        => bp_follow_get_user_url( $user_id, array( buddypress()->follow->followers->slug ) ),
				'unsubscribe'          => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
		);

		// Send digest email.
		$result = bp_send_email( 'bp-follow-digest', (int) $user_id, $email_args );

		// If sent successfully, clear queue and update timestamp.
		if ( $result ) {
			bp_update_user_meta( $user_id, 'bp_follow_digest_queue', array() );
			bp_update_user_meta( $user_id, 'bp_follow_digest_last_sent', current_time( 'timestamp' ) );
		}

		return (bool) $result;
	}

	/**
	 * Check if digest should be sent based on frequency.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $last_sent  Timestamp of last sent digest.
	 * @param string $frequency  Digest frequency (daily or weekly).
	 * @return bool True if should send digest.
	 */
	protected function should_send_digest( $last_sent, $frequency ) {
		if ( empty( $last_sent ) ) {
			return true;
		}

		$current_time = current_time( 'timestamp' );
		$time_diff    = $current_time - $last_sent;

		if ( 'daily' === $frequency ) {
			// Send daily at most once per day.
			return $time_diff >= DAY_IN_SECONDS;
		}

		// Send weekly at most once per week.
		return $time_diff >= ( DAY_IN_SECONDS * 7 );
	}

	/**
	 * Process all pending digests.
	 *
	 * Called by cron job to send digest emails.
	 *
	 * @since 2.0.0
	 *
	 * @return int Number of digests sent.
	 */
	public function process_all_digests() {
		global $wpdb;

		// Get all users with pending digest queues.
		$users = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = %s
				AND meta_value != %s",
				'bp_follow_digest_queue',
				serialize( array() )
			)
		);

		$sent = 0;

		foreach ( $users as $user_id ) {
			if ( $this->send_digest( $user_id ) ) {
				$sent++;
			}
		}

		return $sent;
	}

	/**
	 * Check if user prefers digest emails over instant notifications.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id User ID to check.
	 * @return bool True if user prefers digest.
	 */
	public function is_digest_enabled( $user_id ) {
		$preference = bp_get_user_meta( $user_id, 'notification_follows_digest', true );
		return 'yes' === $preference;
	}
}
