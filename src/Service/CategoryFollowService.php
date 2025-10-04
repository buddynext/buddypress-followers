<?php
/**
 * Category Follow Service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use BP_Follow;
use function apply_filters;
use function bp_core_current_time;
use function bp_loggedin_user_id;
use function do_action;
use function get_term;
use function taxonomy_exists;
use function wp_cache_delete;
use function wp_cache_get;
use function wp_cache_set;
use function wp_parse_args;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service for following categories and taxonomies.
 *
 * @since 2.1.0
 */
class CategoryFollowService {

	/**
	 * Follow a category/taxonomy term.
	 *
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param int    $follower_id Follower user ID.
	 * @return bool True on success, false on failure.
	 */
	public function follow_term( $term_id, $taxonomy, $follower_id = 0 ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $term_id ) {
			return false;
		}

		// Validate taxonomy exists.
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		// Check if taxonomy is enabled.
		$enabled_taxonomies = bp_follow_get_enabled_taxonomies();
		if ( ! in_array( $taxonomy, $enabled_taxonomies, true ) ) {
			return false;
		}

		// Validate term exists.
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		$follow_type = $taxonomy;

		// Check if already following.
		$follow = new BP_Follow( $term_id, $follower_id, $follow_type );
		if ( ! empty( $follow->id ) ) {
			return false;
		}

		// Create follow relationship.
		$follow                = new BP_Follow();
		$follow->leader_id     = $term_id;
		$follow->follower_id   = $follower_id;
		$follow->follow_type   = $follow_type;
		$follow->date_recorded = bp_core_current_time();

		if ( ! $follow->save() ) {
			return false;
		}

		// Clear cache.
		$this->clear_cache( $term_id, $follower_id, $taxonomy );

		// Update count cache.
		$this->update_follow_counts( $term_id, $taxonomy );

		// Fire action.
		do_action( 'bp_follow_term_followed', $term_id, $taxonomy, $follower_id, $follow->id );

