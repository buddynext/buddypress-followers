<?php
/**
 * BuddyPress - Members Follow
 *
 * @since 2.0.0
 * @version 2.0.0
 */
?>

<?php bp_nouveau_member_hook( 'before', bp_current_action() . '_content' ); ?>

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<div class="members <?php echo esc_attr( bp_current_action() ); ?>" data-bp-list="members">

	<?php bp_get_template_part( 'members/members-loop' ); ?>

</div><!-- .members -->

<?php bp_nouveau_member_hook( 'after', bp_current_action() . '_content' );
