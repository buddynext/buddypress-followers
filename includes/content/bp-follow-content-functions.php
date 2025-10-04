<?php
/**
 * Content Following Helper Functions.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get enabled post types for content following.
 *
 * @since 2.1.0
 *
 * @return array Array of enabled post type names.
 */
function bp_follow_get_enabled_post_types() {
	$default = array( 'post' );
	$settings = bp_get_option( '_bp_follow_post_types', array() );

	if ( empty( $settings ) ) {
		$enabled = $default;
	} else {
		// Extract only enabled post types from settings array.
		$enabled = array();
		foreach ( $settings as $post_type => $config ) {
			if ( ! empty( $config['enabled'] ) ) {
				$enabled[] = $post_type;
			}
		}

		// Fallback to default if none enabled.
		if ( empty( $enabled ) ) {
			$enabled = $default;
		}
	}

	/**
	 * Filter enabled post types for content following.
	 *
	 * @since 2.1.0
	 *
	 * @param array $enabled Array of enabled post type names.
	 */
	return apply_filters( 'bp_follow_enabled_post_types', $enabled );
}

/**
 * Get enabled taxonomies for content following.
 *
 * @since 2.1.0
 *
 * @return array Array of enabled taxonomy names.
 */
function bp_follow_get_enabled_taxonomies() {
	$default = array( 'category', 'post_tag' );
	$settings = bp_get_option( '_bp_follow_taxonomies', array() );

	if ( empty( $settings ) ) {
		$enabled = $default;
	} else {
		// Extract only enabled taxonomies from settings array.
		$enabled = array();
		foreach ( $settings as $taxonomy => $config ) {
			if ( ! empty( $config['enabled'] ) ) {
				$enabled[] = $taxonomy;
			}
		}

		// Fallback to default if none enabled.
		if ( empty( $enabled ) ) {
			$enabled = $default;
		}
	}

	/**
	 * Filter enabled taxonomies for content following.
	 *
	 * @since 2.1.0
	 *
	 * @param array $enabled Array of enabled taxonomy names.
	 */
	return apply_filters( 'bp_follow_enabled_taxonomies', $enabled );
}

/**
 * Check if a post type is enabled for following.
 *
 * @since 2.1.0
 *
 * @param string $post_type Post type name.
 * @return bool True if enabled, false otherwise.
 */
function bp_follow_is_post_type_enabled( $post_type ) {
	$enabled = bp_follow_get_enabled_post_types();
	return in_array( $post_type, $enabled, true );
}

/**
 * Check if a taxonomy is enabled for following.
 *
 * @since 2.1.0
 *
 * @param string $taxonomy Taxonomy name.
 * @return bool True if enabled, false otherwise.
 */
function bp_follow_is_taxonomy_enabled( $taxonomy ) {
	$enabled = bp_follow_get_enabled_taxonomies();
	return in_array( $taxonomy, $enabled, true );
}

/**
 * Get digest mode for a post type.
 *
 * @since 2.1.0
 *
 * @param string $post_type Post type name.
 * @return string Digest mode: 'separate', 'combined', or 'user_choice'.
 */
function bp_follow_get_post_type_digest_mode( $post_type ) {
	$post_types = bp_get_option( '_bp_follow_post_types', array() );

	if ( isset( $post_types[ $post_type ]['digest_mode'] ) ) {
		return $post_types[ $post_type ]['digest_mode'];
	}

	// Default to combined.
	return 'combined';
}

/**
 * Check if separate digest should be sent for a post type.
 *
 * @since 2.1.0
 *
 * @param string $post_type Post type name.
 * @param int    $user_id   User ID (for checking user preference).
 * @return bool True if separate digest, false if combined.
 */
function bp_follow_should_send_separate_digest( $post_type, $user_id = 0 ) {
	$mode = bp_follow_get_post_type_digest_mode( $post_type );

	// If admin set to separate, always separate.
	if ( 'separate' === $mode ) {
		return true;
	}

	// If admin set to combined, always combined.
	if ( 'combined' === $mode ) {
		return false;
	}

	// If user_choice, check user preference.
	if ( 'user_choice' === $mode && $user_id ) {
		global $wpdb, $bp;

		$table_prefix = $bp->table_prefix;
		$prefs_table  = $table_prefix . 'bp_follow_digest_prefs';

  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$user_mode = $wpdb->get_var( $wpdb->prepare(
			"SELECT digest_mode FROM {$prefs_table} WHERE user_id = %d AND post_type = %s",
			$user_id,
			$post_type
		) );

		if ( $user_mode ) {
			return 'separate' === $user_mode;
		}
	}

	// Default to combined.
	return false;
}

/**
 * Get post type label for display.
 *
 * @since 2.1.0
 *
 * @param string $post_type Post type name.
 * @return string Post type label.
 */
function bp_follow_get_post_type_label( $post_type ) {
	$post_types = bp_get_option( '_bp_follow_post_types', array() );

	if ( isset( $post_types[ $post_type ]['label'] ) ) {
		return $post_types[ $post_type ]['label'];
	}

	// Fallback to WordPress post type object.
	$post_type_obj = get_post_type_object( $post_type );

	if ( $post_type_obj ) {
		return $post_type_obj->labels->name;
	}

	return ucfirst( $post_type );
}

/**
 * Get taxonomy label for display.
 *
 * @since 2.1.0
 *
 * @param string $taxonomy Taxonomy name.
 * @return string Taxonomy label.
 */
function bp_follow_get_taxonomy_label( $taxonomy ) {
	$taxonomies = bp_get_option( '_bp_follow_taxonomies', array() );

	if ( isset( $taxonomies[ $taxonomy ]['label'] ) ) {
		return $taxonomies[ $taxonomy ]['label'];
	}

	// Fallback to WordPress taxonomy object.
	$taxonomy_obj = get_taxonomy( $taxonomy );

	if ( $taxonomy_obj ) {
		return $taxonomy_obj->labels->name;
	}

	return ucfirst( $taxonomy );
}

