<?php
/**
 * Author Follow Service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use BP_Follow;
use WP_User;
use function apply_filters;
use function bp_core_current_time;
use function bp_loggedin_user_id;
use function do_action;
use function get_userdata;
use function wp_cache_delete;
use function wp_cache_get;
use function wp_cache_set;
use function wp_parse_args;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service for following authors and their content.
 *
 * @since 2.1.0
 */
class AuthorFollowService {

	/**
	 * Follow an author for a specific post type.
	 *
	 * @param int          $author_id  Author user ID.
	 * @param int          $follower_id Follower user ID.
	 * @param string|array $post_types Post type(s) to follow. Default 'post'.
	 * @return bool True on success, false on failure.
	 */
	public function follow_author( $author_id, $follower_id = 0, $post_types = 'post' ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $author_id ) {
			return false;
		}

		// Validate author exists.
		$author = get_userdata( $author_id );
		if ( ! $author ) {
			return false;
		}

		// Convert to array for processing.
		$post_types = (array) $post_types;

		// Check if enabled post types.
		$enabled_post_types = bp_follow_get_enabled_post_types();
		$post_types         = array_intersect( $post_types, $enabled_post_types );

		if ( empty( $post_types ) ) {
			return false;
		}

		$success = true;

		foreach ( $post_types as $post_type ) {
			$follow_type = 'authors:' . $post_type;

			// Check if already following.
			$follow = new BP_Follow( $author_id, $follower_id, $follow_type );
			if ( ! empty( $follow->id ) ) {
				continue;
			}

			// Create follow relationship.
			$follow                = new BP_Follow();
			$follow->leader_id     = $author_id;
			$follow->follower_id   = $follower_id;
			$follow->follow_type   = $follow_type;
			$follow->date_recorded = bp_core_current_time();

			if ( ! $follow->save() ) {
				$success = false;
				continue;
			}

			// Clear cache.
			$this->clear_cache( $author_id, $follower_id, $post_type );

			// Fire action.
			do_action( 'bp_follow_author_followed', $author_id, $follower_id, $post_type, $follow->id );
		}

		// Update count cache.
		$this->update_follow_counts( $author_id, $post_types );

