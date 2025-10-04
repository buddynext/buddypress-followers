<?php
/**
 * Content Follows Assets.
 *
 * Handles CSS and JS enqueuing for content follows.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue content follows styles.
 *
 * @since 2.1.0
 */
function bp_follow_enqueue_content_styles() {
	global $bp;

	// Only enqueue on following component with content follows actions.
	$following_slug = isset( $bp->follow->following->slug ) ? $bp->follow->following->slug : 'following';
	if ( ! bp_is_current_component( $following_slug ) ) {
		return;
	}

	$current_action = bp_current_action();
	if ( 'authors' !== $current_action && 'categories' !== $current_action ) {
		return;
	}

	// Enqueue content follows stylesheet.
	wp_enqueue_style(
		'bp-follow-content',
		BP_FOLLOW_URL . 'assets/css/content-follows.css',
		array( 'dashicons' ),
		'2.1.0',
		'all'
	);
}
add_action( 'wp_enqueue_scripts', 'bp_follow_enqueue_content_styles' );
