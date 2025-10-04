<?php
/**
 * Content Following Hooks.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Trigger notifications when a new post is published.
 *
 * @since 2.1.0
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function bp_follow_notify_new_post( $new_status, $old_status, $post ) {
	// Only notify when post transitions to 'publish'.
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}

	// Check if post type is enabled.
	if ( ! bp_follow_is_post_type_enabled( $post->post_type ) ) {
		return;
	}

	// Get author followers.
	$service      = bp_follow_get_author_service();
	$follower_ids = $service->get_author_followers( $post->post_author, $post->post_type );

	if ( empty( $follower_ids ) ) {
		return;
	}

	/**
	 * Fire action when new post is published.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Post $post         Post object.
	 * @param array   $follower_ids Array of follower user IDs.
	 */
	do_action( 'bp_follow_new_post_published', $post, $follower_ids );

	// Queue notifications for followers.
	bp_follow_queue_post_notifications( $post, $follower_ids, 'author' );

	// Also check for category/tag followers.
	bp_follow_notify_taxonomy_followers( $post );
}
add_action( 'transition_post_status', 'bp_follow_notify_new_post', 10, 3 );

/**
 * Notify followers of categories/tags when a post is published.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post Post object.
 */
function bp_follow_notify_taxonomy_followers( $post ) {
	$taxonomies = get_object_taxonomies( $post->post_type );
	$enabled_taxonomies = bp_follow_get_enabled_taxonomies();

	// Filter to only enabled taxonomies.
	$taxonomies = array_intersect( $taxonomies, $enabled_taxonomies );

	if ( empty( $taxonomies ) ) {
		return;
	}

	$service = bp_follow_get_category_service();

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term_id ) {
			$follower_ids = $service->get_term_followers( $term_id, $taxonomy );

			if ( empty( $follower_ids ) ) {
				continue;
			}

			/**
			 * Fire action when new post with followed term is published.
			 *
			 * @since 2.1.0
			 *
			 * @param WP_Post $post         Post object.
			 * @param int     $term_id      Term ID.
			 * @param string  $taxonomy     Taxonomy name.
			 * @param array   $follower_ids Array of follower user IDs.
			 */
			do_action( 'bp_follow_new_post_with_term', $post, $term_id, $taxonomy, $follower_ids );

			// Queue notifications for term followers.
			bp_follow_queue_post_notifications( $post, $follower_ids, 'term', array(
				'term_id'  => $term_id,
				'taxonomy' => $taxonomy,
			) );
		}
	}
}

/**
 * Queue post notifications for batch processing.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post         Post object.
 * @param array   $follower_ids Array of follower user IDs.
 * @param string  $follow_type  Follow type: 'author' or 'term'.
 * @param array   $extra_data   Extra data for term follows.
 */
