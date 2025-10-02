<?php
/**
 * Follow service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use BP_Follow;
use function apply_filters;
use function bp_core_current_time;
use function bp_displayed_user_id;
use function bp_follow_get_common_args;
use function bp_loggedin_user_id;
use function do_action_ref_array;
use function wp_cache_get;
use function wp_cache_set;
use function wp_parse_args;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Application-facing follow operations.
 */
class FollowService {
	/**
	 * Begin following an item.
	 *
	 * @param array $args Arguments.
	 * @return bool
	 */
	public function follow( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'leader_id'     => bp_displayed_user_id(),
			'follower_id'   => bp_loggedin_user_id(),
			'follow_type'   => '',
			'date_recorded' => bp_core_current_time(),
		) );

		$follow = new BP_Follow( $r['leader_id'], $r['follower_id'], $r['follow_type'] );

		if ( ! empty( $follow->id ) ) {
			return false;
		}

		$follow->date_recorded = $r['date_recorded'];

		if ( ! $follow->save() ) {
			return false;
		}

		if ( empty( $r['follow_type'] ) ) {
			do_action_ref_array( 'bp_follow_start_following', array( &$follow ) );
		} else {
			do_action_ref_array( 'bp_follow_start_following_' . $r['follow_type'], array( &$follow ) );
		}

		return true;
	}

	/**
	 * Stop following an item.
	 *
	 * @param array $args Arguments.
	 * @return bool
	 */
	public function unfollow( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
			'follow_type' => '',
		) );

		$follow = new BP_Follow( $r['leader_id'], $r['follower_id'], $r['follow_type'] );

		if ( empty( $follow->id ) || ! $follow->delete() ) {
			return false;
		}

		if ( empty( $r['follow_type'] ) ) {
			do_action_ref_array( 'bp_follow_stop_following', array( &$follow ) );
		} else {
			do_action_ref_array( 'bp_follow_stop_following_' . $r['follow_type'], array( &$follow ) );
		}

		return true;
	}

	/**
	 * Determine whether a relationship exists.
	 *
	 * @param array $args Arguments.
	 * @return bool
	 */
	public function is_following( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
			'follow_type' => '',
		) );

		$follow = new BP_Follow( $r['leader_id'], $r['follower_id'], $r['follow_type'] );

		if ( empty( $r['follow_type'] ) ) {
			$retval = apply_filters( 'bp_follow_is_following', (int) $follow->id, $follow );
		} else {
			$retval = apply_filters( 'bp_follow_is_following_' . $r['follow_type'], (int) $follow->id, $follow );
		}

		return (bool) $retval;
	}

	/**
	 * Retrieve follower IDs for a leader.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public function get_followers( $args = array() ) {
		$r = bp_follow_get_common_args( wp_parse_args( $args, array(
			'user_id' => bp_displayed_user_id(),
		) ) );

		$retval   = array();
		$do_query = true;

		$filter = ! empty( $r['follow_type'] ) ? 'bp_follow_get_followers_' . $r['object'] : 'bp_follow_get_followers';

		if ( empty( $r['query_args'] ) ) {
			$retval = wp_cache_get( $r['object_id'], "bp_follow_{$r['object']}_followers_query" );

			if ( false !== $retval ) {
				$do_query = false;
			}
		}

		if ( true === $do_query ) {
			$retval = BP_Follow::get_followers( $r['object_id'], $r['follow_type'], $r['query_args'] );

			if ( empty( $r['query_args'] ) ) {
				wp_cache_set( $r['object_id'], $retval, "bp_follow_{$r['object']}_followers_query" );

				global $wpdb;
				wp_cache_set( $r['object_id'], $wpdb->num_rows, "bp_follow_{$r['object']}_followers_count" );
			}
		}

		return apply_filters( $filter, $retval );
	}

	/**
	 * Retrieve IDs the user is following.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public function get_following( $args = array() ) {
		$r = bp_follow_get_common_args( wp_parse_args( $args, array(
			'user_id' => bp_displayed_user_id(),
		) ) );

		$retval   = array();
		$do_query = true;

		$filter = ! empty( $r['follow_type'] ) ? 'bp_follow_get_following_' . $r['object'] : 'bp_follow_get_following';

		if ( empty( $r['query_args'] ) ) {
			$retval = wp_cache_get( $r['object_id'], "bp_follow_{$r['object']}_following_query" );

			if ( false !== $retval ) {
				$do_query = false;
			}
		}

		if ( true === $do_query ) {
			$retval = BP_Follow::get_following( $r['object_id'], $r['follow_type'], $r['query_args'] );

			if ( empty( $r['query_args'] ) ) {
				wp_cache_set( $r['object_id'], $retval, "bp_follow_{$r['object']}_following_query" );

				global $wpdb;
				wp_cache_set( $r['object_id'], $wpdb->num_rows, "bp_follow_{$r['object']}_following_count" );
			}
		}

		return apply_filters( $filter, $retval );
	}

	/**
	 * Retrieve aggregate counts for a user.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public function get_counts( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'user_id'     => bp_loggedin_user_id(),
			'follow_type' => '',
		) );

		$retval = array();

		$retval['following'] = $this->get_following_count( array(
			'user_id'     => $r['user_id'],
			'object_id'   => $r['user_id'],
			'follow_type' => $r['follow_type'],
		) );

		if ( ! empty( $r['follow_type'] ) ) {
			$retval['followers'] = 0;
		} else {
			$retval['followers'] = $this->get_followers_count( array(
				'user_id'     => $r['user_id'],
				'object_id'   => $r['user_id'],
				'follow_type' => $r['follow_type'],
			) );
		}

		if ( empty( $r['follow_type'] ) ) {
			$retval = apply_filters( 'bp_follow_total_follow_counts', $retval, $r['user_id'] );
		} else {
			$retval = apply_filters( 'bp_follow_total_follow_' . $r['follow_type'] . '_counts', $retval, $r['user_id'] );
		}

		return $retval;
	}

	/**
	 * Retrieve the following count for an object.
	 *
	 * @param array $args Arguments.
	 * @return int
	 */
	public function get_following_count( $args = array() ) {
		$r = bp_follow_get_common_args( $args );

		$cache_key = "bp_follow_{$r['object']}_following_count";
		$retval    = wp_cache_get( $r['object_id'], $cache_key );

		if ( false === $retval ) {
			$retval = BP_Follow::get_following_count( $r['object_id'], $r['follow_type'] );
			wp_cache_set( $r['object_id'], $retval, $cache_key );
		}

		return (int) apply_filters( "bp_follow_get_{$r['object']}_following_count", $retval, $r['object_id'] );
	}

	/**
	 * Retrieve the followers count for an object.
	 *
	 * @param array $args Arguments.
	 * @return int
	 */
	public function get_followers_count( $args = array() ) {
		$r = bp_follow_get_common_args( $args );

		$cache_key = "bp_follow_{$r['object']}_followers_count";
		$retval    = wp_cache_get( $r['object_id'], $cache_key );

		if ( false === $retval ) {
			$retval = BP_Follow::get_followers_count( $r['object_id'], $r['follow_type'] );
			wp_cache_set( $r['object_id'], $retval, $cache_key );
		}

		return (int) apply_filters( "bp_follow_get_{$r['object']}_followers_count", $retval, $r['object_id'] );
	}
}
