<?php
/**
 * Notification service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use BP_Follow;
use function add_query_arg;
use function apply_filters;
use function bp_core_add_notification;
use function bp_core_delete_notifications_by_item_id;
use function bp_core_delete_notifications_by_type;
use function bp_core_delete_notifications_from_user;
use function bp_core_get_user_displayname;
use function bp_follow_get_user_url;
use function bp_is_active;
use function bp_is_current_action;
use function bp_is_user_notifications;
use function bp_notifications_add_notification;
use function bp_notifications_delete_all_notifications_by_type;
use function bp_notifications_delete_notifications_by_item_id;
use function bp_notifications_delete_notifications_by_type;
use function bp_notifications_mark_notifications_by_item_id;
use function bp_notifications_permalink;
use function bp_loggedin_user_id;
use function bp_displayed_user_id;
use function buddypress;
use function did_action;
use function sanitize_title;
use function wp_safe_redirect;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles BuddyPress notification integration.
 */
class NotificationService {
	/**
	 * Add notification when a follow happens.
	 *
	 * @param BP_Follow $follow Follow relationship.
	 */
	public function add_follow_notification( BP_Follow $follow ) {
		$bp = buddypress();

		if ( ! empty( $follow->follow_type ) ) {
			return;
		}

		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_add_notification( array(
				'item_id'          => $follow->follower_id,
				'user_id'          => $follow->leader_id,
				'component_name'   => $bp->follow->id,
				'component_action' => 'new_follow',
			) );
		} else {
			bp_core_add_notification(
				$follow->follower_id,
				$follow->leader_id,
				$bp->follow->id,
				'new_follow'
			);
		}
	}

	/**
	 * Remove notification when unfollow happens.
	 *
	 * @param BP_Follow $follow Follow relationship.
	 */
	public function remove_follow_notification( BP_Follow $follow ) {
		$bp = buddypress();

		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_delete_notifications_by_item_id( $follow->leader_id, $follow->follower_id, $bp->follow->id, 'new_follow' );
		} else {
			bp_core_delete_notifications_by_item_id( $follow->leader_id, $follow->follower_id, $bp->follow->id, 'new_follow' );
		}
	}

	/**
	 * Clear notifications when user visits followers page.
	 */
	public function clear_on_followers_page() {
		if ( ! isset( $_GET['new'] ) || ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$bp = buddypress();

		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_delete_notifications_by_type( bp_loggedin_user_id(), $bp->follow->id, 'new_follow' );
		} else {
			bp_core_delete_notifications_by_type( bp_loggedin_user_id(), $bp->follow->id, 'new_follow' );
		}
	}

	/**
	 * Mark notifications read when visiting follower profile.
	 */
	public function mark_profile_notification() {
		if ( ! isset( $_GET['bpf_read'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$bp = buddypress();

		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), bp_displayed_user_id(), $bp->follow->id, 'new_follow' );
		} else {
			bp_core_delete_notifications_by_item_id( bp_loggedin_user_id(), bp_displayed_user_id(), $bp->follow->id, 'new_follow' );
		}
	}

	/**
	 * Remove query arg from notification links when read.
	 *
	 * @param string $link Notification link.
	 * @return string
	 */
	public function strip_read_query_arg( $link ) {
		if ( bp_is_current_action( 'read' ) ) {
			if ( did_action( 'bp_after_member_body' ) ) {
				return $link;
			}

			$link = str_replace( '?bpf_read', '', $link );
		}

		return $link;
	}

	/**
	 * Filter unread notifications query args by action.
	 *
	 * @param array $args Notification query args.
	 * @return array
	 */
	public function filter_unread_args( $args ) {
		if ( ! bp_is_user_notifications() ) {
			return $args;
		}

		if ( ! did_action( 'bp_before_member_body' ) ) {
			return $args;
		}

		if ( ! empty( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['component_action'] = sanitize_title( $_GET['action'] );
			remove_filter( 'bp_after_has_notifications_parse_args', array( $this, 'filter_unread_args' ) );
		}

		return $args;
	}

	/**
	 * Remove notifications when a user account is deleted.
	 *
	 * @param int $user_id User ID.
	 */
	public function remove_all_for_user( $user_id ) {
		$bp = buddypress();

		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_delete_all_notifications_by_type( $user_id, $bp->follow->id, 'new_follow' );
		} else {
			bp_core_delete_notifications_from_user( $user_id, $bp->follow->id, 'new_follow' );
		}
	}
}
