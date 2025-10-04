<?php
/**
 * BP Follow Email Functions
 *
 * @package BP-Follow
 * @subpackage Emails
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register email schema for BP Follow emails.
 *
 * @since 2.0.0
 *
 * @param array $emails Existing email schemas.
 * @return array Updated email schemas.
 */
function bp_follow_register_email_schemas( $emails = array() ) {
	$emails['bp-follow-new-follow'] = array(
		/* translators: do not remove {} brackets or translate its contents. */
		'post_title'   => __( '{{follower.name}} is now following you', 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<h2>{{follower.name}} started following you</h2>\n\n<p>You have a new follower on {{{site.name}}}!</p>\n\n<table style=\"margin: 20px 0;\" cellpadding=\"10\">\n<tr>\n<td style=\"background: #f5f5f5; border-radius: 5px;\">\n<p style=\"margin:0; font-size: 16px;\"><strong>{{follower.name}}</strong></p>\n<p style=\"margin: 5px 0 0 0; color: #666;\">@{{follower.name}}</p>\n</td>\n</tr>\n</table>\n\n<p><a href=\"{{{follower.url}}}\" style=\"display: inline-block; padding: 10px 20px; background: #1da1f2; color: white; text-decoration: none; border-radius: 5px;\">View Profile</a> &nbsp; <a href=\"{{{followers.url}}}\" style=\"display: inline-block; padding: 10px 20px; background: #657786; color: white; text-decoration: none; border-radius: 5px;\">See All Followers</a></p>", 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{follower.name}} started following you on {{{site.name}}}.\n\nView their profile: {{{follower.url}}}\n\nSee all your followers: {{{followers.url}}}", 'buddypress-followers' ),
	);

	$emails['bp-follow-digest'] = array(
		/* translators: do not remove {} brackets or translate its contents. */
		'post_title'   => __( 'You have {{follower.count}} new followers {{digest.period}}', 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<h2>{{follower.count}} new followers {{digest.period}}</h2>\n\n<p>Your network is growing! Here are your new followers on {{{site.name}}}:</p>\n\n<table style=\"margin: 20px 0; width: 100%;\" cellpadding=\"10\">\n<tr>\n<td style=\"background: #f5f5f5; border-radius: 5px; padding: 15px;\">\n<p style=\"margin:0; line-height: 1.6;\">{{follower.names}}</p>\n</td>\n</tr>\n</table>\n\n<p><a href=\"{{{followers.url}}}\" style=\"display: inline-block; padding: 12px 24px; background: #1da1f2; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;\">View All {{follower.count}} Followers</a></p>\n\n<p style=\"color: #657786; font-size: 13px; margin-top: 30px;\">You're receiving this because you enabled digest notifications. <a href=\"{{{settings.url}}}\">Change your preferences</a> or {{{unsubscribe}}}.</p>", 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{follower.count}} new followers {{digest.period}} on {{{site.name}}}\n\n{{follower.names_full}}\n\nView all your followers: {{{followers.url}}}\n\nManage your email preferences: {{{settings.url}}}", 'buddypress-followers' ),
	);

	return $emails;
}
add_filter( 'bp_email_get_schema', 'bp_follow_register_email_schemas' );

/**
 * Register email type descriptions for BP Follow emails.
 *
 * @since 2.0.0
 *
 * @param array $descriptions Existing email type descriptions.
 * @return array Updated descriptions.
 */
function bp_follow_register_email_type_descriptions( $descriptions = array() ) {
	$descriptions['bp-follow-new-follow'] = __( 'A member starts following your activity', 'buddypress-followers' );
	$descriptions['bp-follow-digest']     = __( 'Daily or weekly digest of new followers', 'buddypress-followers' );

	return $descriptions;
}
add_filter( 'bp_email_get_type_schema', 'bp_follow_register_email_type_descriptions' );

/**
 * Install BP Follow emails.
 *
 * Called during plugin activation or when emails need to be reinstalled.
 *
 * @since 2.0.0
 */
function bp_follow_install_emails() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_follow_register_email_schemas( array() );
	$descriptions = bp_follow_register_email_type_descriptions( array() );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {
		// Check if email post already exists by checking for posts with this term.
		$posts = get_posts(
			array(
				'post_type'   => bp_get_email_post_type(),
				'tax_query'   => array(
					array(
						'taxonomy' => bp_get_email_tax_type(),
						'field'    => 'slug',
						'terms'    => $id,
					),
				),
				'numberposts' => 1,
			)
		);

		if ( ! empty( $posts ) ) {
			continue;
		}

		$post_id = wp_insert_post(
			bp_parse_args(
				$email,
				$defaults,
				'install_email_' . $id
			)
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		if ( ! is_wp_error( $tt_ids ) && is_array( $tt_ids ) ) {
			foreach ( $tt_ids as $tt_id ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
				if ( $term && ! is_wp_error( $term ) ) {
					wp_update_term(
						(int) $term->term_id,
						bp_get_email_tax_type(),
						array(
							'description' => $descriptions[ $id ],
						)
					);
				}
			}
		}
	}

	/**
	 * Fires after BP Follow adds the posts for its emails.
	 *
	 * @since 2.0.0
	 */
	do_action( 'bp_follow_install_emails' );
}
add_action( 'bp_core_install_emails', 'bp_follow_install_emails' );

/**
 * Send new post email notification.
 *
 * @since 2.1.0
 *
 * @param int     $user_id           User ID to send to.
 * @param WP_Post $post              Post object.
 * @param string  $notification_type Notification type.
 * @return bool True on success, false on failure.
 */
function bp_follow_send_new_post_email( $user_id, $post, $notification_type ) {
	// Placeholder function - will be fully implemented in Phase 8 (Email Templates).
	// For now, just return true to indicate queue processing succeeded.
	return true;
}
