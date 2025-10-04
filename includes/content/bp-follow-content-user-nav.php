<?php
/**
 * Content Following User Navigation.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add "Followed Authors" and "Followed Categories" as sub-tabs under Following.
 *
 * @since 2.1.0
 */
function bp_follow_content_setup_profile_nav() {
	global $bp;

	$displayed_user_id = bp_displayed_user_id();

	// Get counts for badge display.
	$author_count = bp_follow_get_followed_authors_count( $displayed_user_id );
	$category_count = bp_follow_get_followed_categories_count( $displayed_user_id );

	// Add "Followed Authors" as sub-nav under Following.
	bp_core_new_subnav_item(
		array(
			'name'            => sprintf(
				/* translators: %s: followed authors count */
				__( 'Authors %s', 'buddypress-followers' ),
				'<span class="count">' . number_format_i18n( $author_count ) . '</span>'
			),
			'slug'            => 'authors',
			'parent_url'      => bp_follow_get_user_url( $displayed_user_id, array( $bp->follow->following->slug ) ),
			'parent_slug'     => $bp->follow->following->slug,
			'screen_function' => 'bp_follow_content_screen_followed_authors',
			'position'        => 20,
		)
	);

	// Add "Followed Categories & Tags" as sub-nav under Following.
	bp_core_new_subnav_item(
		array(
			'name'            => sprintf(
				/* translators: %s: followed categories count */
				__( 'Categories & Tags %s', 'buddypress-followers' ),
				'<span class="count">' . number_format_i18n( $category_count ) . '</span>'
			),
			'slug'            => 'categories',
			'parent_url'      => bp_follow_get_user_url( $displayed_user_id, array( $bp->follow->following->slug ) ),
			'parent_slug'     => $bp->follow->following->slug,
			'screen_function' => 'bp_follow_content_screen_followed_categories',
			'position'        => 30,
		)
	);
}
add_action( 'bp_setup_nav', 'bp_follow_content_setup_profile_nav', 100 );

/**
 * Screen function for followed authors.
 *
 * @since 2.1.0
 */
function bp_follow_content_screen_followed_authors() {
	do_action( 'bp_follow_content_screen_followed_authors' );

	// Register our template directory.
	bp_register_template_stack( 'bp_follow_get_template_directory', 14 );

	bp_core_load_template( apply_filters( 'bp_follow_content_template_followed_authors', 'members/single/follow' ) );
}

/**
 * Screen function for followed categories.
 *
 * @since 2.1.0
 */
function bp_follow_content_screen_followed_categories() {
	do_action( 'bp_follow_content_screen_followed_categories' );

	// Register our template directory.
	bp_register_template_stack( 'bp_follow_get_template_directory', 14 );

	bp_core_load_template( apply_filters( 'bp_follow_content_template_followed_categories', 'members/single/follow' ) );
}

/**
 * Get followed authors count for a user.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID.
 * @return int Count of followed authors.
 */
function bp_follow_get_followed_authors_count( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	$cache_key = "followed_authors_count_{$user_id}";
	$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$count = 0;
	$service = bp_follow_get_author_service();
	$enabled_post_types = bp_follow_get_enabled_post_types();

	if ( empty( $enabled_post_types ) ) {
		wp_cache_set( $cache_key, 0, 'bp_follow_data', 3600 );
		return 0;
	}

	foreach ( $enabled_post_types as $post_type ) {
		$author_ids = $service->get_followed_authors( $user_id, $post_type, array( 'per_page' => 1000 ) );
		if ( is_array( $author_ids ) ) {
			$count += count( $author_ids );
		}
	}

	wp_cache_set( $cache_key, $count, 'bp_follow_data', 3600 );

	return $count;
}

/**
 * Get followed categories count for a user.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID.
 * @return int Count of followed categories and tags.
 */
function bp_follow_get_followed_categories_count( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	$cache_key = "followed_categories_count_{$user_id}";
	$cached    = wp_cache_get( $cache_key, 'bp_follow_data' );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$count = 0;
	$service = bp_follow_get_category_service();
	$enabled_taxonomies = bp_follow_get_enabled_taxonomies();

	if ( ! empty( $enabled_taxonomies ) ) {
		foreach ( $enabled_taxonomies as $taxonomy ) {
			$term_ids = $service->get_followed_terms( $user_id, $taxonomy, array( 'per_page' => 1000 ) );
			if ( is_array( $term_ids ) ) {
				$count += count( $term_ids );
			}
		}
	}

	wp_cache_set( $cache_key, $count, 'bp_follow_data', 3600 );

	return $count;
}

