<?php
/**
 * Follow button rendering service.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\Service;

use function apply_filters;
use function bp_core_get_user_displayname;
use function bp_displayed_user_id;
use function bp_follow_get_user_url;
use function _x;
use function bp_follow_is_doing_ajax;
use function bp_follow_is_following;
use function bp_get_button;
use function bp_get_loggedin_user_fullname;
use function bp_get_member_user_id;
use function bp_get_user_firstname;
use function bp_is_group;
use function bp_is_my_profile;
use function bp_loggedin_user_id;
use function esc_attr;
use function wp_nonce_url;
use function wp_parse_args;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates follow and unfollow buttons.
 */
class ButtonService {
	/**
	 * Render follow/unfollow button markup.
	 *
	 * Mirrors the legacy template logic but centralised for reuse.
	 *
	 * @param array $args Button arguments.
	 * @return string|false
	 */
	public function render_button( $args = array() ) {
		global $members_template;

		$bp = $GLOBALS['bp'];

		$r = wp_parse_args(
			$args,
			array(
				'leader_id'     => bp_displayed_user_id(),
				'follower_id'   => bp_loggedin_user_id(),
				'link_text'     => '',
				'link_title'    => '',
				'wrapper_class' => '',
				'link_class'    => '',
				'wrapper'       => 'div',
			)
		);

		if ( ! $r['leader_id'] || ! $r['follower_id'] ) {
			return false;
		}

		if ( ! empty( $members_template->in_the_loop ) && $r['follower_id'] === bp_loggedin_user_id() && $r['leader_id'] === bp_get_member_user_id() ) {
			$is_following = $members_template->member->is_following;
		} else {
			$is_following = bp_follow_is_following(
				array(
					'leader_id'   => $r['leader_id'],
					'follower_id' => $r['follower_id'],
				)
			);
		}

		$logged_user_id = bp_loggedin_user_id();

		if ( $logged_user_id && $logged_user_id === $r['leader_id'] ) {
			$leader_fullname = bp_get_loggedin_user_fullname();
		} else {
			$leader_fullname = bp_core_get_user_displayname( $r['leader_id'] );
		}

		if ( $is_following ) {
			$id        = 'following';
			$action    = 'stop';
			$class     = 'unfollow';
			$link_text = sprintf( _x( 'Unfollow', 'Button', 'buddypress-followers' ), apply_filters( 'bp_follow_leader_name', bp_get_user_firstname( $leader_fullname ), $r['leader_id'] ) );
		} else {
			$id        = 'not-following';
			$action    = 'start';
			$class     = 'follow';
			$link_text = sprintf( _x( 'Follow', 'Button', 'buddypress-followers' ), apply_filters( 'bp_follow_leader_name', bp_get_user_firstname( $leader_fullname ), $r['leader_id'] ) );
		}

		if ( empty( $r['link_text'] ) ) {
			$r['link_text'] = $link_text;
		}

		$wrapper_class = 'follow-button ' . $id;
		if ( ! empty( $r['wrapper_class'] ) ) {
			$wrapper_class .= ' ' . esc_attr( $r['wrapper_class'] );
		}

		$link_class = $class;
		if ( ! empty( $r['link_class'] ) ) {
			$link_class .= ' ' . esc_attr( $r['link_class'] );
		}

		$block_self = empty( $members_template->member );
		if ( bp_follow_is_doing_ajax() && bp_is_my_profile() ) {
			$block_self = false;
		}

		$button = array(
			'id'                => $id,
			'component'         => 'follow',
			'must_be_logged_in' => true,
			'block_self'        => $block_self,
			'wrapper_class'     => $wrapper_class,
			'wrapper_id'        => 'follow-button-' . (int) $r['leader_id'],
			'link_href'         => wp_nonce_url( bp_follow_get_user_url( $r['leader_id'], array( $bp->follow->followers->slug, $action ) ), $action . '_following' ),
			'link_text'         => esc_attr( $r['link_text'] ),
			'link_title'        => esc_attr( $r['link_title'] ),
			'link_id'           => $class . '-' . (int) $r['leader_id'],
			'link_class'        => $link_class,
			'wrapper'           => ! empty( $r['wrapper'] ) ? esc_attr( $r['wrapper'] ) : false,
		);

		if ( function_exists( 'bp_nouveau' ) ) {
			if ( $button['wrapper'] && ! bp_is_group() ) {
				$button['parent_element'] = 'li';
			}
			$button['link_class'] .= ' button';
		}

		return bp_get_button( apply_filters( 'bp_follow_get_add_follow_button', $button, $r['leader_id'], $r['follower_id'] ) );
	}
}
