<?php
/**
 * BP Follow Notifications
 *
 * @package BP-Follow
 * @subpackage Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** NOTIFICATIONS API ***************************************************/

/**
 * Removes notifications made by a user.
 *
 * @since 1.2.1
 *
 * @param int $user_id The user ID.
 */
function bp_follow_remove_notifications_for_user( $user_id = 0 ) {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return;
	}

	$service->remove_all_for_user( $user_id );
}
add_action( 'bp_follow_remove_data', 'bp_follow_remove_notifications_for_user' );

/**
 * Adds notification when a user follows another user.
 *
 * @since 1.2.1
 *
 * @param object $follow The BP_Follow object.
 */
function bp_follow_notifications_add_on_follow( BP_Follow $follow ) {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return;
	}

	$service->add_follow_notification( $follow );

	bp_follow_service( '\\Followers\\Service\\EmailService' )->send_follow_email( array(
		'leader_id'   => $follow->leader_id,
		'follower_id' => $follow->follower_id,
	) );
}
add_action( 'bp_follow_start_following', 'bp_follow_notifications_add_on_follow' );

/**
 * Removes notification when a user unfollows another user.
 *
 * @since 1.2.1
 *
 * @param object $follow The BP_Follow object.
 */
function bp_follow_notifications_remove_on_unfollow( BP_Follow $follow ) {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return;
	}

	$service->remove_follow_notification( $follow );
}
add_action( 'bp_follow_stop_following', 'bp_follow_notifications_remove_on_unfollow' );

/**
 * Mark notification as read when a logged-in user visits their follower's profile.
 *
 * This is a new feature in BuddyPress 1.9.
 *
 * @since 1.2.1
 */
function bp_follow_notifications_mark_follower_profile_as_read() {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return;
	}

	$service->mark_profile_notification();
}
add_action( 'bp_members_screen_display_profile', 'bp_follow_notifications_mark_follower_profile_as_read' );
add_action( 'bp_activity_screen_my_activity',    'bp_follow_notifications_mark_follower_profile_as_read' );

/**
 * Delete notifications when a logged-in user visits their followers page.
 *
 * Since 1.2.1, when the "X users are now following you" notification appears,
 * users will be redirected to the new notifications unread page instead of
 * the logged-in user's followers page.  This is so users can see who followed
 * them and in the date order in which they were followed.
 *
 * For backwards-compatibility, we still keep the old method of redirecting to
 * the logged-in user's followers page so notifications can be deleted for
 * older versions of BuddyPress.
 *
 * Will probably remove this in a future release.
 *
 * @since 1.2.1
 */
function bp_follow_notifications_delete_on_followers_page() {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return;
	}

	$service->clear_on_followers_page();
}
add_action( 'bp_follow_screen_followers', 'bp_follow_notifications_delete_on_followers_page' );

/**
 * When we're on the notification's 'read' page, remove 'bpf_read' query arg.
 *
 * Since we are already on the 'read' page, notifications on this page are
 * already marked as read.  So, we no longer need to add our special
 * 'bpf_read' query argument to each notification to determine whether we
 * need to clear it.
 *
 * @since 1.2.1
 */
function bp_follow_notifications_remove_queryarg_from_userlink( $retval ) {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return $retval;
	}

	return $service->strip_read_query_arg( $retval );
}
add_filter( 'bp_follow_new_followers_notification', 'bp_follow_notifications_remove_queryarg_from_userlink' );

/**
 * Filter notifications by component action.
 *
 * Only applicable in BuddyPress 2.1+.
 *
 * @since 1.3.0
 *
 * @param array $retval Current notification parameters.
 * @return array
 */
function bp_follow_filter_unread_notifications( $retval ) {
	$service = bp_follow_service( '\\Followers\\Service\\NotificationService' );

	if ( ! $service ) {
		return $retval;
	}

	return $service->filter_unread_args( $retval );
}
add_filter( 'bp_after_has_notifications_parse_args', 'bp_follow_filter_unread_notifications' );

/** SETTINGS ************************************************************/

/**
 * Adds user configurable notification settings for the component.
 */
function bp_follow_user_screen_notification_settings() {
	if ( ! $notify = bp_get_user_meta( bp_displayed_user_id(), 'notification_starts_following', true ) ) {
		$notify = 'yes';
	}

	if ( ! $digest = bp_get_user_meta( bp_displayed_user_id(), 'notification_follows_digest', true ) ) {
		$digest = 'no';
	}

	if ( ! $frequency = bp_get_user_meta( bp_displayed_user_id(), 'notification_follows_digest_frequency', true ) ) {
		$frequency = 'weekly';
	}

?>

	<tr>
		<td></td>
		<td><?php esc_html_e( 'A member starts following your activity', 'buddypress-followers' ) ?></td>
		<td class="yes"><input type="radio" name="notifications[notification_starts_following]" value="yes" <?php checked( $notify, 'yes', true ) ?>/></td>
		<td class="no"><input type="radio" name="notifications[notification_starts_following]" value="no" <?php checked( $notify, 'no', true ) ?>/></td>
	</tr>

	<tr>
		<td></td>
		<td><?php esc_html_e( 'Send follower notifications as digest', 'buddypress-followers' ) ?></td>
		<td class="yes"><input type="radio" name="notifications[notification_follows_digest]" value="yes" <?php checked( $digest, 'yes', true ) ?>/></td>
		<td class="no"><input type="radio" name="notifications[notification_follows_digest]" value="no" <?php checked( $digest, 'no', true ) ?>/></td>
	</tr>

	<tr id="bp-follow-digest-frequency" style="<?php echo 'yes' !== $digest ? 'display:none;' : ''; ?>">
		<td></td>
		<td colspan="3">
			<label>
				<?php esc_html_e( 'Digest frequency:', 'buddypress-followers' ) ?>
				<select name="notifications[notification_follows_digest_frequency]">
					<option value="daily" <?php selected( $frequency, 'daily' ) ?>><?php esc_html_e( 'Daily', 'buddypress-followers' ) ?></option>
					<option value="weekly" <?php selected( $frequency, 'weekly' ) ?>><?php esc_html_e( 'Weekly', 'buddypress-followers' ) ?></option>
				</select>
			</label>
		</td>
	</tr>

	<script>
	jQuery(document).ready(function($) {
		$('input[name="notifications[notification_follows_digest]"]').on('change', function() {
			if ($(this).val() === 'yes' && $(this).is(':checked')) {
				$('#bp-follow-digest-frequency').show();
			} else if ($(this).val() === 'no' && $(this).is(':checked')) {
				$('#bp-follow-digest-frequency').hide();
			}
		});
	});
	</script>

<?php
}
add_action( 'bp_follow_screen_notification_settings', 'bp_follow_user_screen_notification_settings' );

/** EMAIL ***************************************************************/

/**
 * Send an email to the leader when someone follows them.
 *
 * @todo Use BP_Email.
 *
 * @uses bp_core_get_user_displayname() Get the display name for a user
 * @uses bp_follow_get_user_url() Get the profile url for a user
 * @uses bp_core_get_core_userdata() Get the core userdata for a user without extra usermeta
 * @uses wp_mail() Send an email using the built in WP mail class
 */
function bp_follow_new_follow_email_notification( $args = '' ) {
	$service = bp_follow_service( '\\Followers\\Service\\EmailService' );

	if ( ! $service ) {
		return false;
	}

	return $service->send_follow_email( $args );
}
