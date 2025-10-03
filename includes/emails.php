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
		'post_title'   => __( '[{{{site.name}}}] {{follower.name}} is now following you', 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<a href=\"{{{follower.url}}}\">{{follower.name}}</a> is now following your activity.\n\nTo view {{follower.name}}'s profile, visit: <a href=\"{{{follower.url}}}\">{{{follower.url}}}</a>", 'buddypress-followers' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{follower.name}} is now following your activity.\n\nTo view {{follower.name}}'s profile, visit: {{{follower.url}}}", 'buddypress-followers' ),
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
