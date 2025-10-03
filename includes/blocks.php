<?php
/**
 * BP Follow Blocks Registration and Rendering
 *
 * @package BP-Follow
 * @subpackage Blocks
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register all Follow blocks.
 *
 * Registers the "Users I'm Following" and "My Followers" Gutenberg blocks
 * for displaying follower/following lists in the block editor.
 *
 * @since 2.0.0
 */
function bp_follow_register_blocks() {
	$blocks = array(
		'bp-follow/following' => array(
			'metadata'        => BP_FOLLOW_DIR . '/blocks/following',
			'render_callback' => 'bp_follow_render_following_block',
		),
		'bp-follow/followers' => array(
			'metadata'        => BP_FOLLOW_DIR . '/blocks/followers',
			'render_callback' => 'bp_follow_render_followers_block',
		),
	);

	foreach ( $blocks as $block_name => $block_args ) {
		register_block_type(
			$block_args['metadata'],
			array(
				'render_callback' => $block_args['render_callback'],
			)
		);
	}
}
add_action( 'init', 'bp_follow_register_blocks' );

/**
 * Render the Following block.
 *
 * Displays a list of members that a user is following. Shows member avatars,
 * names, last active time, and a link to view the complete following list.
 *
 * @since 2.0.0
 *
 * @param array $attributes {
 *     Block attributes.
 *
 *     @type string $title    Block title. Default "Users I'm Following".
 *     @type int    $maxUsers Maximum number of members to display. Default 16.
 *     @type int    $userId   User ID to show following for. Default 0 (logged-in user).
 * }
 * @return string Block HTML output, or empty string if no following found.
 */
function bp_follow_render_following_block( $attributes ) {
	$title     = isset( $attributes['title'] ) ? $attributes['title'] : __( "Users I'm Following", 'buddypress-followers' );
	$max_users = isset( $attributes['maxUsers'] ) ? (int) $attributes['maxUsers'] : 16;
	$user_id   = isset( $attributes['userId'] ) ? (int) $attributes['userId'] : 0;

	// If no user ID specified, use logged-in user.
	if ( 0 === $user_id ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$user_id = bp_loggedin_user_id();
	}

	// Get following list for specified user.
	$following = bp_get_following_ids( array( 'user_id' => $user_id ) );
	if ( ! $following ) {
		return '';
	}

	// Show the users the logged-in user is following.
	if ( ! bp_has_members( array(
		'include'         => $following,
		'max'             => $max_users,
		'populate_extras' => false,
	) ) ) {
		return '';
	}

	$bp                 = buddypress();
	$classnames         = 'widget_bp_core_members_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	ob_start();

	do_action( 'bp_before_following_block' );
	?>

	<div <?php echo $wrapper_attributes; ?>>
		<?php if ( $title ) : ?>
			<h2 class="widget-title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<div class="item-options">
			<a href="<?php echo esc_url( bp_members_get_user_url( $user_id, array( $bp->follow->following->slug ) ) ); ?>">
				<?php esc_html_e( 'See all', 'buddypress-followers' ); ?>
			</a>
		</div>

		<ul id="members-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
			<?php while ( bp_members() ) : bp_the_member(); ?>
				<li class="vcard">
					<div class="item-avatar">
						<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip="<?php bp_member_name(); ?>">
							<?php bp_member_avatar( array( 'type' => 'thumb', 'width' => 50, 'height' => 50 ) ); ?>
						</a>
					</div>
					<div class="item">
						<div class="item-title fn">
							<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
						</div>
						<div class="item-meta">
							<span class="activity"><?php bp_member_last_active(); ?></span>
						</div>
					</div>
				</li>
			<?php endwhile; ?>
		</ul>
	</div>

	<?php
	do_action( 'bp_after_following_block' );

	return ob_get_clean();
}

/**
 * Render the Followers block.
 *
 * Displays a list of members that follow a user. Shows member avatars,
 * names, last active time, and a link to view the complete followers list.
 *
 * @since 2.0.0
 *
 * @param array $attributes {
 *     Block attributes.
 *
 *     @type string $title    Block title. Default "My Followers".
 *     @type int    $maxUsers Maximum number of members to display. Default 16.
 *     @type int    $userId   User ID to show followers for. Default 0 (logged-in user).
 * }
 * @return string Block HTML output, or empty string if no followers found.
 */
function bp_follow_render_followers_block( $attributes ) {
	$title     = isset( $attributes['title'] ) ? $attributes['title'] : __( 'My Followers', 'buddypress-followers' );
	$max_users = isset( $attributes['maxUsers'] ) ? (int) $attributes['maxUsers'] : 16;
	$user_id   = isset( $attributes['userId'] ) ? (int) $attributes['userId'] : 0;

	// If no user ID specified, use logged-in user.
	if ( 0 === $user_id ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$user_id = bp_loggedin_user_id();
	}

	// Get followers for specified user.
	$followers = bp_get_follower_ids( array( 'user_id' => $user_id ) );
	if ( ! $followers ) {
		return '';
	}

	// Show the users who follow the logged-in user.
	if ( ! bp_has_members( array(
		'include'         => $followers,
		'max'             => $max_users,
		'populate_extras' => false,
	) ) ) {
		return '';
	}

	$bp                 = buddypress();
	$classnames         = 'widget_bp_core_members_widget buddypress widget';
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	ob_start();

	do_action( 'bp_before_followers_block' );
	?>

	<div <?php echo $wrapper_attributes; ?>>
		<?php if ( $title ) : ?>
			<h2 class="widget-title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<div class="item-options">
			<a href="<?php echo esc_url( bp_members_get_user_url( $user_id, array( $bp->follow->followers->slug ) ) ); ?>">
				<?php esc_html_e( 'See all', 'buddypress-followers' ); ?>
			</a>
		</div>

		<ul id="members-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
			<?php while ( bp_members() ) : bp_the_member(); ?>
				<li class="vcard">
					<div class="item-avatar">
						<a href="<?php bp_member_permalink(); ?>" class="bp-tooltip" data-bp-tooltip="<?php bp_member_name(); ?>">
							<?php bp_member_avatar( array( 'type' => 'thumb', 'width' => 50, 'height' => 50 ) ); ?>
						</a>
					</div>
					<div class="item">
						<div class="item-title fn">
							<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
						</div>
						<div class="item-meta">
							<span class="activity"><?php bp_member_last_active(); ?></span>
						</div>
					</div>
				</li>
			<?php endwhile; ?>
		</ul>
	</div>

	<?php
	do_action( 'bp_after_followers_block' );

	return ob_get_clean();
}
