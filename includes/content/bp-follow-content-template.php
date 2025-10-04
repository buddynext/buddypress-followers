<?php
/**
 * Content Following Template Functions.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output follow button for an author.
 *
 * @since 2.1.0
 *
 * @param array $args Button arguments.
 */
function bp_follow_author_button( $args = array() ) {
	echo bp_follow_get_author_button( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get follow button for an author.
 *
 * @since 2.1.0
 *
 * @param array $args Button arguments.
 * @return string Button HTML.
 */
function bp_follow_get_author_button( $args = array() ) {
	$defaults = array(
		'author_id'   => get_queried_object_id(),
		'post_types'  => array( 'post' ),
		'follower_id' => bp_loggedin_user_id(),
		'wrapper'     => 'div',
		'link_class'  => 'bp-follow-author-button',
		'link_text'   => __( 'Follow', 'buddypress-followers' ),
		'unlink_text' => __( 'Unfollow', 'buddypress-followers' ),
	);

	$r = wp_parse_args( $args, $defaults );

	if ( ! $r['author_id'] || ! $r['follower_id'] ) {
		return '';
	}

	// Check if user is already following.
	$post_type    = is_array( $r['post_types'] ) ? reset( $r['post_types'] ) : $r['post_types'];
	$is_following = bp_is_following_author( $r['author_id'], $r['follower_id'], $post_type );

	$button_text = $is_following ? $r['unlink_text'] : $r['link_text'];
	$action      = $is_following ? 'unfollow' : 'follow';

	$button_args = array(
		'id'                => 'author_' . $r['author_id'],
		'component'         => 'follow',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'wrapper_class'     => 'follow-author-button',
		'wrapper_id'        => 'follow-author-button-' . $r['author_id'],
		'link_href'         => wp_nonce_url(
			add_query_arg( array(
				'action'     => $action,
				'author_id'  => $r['author_id'],
				'post_types' => implode( ',', (array) $r['post_types'] ),
			) ),
			'bp_follow_author_' . $action
		),
		'link_text'         => $button_text,
		'link_class'        => $r['link_class'] . ' ' . $action,
	);

	/**
	 * Filter the author follow button args.
	 *
	 * @since 2.1.0
	 *
	 * @param array $button_args Button arguments.
	 * @param array $r           Original arguments.
	 */
	$button_args = apply_filters( 'bp_follow_get_author_button_args', $button_args, $r );

	return bp_get_button( $button_args );
}

/**
 * Output follow button for a taxonomy term.
 *
 * @since 2.1.0
 *
 * @param array $args Button arguments.
 */
function bp_follow_term_button( $args = array() ) {
	echo bp_follow_get_term_button( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get follow button for a taxonomy term.
 *
 * @since 2.1.0
 *
 * @param array $args Button arguments.
 * @return string Button HTML.
 */
function bp_follow_get_term_button( $args = array() ) {
	$defaults = array(
		'term_id'     => get_queried_object_id(),
		'taxonomy'    => 'category',
		'follower_id' => bp_loggedin_user_id(),
		'wrapper'     => 'div',
		'link_class'  => 'bp-follow-term-button',
		'link_text'   => __( 'Follow', 'buddypress-followers' ),
		'unlink_text' => __( 'Unfollow', 'buddypress-followers' ),
	);

	$r = wp_parse_args( $args, $defaults );

	if ( ! $r['term_id'] || ! $r['follower_id'] ) {
		return '';
	}

	// Check if user is already following.
	$is_following = bp_is_following_term( $r['term_id'], $r['taxonomy'], $r['follower_id'] );

	$button_text = $is_following ? $r['unlink_text'] : $r['link_text'];
	$action      = $is_following ? 'unfollow' : 'follow';

	$button_args = array(
		'id'                => 'term_' . $r['term_id'],
		'component'         => 'follow',
		'must_be_logged_in' => true,
		'block_self'        => false,
		'wrapper_class'     => 'follow-term-button',
		'wrapper_id'        => 'follow-term-button-' . $r['term_id'],
		'link_href'         => wp_nonce_url(
			add_query_arg( array(
				'action'   => $action,
				'term_id'  => $r['term_id'],
				'taxonomy' => $r['taxonomy'],
			) ),
			'bp_follow_term_' . $action
		),
		'link_text'         => $button_text,
		'link_class'        => $r['link_class'] . ' ' . $action,
	);

	/**
	 * Filter the term follow button args.
	 *
	 * @since 2.1.0
	 *
	 * @param array $button_args Button arguments.
	 * @param array $r           Original arguments.
	 */
	$button_args = apply_filters( 'bp_follow_get_term_button_args', $button_args, $r );

	return bp_get_button( $button_args );
}

/**
 * Display author follow stats.
 *
 * @since 2.1.0
 *
 * @param int    $author_id Author ID.
 * @param string $post_type Post type.
 */
function bp_follow_author_stats( $author_id = 0, $post_type = 'post' ) {
	if ( ! $author_id ) {
		$author_id = get_queried_object_id();
	}

	if ( ! $author_id ) {
		return;
	}

	$service = bp_follow_get_author_service();
	$count   = $service->get_follower_count( $author_id, $post_type );

	printf(
		'<span class="bp-follow-author-stats">%s</span>',
		sprintf(
			/* translators: %d: follower count */
			esc_html( _n( '%d Follower', '%d Followers', $count, 'buddypress-followers' ) ),
			esc_html( number_format_i18n( $count ) )
		)
	);
}

/**
 * Display term follow stats.
 *
 * @since 2.1.0
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 */
function bp_follow_term_stats( $term_id = 0, $taxonomy = 'category' ) {
	if ( ! $term_id ) {
		$term_id = get_queried_object_id();
	}

	if ( ! $term_id ) {
		return;
	}

	$service = bp_follow_get_category_service();
	$count   = $service->get_follower_count( $term_id, $taxonomy );

	printf(
		'<span class="bp-follow-term-stats">%s</span>',
		sprintf(
			/* translators: %d: follower count */
			esc_html( _n( '%d Follower', '%d Followers', $count, 'buddypress-followers' ) ),
			esc_html( number_format_i18n( $count ) )
		)
	);
}
