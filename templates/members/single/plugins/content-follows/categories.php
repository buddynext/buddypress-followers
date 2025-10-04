<?php
/**
 * Followed Categories & Tags Template.
 *
 * Displays posts from categories and tags the user follows using the shortcode.
 * This is separate from following members socially.
 *
 * @package BP-Follow
 * @subpackage Templates
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$user_id = bp_displayed_user_id();
?>

<div id="bp-follow-followed-categories" class="bp-follow-content-page" role="main" aria-label="<?php esc_attr_e( 'Posts from Followed Categories', 'buddypress-followers' ); ?>">
	<?php do_action( 'bp_before_followed_categories_content' ); ?>

	<?php
	// Use the shortcode to display the feed
	echo do_shortcode( '[bp_followed_categories_feed user_id="' . $user_id . '"]' );
	?>

	<?php do_action( 'bp_after_followed_categories_content' ); ?>
</div>

