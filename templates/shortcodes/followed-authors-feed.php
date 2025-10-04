<?php
/**
 * Followed Authors Feed Shortcode Template.
 *
 * @package BP-Follow
 * @subpackage Templates
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get template args.
$user_id        = isset( $args['user_id'] ) ? absint( $args['user_id'] ) : get_current_user_id();
$posts_per_page = isset( $args['posts_per_page'] ) ? absint( $args['posts_per_page'] ) : 10;
$post_type      = isset( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post';

// Get service and followed authors.
$service    = bp_follow_get_author_service();
$author_ids = $service->get_followed_authors( $user_id, $post_type, array( 'per_page' => 100 ) );

// Get authors data.
$authors_data = array();
if ( ! empty( $author_ids ) && is_array( $author_ids ) ) {
	foreach ( $author_ids as $author_id ) {
		$author = get_userdata( $author_id );
		if ( ! $author ) {
			continue;
		}
		$post_count = count_user_posts( $author_id, $post_type, true );
		if ( $post_count > 0 ) {
			$authors_data[] = array(
				'id'    => $author_id,
				'name'  => $author->display_name,
				'count' => $post_count,
				'url'   => get_author_posts_url( $author_id ),
			);
		}
	}
}

// Get initial posts.
$posts_query = null;
if ( ! empty( $author_ids ) ) {
	$posts_query = new WP_Query( array(
		'post_type'      => $post_type,
		'author__in'     => $author_ids,
		'posts_per_page' => $posts_per_page,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}
?>

<div class="bp-follow-feed-container" data-feed-type="authors" data-user-id="<?php echo esc_attr( $user_id ); ?>" data-post-type="<?php echo esc_attr( $post_type ); ?>" data-per-page="<?php echo esc_attr( $posts_per_page ); ?>">

	<?php if ( empty( $authors_data ) ) : ?>
		<div class="bp-follow-empty-state">
			<p><?php esc_html_e( 'You are not following any authors yet.', 'buddypress-followers' ); ?></p>
			<p><a href="<?php echo esc_url( site_url( '/blog/' ) ); ?>" class="button"><?php esc_html_e( 'Browse Posts', 'buddypress-followers' ); ?></a></p>
		</div>
	<?php else : ?>

		<div class="bp-follow-feed-layout">
			<!-- Sidebar Filter -->
			<aside class="bp-follow-feed-sidebar">
				<div class="bp-follow-sidebar-header">
					<h3><?php esc_html_e( 'Filter by Author', 'buddypress-followers' ); ?></h3>
					<button type="button" class="bp-follow-toggle-all button-secondary" title="<?php esc_attr_e( 'Select/deselect all', 'buddypress-followers' ); ?>">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Toggle All', 'buddypress-followers' ); ?>
					</button>
				</div>

				<div class="bp-follow-filter-help">
					<span class="dashicons dashicons-info-outline"></span>
					<span><?php esc_html_e( 'Uncheck authors to hide their posts', 'buddypress-followers' ); ?></span>
				</div>

				<ul class="bp-follow-filter-list <?php echo count( $authors_data ) > 5 ? 'bp-follow-collapsed' : ''; ?>" role="list" data-item-count="<?php echo count( $authors_data ); ?>">
					<?php foreach ( $authors_data as $author ) : ?>
						<li class="bp-follow-filter-item" data-author-id="<?php echo esc_attr( $author['id'] ); ?>">
							<label class="bp-follow-filter-label" title="<?php esc_attr_e( 'Show/hide posts', 'buddypress-followers' ); ?>">
								<input type="checkbox" class="bp-follow-filter-checkbox" value="<?php echo esc_attr( $author['id'] ); ?>" checked>
								<span class="bp-follow-filter-name">
									<?php echo esc_html( $author['name'] ); ?>
									<span class="bp-follow-filter-count" title="<?php echo esc_attr( sprintf( __( '%d posts', 'buddypress-followers' ), $author['count'] ) ); ?>">(<?php echo esc_html( $author['count'] ); ?>)</span>
								</span>
							</label>
							<button type="button"
								class="bp-follow-unfollow-btn bp-tooltip"
								data-author-id="<?php echo esc_attr( $author['id'] ); ?>"
								data-tooltip="<?php esc_attr_e( 'Unfollow', 'buddypress-followers' ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Unfollow %s', 'buddypress-followers' ), $author['name'] ) ); ?>">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( count( $authors_data ) > 5 ) : ?>
					<div class="bp-follow-show-more-wrapper">
						<button type="button" class="bp-follow-show-more">
							<span><?php esc_html_e( 'Show More', 'buddypress-followers' ); ?></span>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
				<?php endif; ?>

				<div class="bp-follow-sidebar-actions">
					<button type="button" class="bp-follow-apply-filter button button-primary" title="<?php esc_attr_e( 'Update posts', 'buddypress-followers' ); ?>">
						<span class="dashicons dashicons-filter"></span>
						<?php esc_html_e( 'Apply Filter', 'buddypress-followers' ); ?>
					</button>
				</div>
			</aside>

			<!-- Posts Feed -->
			<main class="bp-follow-feed-content">
				<div class="bp-follow-feed-header">
					<h2><?php esc_html_e( 'Recent Posts', 'buddypress-followers' ); ?></h2>
					<span class="bp-follow-posts-count">
						<?php
						if ( $posts_query ) {
							printf(
								/* translators: %d: number of posts */
								esc_html( _n( '%d post', '%d posts', $posts_query->found_posts, 'buddypress-followers' ) ),
								esc_html( number_format_i18n( $posts_query->found_posts ) )
							);
						}
						?>
					</span>
				</div>

				<div class="bp-follow-posts-container">
					<?php if ( $posts_query && $posts_query->have_posts() ) : ?>
						<ul class="bp-follow-posts-list" role="list">
							<?php
							while ( $posts_query->have_posts() ) {
								$posts_query->the_post();
								bp_get_template_part( 'shortcodes/parts/post-item' );
							}
							wp_reset_postdata();
							?>
						</ul>

						<?php if ( $posts_query->max_num_pages > 1 ) : ?>
							<nav class="bp-follow-pagination">
								<button type="button" class="bp-follow-load-more button" data-paged="2" data-max-pages="<?php echo esc_attr( $posts_query->max_num_pages ); ?>">
									<?php esc_html_e( 'Load More', 'buddypress-followers' ); ?>
								</button>
							</nav>
						<?php endif; ?>
					<?php else : ?>
						<p class="bp-follow-no-posts"><?php esc_html_e( 'No posts found from the selected authors.', 'buddypress-followers' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="bp-follow-loading" style="display: none;">
					<span class="spinner is-active"></span>
				</div>
			</main>
		</div>

	<?php endif; ?>
</div>