		return $success;
	}

	/**
	 * Unfollow an author for a specific post type.
	 *
	 * @param int          $author_id  Author user ID.
	 * @param int          $follower_id Follower user ID.
	 * @param string|array $post_types Post type(s) to unfollow. Default 'post'.
	 * @return bool True on success, false on failure.
	 */
	public function unfollow_author( $author_id, $follower_id = 0, $post_types = 'post' ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $author_id ) {
			return false;
		}

		// Convert to array for processing.
		$post_types = (array) $post_types;

		$success = true;

		foreach ( $post_types as $post_type ) {
			$follow_type = 'authors:' . $post_type;

			// Find existing follow.
			$follow = new BP_Follow( $author_id, $follower_id, $follow_type );

			if ( empty( $follow->id ) || ! $follow->delete() ) {
				$success = false;
				continue;
			}

			// Clear cache.
			$this->clear_cache( $author_id, $follower_id, $post_type );

			// Fire action.
			do_action( 'bp_follow_author_unfollowed', $author_id, $follower_id, $post_type );
		}

		// Update count cache.
		$this->update_follow_counts( $author_id, $post_types );

		return $success;
	}

	/**
	 * Check if a user is following an author for a post type.
	 *
	 * @param int    $author_id  Author user ID.
	 * @param int    $follower_id Follower user ID.
	 * @param string $post_type   Post type. Default 'post'.
	 * @return bool True if following, false otherwise.
	 */
	public function is_following_author( $author_id, $follower_id = 0, $post_type = 'post' ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $author_id ) {
			return false;
		}

		$cache_key = "is_following_author_{$author_id}_{$follower_id}_{$post_type}";
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$follow_type = 'authors:' . $post_type;
		$follow      = new BP_Follow( $author_id, $follower_id, $follow_type );
		$is_following = ! empty( $follow->id );

		wp_cache_set( $cache_key, $is_following, 'bp_follow_data', 3600 );

		return $is_following;
	}

	/**
	 * Get all authors followed by a user for a post type.
	 *
	 * @param int    $follower_id Follower user ID.
	 * @param string $post_type   Post type. Default 'post'.
	 * @param array  $args        Additional query arguments.
	 * @return array Array of author IDs.
	 */
	public function get_followed_authors( $follower_id = 0, $post_type = 'post', $args = array() ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id ) {
			return array();
		}

		// Ensure post_type is a string for cache key.
		if ( is_array( $post_type ) ) {
			// Filter out empty values and reindex.
			$post_type = array_values( array_filter( $post_type ) );

			if ( empty( $post_type ) ) {
				return array(); // No post types specified
			}
			$post_type_string = implode( '_', $post_type );
			$post_type_for_query = $post_type[0];
		} else {
			// Validate single post_type is not empty.
			if ( empty( $post_type ) ) {
				return array();
			}
			$post_type_string = $post_type;
			$post_type_for_query = $post_type;
		}

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
		);

		$r = wp_parse_args( $args, $defaults );

		$cache_key = "followed_authors_{$follower_id}_{$post_type_string}_" . md5( serialize( $r ) );
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Use post_type for query.
		$follow_type = 'authors:' . $post_type_for_query;

		$author_ids = BP_Follow::get_following( $follower_id, $follow_type, array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		wp_cache_set( $cache_key, $author_ids, 'bp_follow_data', 3600 );

		return $author_ids;
	}

	/**
	 * Get all followers of an author for a post type.
	 *
	 * @param int    $author_id Author user ID.
	 * @param string $post_type Post type. Default 'post'.
	 * @param array  $args      Additional query arguments.
	 * @return array Array of follower IDs.
	 */
	public function get_author_followers( $author_id, $post_type = 'post', $args = array() ) {
		if ( ! $author_id ) {
			return array();
		}

		// Ensure post_type is a string for cache key.
		if ( is_array( $post_type ) ) {
			// Filter out empty values and reindex.
			$post_type = array_values( array_filter( $post_type ) );

			if ( empty( $post_type ) ) {
				return array(); // No post types specified
			}
			$post_type_string = implode( '_', $post_type );
			$post_type_for_query = $post_type[0];
		} else {
			// Validate single post_type is not empty.
			if ( empty( $post_type ) ) {
				return array();
			}
			$post_type_string = $post_type;
			$post_type_for_query = $post_type;
		}

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
		);

		$r = wp_parse_args( $args, $defaults );

		$cache_key = "author_followers_{$author_id}_{$post_type_string}_" . md5( serialize( $r ) );
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Use post_type for query.
		$follow_type = 'authors:' . $post_type_for_query;

		$follower_ids = BP_Follow::get_followers( $author_id, $follow_type, array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		wp_cache_set( $cache_key, $follower_ids, 'bp_follow_data', 3600 );

		return $follower_ids;
	}

	/**
	 * Get follower count for an author and post type.
	 *
	 * @param int    $author_id Author user ID.
	 * @param string $post_type Post type. Default 'post'.
	 * @return int Follower count.
	 */
	public function get_follower_count( $author_id, $post_type = 'post' ) {
		global $wpdb, $bp;

		$cache_key = "author_follower_count_{$author_id}_{$post_type}";
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		// Try to get from counts table first.
		$table_prefix = $bp->table_prefix;
		$counts_table = $table_prefix . 'bp_follow_counts';

		$object_type = 'author:' . $post_type;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT follower_count FROM {$counts_table} WHERE object_id = %d AND object_type = %s",
			$author_id,
			$object_type
		) );

		if ( null === $count ) {
			// Calculate if not in cache table.
			$follow_type = 'authors:' . $post_type;
			$count       = BP_Follow::get_counts( array(
				'object_id'   => $author_id,
				'follow_type' => $follow_type,
				'count_type'  => 'followers',
			) );
		}

		$count = (int) $count;

		wp_cache_set( $cache_key, $count, 'bp_follow_data', 3600 );

		return $count;
	}

	/**
	 * Update follow counts in cache table.
	 *
	 * @param int   $author_id  Author user ID.
	 * @param array $post_types Post types to update.
	 */
	protected function update_follow_counts( $author_id, $post_types ) {
		global $wpdb, $bp;

		$table_prefix = $bp->table_prefix;
		$counts_table = $table_prefix . 'bp_follow_counts';

		foreach ( $post_types as $post_type ) {
			$follow_type = 'authors:' . $post_type;
			$object_type = 'author:' . $post_type;

			$count = BP_Follow::get_counts( array(
				'object_id'   => $author_id,
				'follow_type' => $follow_type,
				'count_type'  => 'followers',
			) );

			$current_time = current_time( 'mysql' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare(
				"INSERT INTO {$counts_table}
				(object_id, object_type, follower_count, last_updated)
				VALUES (%d, %s, %d, %s)
				ON DUPLICATE KEY UPDATE
				follower_count = VALUES(follower_count),
				last_updated = VALUES(last_updated)",
				$author_id,
				$object_type,
				$count,
				$current_time
			) );

			// Clear cache.
			wp_cache_delete( "author_follower_count_{$author_id}_{$post_type}", 'bp_follow_data' );
		}
	}

	/**
	 * Clear follow cache.
	 *
	 * @param int    $author_id   Author user ID.
	 * @param int    $follower_id Follower user ID.
	 * @param string $post_type   Post type.
	 */
	protected function clear_cache( $author_id, $follower_id, $post_type ) {
		wp_cache_delete( "is_following_author_{$author_id}_{$follower_id}_{$post_type}", 'bp_follow_data' );
		wp_cache_delete( "followed_authors_{$follower_id}_{$post_type}", 'bp_follow_data' );
		wp_cache_delete( "author_followers_{$author_id}_{$post_type}", 'bp_follow_data' );
		wp_cache_delete( "author_follower_count_{$author_id}_{$post_type}", 'bp_follow_data' );
	}
}
