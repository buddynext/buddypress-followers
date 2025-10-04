<?php
/**
 * Followed Authors Template.
 *
 * Displays posts from authors the user follows using the shortcode.
 * This is separate from following members socially.
 *
 * @package BP-Follow
 * @subpackage Templates
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$user_id = bp_displayed_user_id();
?>

<div id="bp-follow-followed-authors" class="bp-follow-content-page" role="main" aria-label="<?php esc_attr_e( 'Posts from Followed Authors', 'buddypress-followers' ); ?>">
	<?php do_action( 'bp_before_followed_authors_content' ); ?>

	<?php
	// Use the shortcode to display the feed
	echo do_shortcode( '[bp_followed_authors_feed user_id="' . $user_id . '"]' );
	?>

	<?php do_action( 'bp_after_followed_authors_content' ); ?>
</div>
