<?php
/**
 * Followed Categories Feed Shortcode Template.
 *
 * @package BP-Follow
 * @subpackage Templates
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get template args.
$user_id        = isset( $args['user_id'] ) ? absint( $args['user_id'] ) : get_current_user_id();
$posts_per_page = isset( $args['posts_per_page'] ) ? absint( $args['posts_per_page'] ) : 10;
$taxonomy       = isset( $args['taxonomy'] ) ? sanitize_key( $args['taxonomy'] ) : 'category';

// Get service and followed terms.
$service  = bp_follow_get_category_service();
$term_ids = $service->get_followed_terms( $user_id, $taxonomy, array( 'per_page' => 100 ) );

// Get terms data.
$terms_data = array();
if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) || $term->count <= 0 ) {
			continue;
		}
		$terms_data[] = array(
			'id'    => $term_id,
			'name'  => $term->name,
			'count' => $term->count,
			'url'   => get_term_link( $term ),
		);
	}
}

// Get initial posts.
$posts_query = null;
if ( ! empty( $term_ids ) ) {
	$posts_query = new WP_Query( array(
		'post_type'      => 'post',
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			),
		),
		'posts_per_page' => $posts_per_page,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

$taxonomy_obj = get_taxonomy( $taxonomy );
?>

<div class="bp-follow-feed-container" data-feed-type="categories" data-user-id="<?php echo esc_attr( $user_id ); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-per-page="<?php echo esc_attr( $posts_per_page ); ?>">

	<?php if ( empty( $terms_data ) ) : ?>
		<div class="bp-follow-empty-state">
			<p>
				<?php
				printf(
					/* translators: %s: taxonomy name */
					esc_html__( 'You are not following any %s yet.', 'buddypress-followers' ),
					esc_html( strtolower( $taxonomy_obj->labels->name ) )
				);
				?>
			</p>
			<p><a href="<?php echo esc_url( site_url( '/blog/' ) ); ?>" class="button"><?php esc_html_e( 'Browse Posts', 'buddypress-followers' ); ?></a></p>
		</div>
	<?php else : ?>

		<div class="bp-follow-feed-layout">
			<!-- Sidebar Filter -->
			<aside class="bp-follow-feed-sidebar">
				<div class="bp-follow-sidebar-header">
					<h3>
						<?php
						printf(
							/* translators: %s: taxonomy name */
							esc_html__( 'Filter by %s', 'buddypress-followers' ),
							esc_html( $taxonomy_obj->labels->singular_name )
						);
						?>
					</h3>
					<button type="button" class="bp-follow-toggle-all button-secondary">
						<?php esc_html_e( 'Toggle All', 'buddypress-followers' ); ?>
					</button>
				</div>

				<ul class="bp-follow-filter-list" role="list">
					<?php foreach ( $terms_data as $term ) : ?>
						<li class="bp-follow-filter-item" data-term-id="<?php echo esc_attr( $term['id'] ); ?>">
							<label class="bp-follow-filter-label">
								<input type="checkbox" class="bp-follow-filter-checkbox" value="<?php echo esc_attr( $term['id'] ); ?>" checked>
								<span class="bp-follow-filter-name">
									<?php echo esc_html( $term['name'] ); ?>
									<span class="bp-follow-filter-count">(<?php echo esc_html( $term['count'] ); ?>)</span>
								</span>
							</label>
							<button type="button" class="bp-follow-unfollow-btn" data-term-id="<?php echo esc_attr( $term['id'] ); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" title="<?php esc_attr_e( 'Unfollow', 'buddypress-followers' ); ?>">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="bp-follow-sidebar-actions">
					<button type="button" class="bp-follow-apply-filter button button-primary">
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
						<p class="bp-follow-no-posts">
							<?php
							printf(
								/* translators: %s: taxonomy name */
								esc_html__( 'No posts found in the selected %s.', 'buddypress-followers' ),
								esc_html( strtolower( $taxonomy_obj->labels->name ) )
							);
							?>
						</p>
					<?php endif; ?>
				</div>

				<div class="bp-follow-loading" style="display: none;">
					<span class="spinner is-active"></span>
				</div>
			</main>
		</div>

	<?php endif; ?>
</div>
