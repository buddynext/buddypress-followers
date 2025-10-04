<?php
/**
 * BuddyPress - Members Follow
 *
 * Template override for content follows (authors/categories).
 *
 * @since 2.1.0
 */

// Check current action.
$action = bp_current_action();

if ( 'authors' === $action || 'categories' === $action ) {
	// Content follows template.
	?>
	<?php bp_nouveau_member_hook( 'before', $action . '_content' ); ?>

	<div class="<?php echo esc_attr( $action ); ?>" data-bp-content-follows="<?php echo esc_attr( $action ); ?>">

		<?php bp_get_template_part( 'members/single/plugins/content-follows/' . $action ); ?>

	</div><!-- .<?php echo esc_attr( $action ); ?> -->

	<?php bp_nouveau_member_hook( 'after', $action . '_content' ); ?>
	<?php
} else {
	// Default social following template.
	?>
	<?php bp_nouveau_member_hook( 'before', bp_current_action() . '_content' ); ?>

	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

	<div class="members <?php echo esc_attr( bp_current_action() ); ?>" data-bp-list="members">

		<?php bp_get_template_part( 'members/members-loop' ); ?>

	</div><!-- .members -->

	<?php bp_nouveau_member_hook( 'after', bp_current_action() . '_content' ); ?>
	<?php
}