		return true;
	}

	/**
	 * Unfollow a category/taxonomy term.
	 *
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param int    $follower_id Follower user ID.
	 * @return bool True on success, false on failure.
	 */
	public function unfollow_term( $term_id, $taxonomy, $follower_id = 0 ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $term_id ) {
			return false;
		}

		$follow_type = $taxonomy;

		// Find existing follow.
		$follow = new BP_Follow( $term_id, $follower_id, $follow_type );

		if ( empty( $follow->id ) || ! $follow->delete() ) {
			return false;
		}

		// Clear cache.
		$this->clear_cache( $term_id, $follower_id, $taxonomy );

		// Update count cache.
		$this->update_follow_counts( $term_id, $taxonomy );

		// Fire action.
		do_action( 'bp_follow_term_unfollowed', $term_id, $taxonomy, $follower_id );

		return true;
	}

	/**
	 * Check if a user is following a term.
	 *
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param int    $follower_id Follower user ID.
	 * @return bool True if following, false otherwise.
	 */
	public function is_following_term( $term_id, $taxonomy, $follower_id = 0 ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id || ! $term_id ) {
			return false;
		}

		$cache_key = "is_following_term_{$term_id}_{$taxonomy}_{$follower_id}";
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$follow_type = $taxonomy;
		$follow      = new BP_Follow( $term_id, $follower_id, $follow_type );
		$is_following = ! empty( $follow->id );

		wp_cache_set( $cache_key, $is_following, 'bp_follow_data', 3600 );

		return $is_following;
	}

	/**
	 * Get all terms followed by a user for a taxonomy.
	 *
	 * @param int    $follower_id Follower user ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param array  $args        Additional query arguments.
	 * @return array Array of term IDs.
	 */
	public function get_followed_terms( $follower_id = 0, $taxonomy = 'category', $args = array() ) {
		if ( ! $follower_id ) {
			$follower_id = bp_loggedin_user_id();
		}

		if ( ! $follower_id ) {
			return array();
		}

		// Ensure taxonomy is a string for cache key.
		if ( is_array( $taxonomy ) ) {
			// Filter out empty values and reindex.
			$taxonomy = array_values( array_filter( $taxonomy ) );

			if ( empty( $taxonomy ) ) {
				return array(); // No taxonomies specified
			}
			$taxonomy_string = implode( '_', $taxonomy );
			$taxonomy_for_query = $taxonomy[0];
		} else {
			// Validate single taxonomy is not empty.
			if ( empty( $taxonomy ) ) {
				return array();
			}
			$taxonomy_string = $taxonomy;
			$taxonomy_for_query = $taxonomy;
		}

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
		);

		$r = wp_parse_args( $args, $defaults );

		$cache_key = "followed_terms_{$follower_id}_{$taxonomy_string}_" . md5( serialize( $r ) );
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Use taxonomy for query.
		$follow_type = $taxonomy_for_query;

		$term_ids = BP_Follow::get_following( $follower_id, $follow_type, array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		wp_cache_set( $cache_key, $term_ids, 'bp_follow_data', 3600 );

		return $term_ids;
	}

	/**
	 * Get all followers of a term.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $args     Additional query arguments.
	 * @return array Array of follower IDs.
	 */
	public function get_term_followers( $term_id, $taxonomy = 'category', $args = array() ) {
		if ( ! $term_id ) {
			return array();
		}

		// Ensure taxonomy is a string for cache key.
		if ( is_array( $taxonomy ) ) {
			// Filter out empty values and reindex.
			$taxonomy = array_values( array_filter( $taxonomy ) );

			if ( empty( $taxonomy ) ) {
				return array(); // No taxonomies specified
			}
			$taxonomy_string = implode( '_', $taxonomy );
			$taxonomy_for_query = $taxonomy[0];
		} else {
			// Validate single taxonomy is not empty.
			if ( empty( $taxonomy ) ) {
				return array();
			}
			$taxonomy_string = $taxonomy;
			$taxonomy_for_query = $taxonomy;
		}

		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
		);

		$r = wp_parse_args( $args, $defaults );

		$cache_key = "term_followers_{$term_id}_{$taxonomy_string}_" . md5( serialize( $r ) );
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Use taxonomy for query.
		$follow_type = $taxonomy_for_query;

		$follower_ids = BP_Follow::get_followers( $term_id, $follow_type, array(
			'page'     => $r['page'],
			'per_page' => $r['per_page'],
		) );

		wp_cache_set( $cache_key, $follower_ids, 'bp_follow_data', 3600 );

		return $follower_ids;
	}

	/**
	 * Get follower count for a term.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return int Follower count.
	 */
	public function get_follower_count( $term_id, $taxonomy = 'category' ) {
		global $wpdb, $bp;

		$cache_key = "term_follower_count_{$term_id}_{$taxonomy}";
		$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		// Try to get from counts table first.
		$table_prefix = $bp->table_prefix;
		$counts_table = $table_prefix . 'bp_follow_counts';

		$object_type = 'term:' . $taxonomy;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT follower_count FROM {$counts_table} WHERE object_id = %d AND object_type = %s",
			$term_id,
			$object_type
		) );

		if ( null === $count ) {
			// Calculate if not in cache table.
			$follow_type = $taxonomy;
			$count       = BP_Follow::get_counts( array(
				'object_id'   => $term_id,
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
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	protected function update_follow_counts( $term_id, $taxonomy ) {
		global $wpdb, $bp;

		$table_prefix = $bp->table_prefix;
		$counts_table = $table_prefix . 'bp_follow_counts';

		$follow_type = $taxonomy;
		$object_type = 'term:' . $taxonomy;

		$count = BP_Follow::get_counts( array(
			'object_id'   => $term_id,
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
			$term_id,
			$object_type,
			$count,
			$current_time
		) );

		// Clear cache.
		wp_cache_delete( "term_follower_count_{$term_id}_{$taxonomy}", 'bp_follow_data' );
	}

	/**
	 * Clear follow cache.
	 *
	 * @param int    $term_id     Term ID.
	 * @param int    $follower_id Follower user ID.
	 * @param string $taxonomy    Taxonomy name.
	 */
	protected function clear_cache( $term_id, $follower_id, $taxonomy ) {
		wp_cache_delete( "is_following_term_{$term_id}_{$taxonomy}_{$follower_id}", 'bp_follow_data' );
		wp_cache_delete( "followed_terms_{$follower_id}_{$taxonomy}", 'bp_follow_data' );
		wp_cache_delete( "term_followers_{$term_id}_{$taxonomy}", 'bp_follow_data' );
		wp_cache_delete( "term_follower_count_{$term_id}_{$taxonomy}", 'bp_follow_data' );
	}
}