function bp_follow_queue_post_notifications( $post, $follower_ids, $follow_type = 'author', $extra_data = array() ) {
	global $wpdb, $bp;

	if ( empty( $follower_ids ) ) {
		return;
	}

	$table_prefix = $bp->table_prefix;
	$queue_table  = $table_prefix . 'bp_follow_notification_queue';

	$notification_type = 'new_post_' . $follow_type;

	// Get post type settings.
	$post_types = bp_get_option( '_bp_follow_post_types', array() );
	$instant_notify = isset( $post_types[ $post->post_type ]['instant_notifications'] )
		? $post_types[ $post->post_type ]['instant_notifications']
		: true;

	$status = $instant_notify ? 'pending' : 'queued';

	foreach ( $follower_ids as $follower_id ) {
		// Skip if author is following themselves.
		if ( (int) $follower_id === (int) $post->post_author ) {
			continue;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$queue_table,
			array(
				'user_id'          => $follower_id,
				'item_id'          => $post->ID,
				'item_type'        => $post->post_type,
				'notification_type' => $notification_type,
				'status'           => $status,
				'priority'         => 5,
				'retry_count'      => 0,
				'scheduled_time'   => current_time( 'mysql' ),
				'created_time'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	// If instant notifications are enabled, schedule processing.
	if ( $instant_notify ) {
		if ( ! wp_next_scheduled( 'bp_follow_process_notification_queue' ) ) {
			wp_schedule_single_event( time() + 60, 'bp_follow_process_notification_queue' );
		}
	}
}

/**
 * Process notification queue.
 *
 * @since 2.1.0
 */
function bp_follow_process_notification_queue() {
	global $wpdb, $bp;

	$table_prefix = $bp->table_prefix;
	$queue_table  = $table_prefix . 'bp_follow_notification_queue';

	// Get advanced settings.
	$settings    = bp_get_option( '_bp_follow_advanced_settings', array() );
	$batch_size  = isset( $settings['queue_batch_size'] ) ? $settings['queue_batch_size'] : 50;

	// Get pending notifications.
 // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$notifications = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$queue_table}
		WHERE status = 'pending'
		AND scheduled_time <= %s
		ORDER BY priority DESC, created_time ASC
		LIMIT %d",
		current_time( 'mysql' ),
		$batch_size
	) );

	if ( empty( $notifications ) ) {
		return;
	}

	foreach ( $notifications as $notification ) {
		$success = bp_follow_send_content_notification( $notification );

		if ( $success ) {
			// Mark as processed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$queue_table,
				array(
					'status'         => 'processed',
					'processed_time' => current_time( 'mysql' ),
				),
				array( 'id' => $notification->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Increment retry count.
			$retry_count = $notification->retry_count + 1;

			if ( $retry_count >= 3 ) {
				// Mark as failed after 3 retries.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$queue_table,
					array( 'status' => 'failed' ),
					array( 'id' => $notification->id ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				// Schedule retry.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$queue_table,
					array(
						'retry_count'    => $retry_count,
						'scheduled_time' => gmdate( 'Y-m-d H:i:s', time() + ( 300 * $retry_count ) ), // Exponential backoff.
					),
					array( 'id' => $notification->id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
			}
		}
	}

	// If there are more pending notifications, schedule another run.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$remaining = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$queue_table} WHERE status = 'pending' AND scheduled_time <= %s",
		current_time( 'mysql' )
	) );

	if ( $remaining > 0 ) {
		wp_schedule_single_event( time() + 60, 'bp_follow_process_notification_queue' );
	}
}
add_action( 'bp_follow_process_notification_queue', 'bp_follow_process_notification_queue' );

/**
 * Send content notification.
 *
 * @since 2.1.0
 *
 * @param object $notification Notification object from queue.
 * @return bool True on success, false on failure.
 */
function bp_follow_send_content_notification( $notification ) {
	// Get the post.
	$post = get_post( $notification->item_id );

	if ( ! $post ) {
		return false;
	}

	// Create BuddyPress notification.
	$notification_id = bp_notifications_add_notification( array(
		'user_id'           => $notification->user_id,
		'item_id'           => $post->ID,
		'secondary_item_id' => $post->post_author,
		'component_name'    => 'follow',
		'component_action'  => $notification->notification_type,
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );

	if ( ! $notification_id ) {
		return false;
	}

	// Send email notification if user has it enabled.
	$send_email = 'yes' === bp_get_user_meta( $notification->user_id, 'notification_follows_new_post', true );

	if ( $send_email ) {
		bp_follow_send_new_post_email( $notification->user_id, $post, $notification->notification_type );
	}

	return true;
}

/**
 * Hook to add author archive follow button.
 *
 * @since 2.1.0
 */
function bp_follow_author_archive_button() {
	if ( ! is_author() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$author_id = get_queried_object_id();

	if ( ! $author_id ) {
		return;
	}

	// Get enabled post types for this author.
	$author_post_types = get_posts( array(
		'author'         => $author_id,
		'post_type'      => bp_follow_get_enabled_post_types(),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( empty( $author_post_types ) ) {
		return;
	}

	echo '<div class="bp-follow-author-archive-button">';
	bp_follow_author_button( array(
		'author_id'  => $author_id,
		'post_types' => bp_follow_get_enabled_post_types(),
	) );
	echo '</div>';
}
add_action( 'bp_before_author_content', 'bp_follow_author_archive_button', 10 );
