<?php
/**
 * Content Follow Shortcodes.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shortcode: Display followed authors feed with sidebar filter.
 *
 * Usage: [bp_followed_authors_feed user_id="123" posts_per_page="10"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML.
 */
function bp_follow_authors_feed_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'user_id'        => get_current_user_id(),
			'posts_per_page' => 10,
			'post_type'      => 'post',
		),
		$atts,
		'bp_followed_authors_feed'
	);

	// Ensure user is logged in or valid user_id provided.
	$user_id = absint( $atts['user_id'] );
	if ( ! $user_id ) {
		return '<p>' . esc_html__( 'Please log in to view your personalized feed.', 'buddypress-followers' ) . '</p>';
	}

	// Start output buffering.
	ob_start();

	// Load template.
	bp_get_template_part( 'shortcodes/followed-authors-feed', null, array(
		'user_id'        => $user_id,
		'posts_per_page' => absint( $atts['posts_per_page'] ),
		'post_type'      => sanitize_key( $atts['post_type'] ),
	) );

	return ob_get_clean();
}
add_shortcode( 'bp_followed_authors_feed', 'bp_follow_authors_feed_shortcode' );

/**
 * Shortcode: Display followed categories feed with sidebar filter.
 *
 * Usage: [bp_followed_categories_feed user_id="123" posts_per_page="10"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML.
 */
function bp_follow_categories_feed_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'user_id'        => get_current_user_id(),
			'posts_per_page' => 10,
			'taxonomy'       => 'category',
		),
		$atts,
		'bp_followed_categories_feed'
	);

	// Ensure user is logged in or valid user_id provided.
	$user_id = absint( $atts['user_id'] );
	if ( ! $user_id ) {
		return '<p>' . esc_html__( 'Please log in to view your personalized feed.', 'buddypress-followers' ) . '</p>';
	}

	// Start output buffering.
	ob_start();

	// Load template.
	bp_get_template_part( 'shortcodes/followed-categories-feed', null, array(
		'user_id'        => $user_id,
		'posts_per_page' => absint( $atts['posts_per_page'] ),
		'taxonomy'       => sanitize_key( $atts['taxonomy'] ),
	) );

	return ob_get_clean();
}
add_shortcode( 'bp_followed_categories_feed', 'bp_follow_categories_feed_shortcode' );

/**
 * AJAX handler: Get filtered posts for authors feed.
 */
function bp_follow_ajax_get_author_posts() {
	check_ajax_referer( 'bp_follow_feed_nonce', 'nonce' );

	$user_id    = absint( $_POST['user_id'] ?? 0 );
	$author_ids = isset( $_POST['author_ids'] ) ? array_map( 'absint', (array) $_POST['author_ids'] ) : array();
	$paged      = absint( $_POST['paged'] ?? 1 );
	$per_page   = absint( $_POST['per_page'] ?? 10 );
	$post_type  = sanitize_key( $_POST['post_type'] ?? 'post' );

	if ( ! $user_id || empty( $author_ids ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'buddypress-followers' ) ) );
	}

	// Query posts.
	$query = new WP_Query( array(
		'post_type'      => $post_type,
		'author__in'     => $author_ids,
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			// Include the template file directly for AJAX contexts
			$template_path = BP_FOLLOW_DIR . '/templates/shortcodes/parts/post-item.php';
			if ( file_exists( $template_path ) ) {
				include $template_path;
			} else {
				// Try using bp_get_template_part as fallback
				bp_get_template_part( 'shortcodes/parts/post-item' );
			}
		}
		wp_reset_postdata();
	} else {
		echo '<p class="bp-follow-no-posts">' . esc_html__( 'No posts found.', 'buddypress-followers' ) . '</p>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array(
		'html'       => $html,
		'found'      => $query->found_posts,
		'max_pages'  => $query->max_num_pages,
		'paged'      => $paged,
	) );
}
add_action( 'wp_ajax_bp_follow_get_author_posts', 'bp_follow_ajax_get_author_posts' );

/**
 * AJAX handler: Get filtered posts for categories feed.
 */
