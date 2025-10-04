<?php
/**
 * BP Follow User Template Functions
 *
 * @package BP-Follow
 * @subpackage Template
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output a follow / unfollow button for a given user depending on the follower status.
 *
 * @param mixed $args See bp_follow_get_add_follow_button() for full arguments.
 * @uses bp_follow_get_add_follow_button() Returns the follow / unfollow button
 * @author r-a-y
 * @since 1.1
 */
function bp_follow_add_follow_button( $args = '' ) {
	echo bp_follow_get_add_follow_button( $args );
}
/**
 * Returns a follow / unfollow button for a given user depending on the follower status.
 *
 * Checks to see if the follower is already following the leader.  If is following, returns
 * "Stop following" button; if not following, returns "Follow" button.
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 *     @type string $link_text The anchor text for the link.
 *     @type string $link_title The title attribute for the link.
 *     @type string $wrapper_class CSS class for the wrapper container.
 *     @type string $link_class CSS class for the link.
 *     @type string $wrapper The element for the wrapper container. Defaults to 'div'.
 * }
 * @return mixed String of the button on success.  Boolean false on failure.
 * @uses bp_get_button() Renders a button using the BP Button API
 * @author r-a-y
 * @since 1.1
 */
function bp_follow_get_add_follow_button( $args = '' ) {
	$service = bp_follow_service( '\Followers\Service\ButtonService' );

	if ( $service ) {
		return $service->render_button( $args );
	}

	return false;
}