/**
 * Follow an author.
 *
 * @since 2.1.0
 *
 * @param int          $author_id  Author user ID.
 * @param int          $follower_id Follower user ID. Default: current user.
 * @param string|array $post_types Post type(s). Default: 'post'.
 * @return bool True on success, false on failure.
 */
function bp_follow_author( $author_id, $follower_id = 0, $post_types = 'post' ) {
	$service = bp_follow_get_author_service();
	return $service->follow_author( $author_id, $follower_id, $post_types );
}

/**
 * Unfollow an author.
 *
 * @since 2.1.0
 *
 * @param int          $author_id  Author user ID.
 * @param int          $follower_id Follower user ID. Default: current user.
 * @param string|array $post_types Post type(s). Default: 'post'.
 * @return bool True on success, false on failure.
 */
function bp_unfollow_author( $author_id, $follower_id = 0, $post_types = 'post' ) {
	$service = bp_follow_get_author_service();
	return $service->unfollow_author( $author_id, $follower_id, $post_types );
}

/**
 * Check if user is following an author.
 *
 * @since 2.1.0
 *
 * @param int    $author_id  Author user ID.
 * @param int    $follower_id Follower user ID. Default: current user.
 * @param string $post_type   Post type. Default: 'post'.
 * @return bool True if following, false otherwise.
 */
function bp_is_following_author( $author_id, $follower_id = 0, $post_type = 'post' ) {
	$service = bp_follow_get_author_service();
	return $service->is_following_author( $author_id, $follower_id, $post_type );
}

/**
 * Follow a category/taxonomy term.
 *
 * @since 2.1.0
 *
 * @param int    $term_id     Term ID.
 * @param string $taxonomy    Taxonomy name.
 * @param int    $follower_id Follower user ID. Default: current user.
 * @return bool True on success, false on failure.
 */
function bp_follow_term( $term_id, $taxonomy, $follower_id = 0 ) {
	$service = bp_follow_get_category_service();
	return $service->follow_term( $term_id, $taxonomy, $follower_id );
}

/**
 * Unfollow a category/taxonomy term.
 *
 * @since 2.1.0
 *
 * @param int    $term_id     Term ID.
 * @param string $taxonomy    Taxonomy name.
 * @param int    $follower_id Follower user ID. Default: current user.
 * @return bool True on success, false on failure.
 */
function bp_unfollow_term( $term_id, $taxonomy, $follower_id = 0 ) {
	$service = bp_follow_get_category_service();
	return $service->unfollow_term( $term_id, $taxonomy, $follower_id );
}

/**
 * Check if user is following a term.
 *
 * @since 2.1.0
 *
 * @param int    $term_id     Term ID.
 * @param string $taxonomy    Taxonomy name.
 * @param int    $follower_id Follower user ID. Default: current user.
 * @return bool True if following, false otherwise.
 */
function bp_is_following_term( $term_id, $taxonomy, $follower_id = 0 ) {
	$service = bp_follow_get_category_service();
	return $service->is_following_term( $term_id, $taxonomy, $follower_id );
}

/**
 * Get author follow service instance.
 *
 * @since 2.1.0
 *
 * @return \Followers\Service\AuthorFollowService
 */
function bp_follow_get_author_service() {
	static $service = null;

	if ( null === $service ) {
		$service = new \Followers\Service\AuthorFollowService();
	}

	return $service;
}

/**
 * Get category follow service instance.
 *
 * @since 2.1.0
 *
 * @return \Followers\Service\CategoryFollowService
 */
function bp_follow_get_category_service() {
	static $service = null;

	if ( null === $service ) {
		$service = new \Followers\Service\CategoryFollowService();
	}

	return $service;
}

/**
 * Check if a user is following an author.
 *
 * @since 2.1.0
 *
 * @param int    $follower_id User ID of the follower.
 * @param int    $author_id   User ID of the author being followed.
 * @param string $post_type   Post type. Default: 'post'.
 * @return bool True if following, false otherwise.
 */
function bp_follow_is_following_author( $follower_id, $author_id, $post_type = 'post' ) {
	$service = bp_follow_get_author_service();
	return $service->is_following_author( $author_id, $follower_id, $post_type );
}

/**
 * Check if a user is following a term.
 *
 * @since 2.1.0
 *
 * @param int    $follower_id User ID of the follower.
 * @param int    $term_id     Term ID.
 * @param string $taxonomy    Taxonomy name.
 * @return bool True if following, false otherwise.
 */
function bp_follow_is_following_term( $follower_id, $term_id, $taxonomy ) {
	$service = bp_follow_get_category_service();
	return $service->is_following_term( $term_id, $taxonomy, $follower_id );
}

/**
 * Get the number of followers for an author.
 *
 * @since 2.1.0
 *
 * @param int    $author_id User ID of the author.
 * @param string $post_type Post type. Default: 'post'.
 * @return int Number of followers.
 */
function bp_follow_get_author_followers_count( $author_id, $post_type = 'post' ) {
	$service = bp_follow_get_author_service();
	$followers = $service->get_author_followers( $author_id, $post_type );
	return is_array( $followers ) ? count( $followers ) : 0;
}

/**
 * Get the number of followers for a term.
 *
 * @since 2.1.0
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return int Number of followers.
 */
function bp_follow_get_term_followers_count( $term_id, $taxonomy ) {
	$service = bp_follow_get_category_service();
	$followers = $service->get_term_followers( $term_id, $taxonomy );
	return is_array( $followers ) ? count( $followers ) : 0;
}