function bp_follow_ajax_get_category_posts() {
	check_ajax_referer( 'bp_follow_feed_nonce', 'nonce' );

	$user_id  = absint( $_POST['user_id'] ?? 0 );
	$term_ids = isset( $_POST['term_ids'] ) ? array_map( 'absint', (array) $_POST['term_ids'] ) : array();
	$taxonomy = sanitize_key( $_POST['taxonomy'] ?? 'category' );
	$paged    = absint( $_POST['paged'] ?? 1 );
	$per_page = absint( $_POST['per_page'] ?? 10 );

	if ( ! $user_id || empty( $term_ids ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'buddypress-followers' ) ) );
	}

	// Query posts.
	$query = new WP_Query( array(
		'post_type'      => 'post',
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			),
		),
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			// Include the template file directly for AJAX contexts
			$template_path = BP_FOLLOW_DIR . '/templates/shortcodes/parts/post-item.php';
			if ( file_exists( $template_path ) ) {
				include $template_path;
			} else {
				// Try using bp_get_template_part as fallback
				bp_get_template_part( 'shortcodes/parts/post-item' );
			}
		}
		wp_reset_postdata();
	} else {
		echo '<p class="bp-follow-no-posts">' . esc_html__( 'No posts found.', 'buddypress-followers' ) . '</p>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array(
		'html'       => $html,
		'found'      => $query->found_posts,
		'max_pages'  => $query->max_num_pages,
		'paged'      => $paged,
	) );
}
add_action( 'wp_ajax_bp_follow_get_category_posts', 'bp_follow_ajax_get_category_posts' );

/**
 * AJAX handler: Unfollow author or term from feed.
 */
function bp_follow_ajax_unfollow() {
	check_ajax_referer( 'bp_follow_feed_nonce', 'nonce' );

	$user_id = absint( $_POST['user_id'] ?? 0 );
	$type    = sanitize_key( $_POST['type'] ?? '' );

	if ( ! $user_id || $user_id !== get_current_user_id() ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddypress-followers' ) ) );
	}

	if ( $type === 'author' ) {
		$author_id = absint( $_POST['author_id'] ?? 0 );
		$post_type = sanitize_key( $_POST['post_type'] ?? 'post' );

		if ( ! $author_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid author ID.', 'buddypress-followers' ) ) );
		}

		$service = bp_follow_get_author_service();
		$result  = $service->unfollow_author( $author_id, $user_id, $post_type );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Author unfollowed successfully.', 'buddypress-followers' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to unfollow author.', 'buddypress-followers' ) ) );
		}
	} elseif ( $type === 'term' ) {
		$term_id  = absint( $_POST['term_id'] ?? 0 );
		$taxonomy = sanitize_key( $_POST['taxonomy'] ?? 'category' );

		if ( ! $term_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid term ID.', 'buddypress-followers' ) ) );
		}

		$service = bp_follow_get_category_service();
		$result  = $service->unfollow_term( $term_id, $taxonomy, $user_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Category unfollowed successfully.', 'buddypress-followers' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to unfollow category.', 'buddypress-followers' ) ) );
		}
	} else {
		wp_send_json_error( array( 'message' => __( 'Invalid type.', 'buddypress-followers' ) ) );
	}
}
add_action( 'wp_ajax_bp_follow_unfollow_ajax', 'bp_follow_ajax_unfollow' );

/**
 * Enqueue feed assets.
 */
function bp_follow_enqueue_feed_assets() {
	$should_enqueue = false;

	// Check if on member profile authors/categories page
	if ( bp_is_user() ) {
		global $bp;
		$following_slug = isset( $bp->follow->following->slug ) ? $bp->follow->following->slug : 'following';

		if ( bp_is_current_component( $following_slug ) ) {
			$current_action = bp_current_action();
			if ( 'authors' === $current_action || 'categories' === $current_action ) {
				$should_enqueue = true;
			}
		}
	}

	// Check if shortcode is used in post content
	global $post;
	if ( ! $should_enqueue && is_a( $post, 'WP_Post' ) ) {
		if ( has_shortcode( $post->post_content, 'bp_followed_authors_feed' ) ||
		     has_shortcode( $post->post_content, 'bp_followed_categories_feed' ) ) {
			$should_enqueue = true;
		}
	}

	if ( ! $should_enqueue ) {
		return;
	}

	// Enqueue styles.
	wp_enqueue_style(
		'bp-follow-feed',
		BP_FOLLOW_URL . 'assets/css/content-follows-feed.css',
		array( 'dashicons' ),
		'2.1.0'
	);

	// Enqueue scripts.
	wp_enqueue_script(
		'bp-follow-feed',
		BP_FOLLOW_URL . 'assets/js/content-follows-feed.js',
		array( 'jquery' ),
		'2.1.0',
		true
	);

	// Localize script.
	wp_localize_script(
		'bp-follow-feed',
		'bpFollowFeed',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bp_follow_feed_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bp_follow_enqueue_feed_assets' );
