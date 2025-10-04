<?php
/**
 * Post Item Template Part.
 *
 * Used in feed shortcodes to display individual posts.
 *
 * @package BP-Follow
 * @subpackage Templates
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<li class="bp-follow-post-item" role="listitem">
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'bp-follow-news-card' ); ?>>
		<div class="bp-follow-post-layout">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="bp-follow-post-thumbnail">
					<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
						<?php the_post_thumbnail( 'medium', array(
							'alt' => get_the_title(),
							'loading' => 'lazy'
						) ); ?>
					</a>
				</div>
			<?php endif; ?>

			<div class="bp-follow-post-content">
				<header class="entry-header">
					<div class="entry-meta-top">
						<span class="post-category">
							<?php
							$categories = get_the_category();
							if ( ! empty( $categories ) ) {
								$primary_cat = $categories[0];
								echo '<a href="' . esc_url( get_category_link( $primary_cat->term_id ) ) . '" class="category-badge">' . esc_html( $primary_cat->name ) . '</a>';
							}
							?>
						</span>
						<span class="post-date">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ago' ); ?>
							</time>
						</span>
					</div>

					<h3 class="entry-title">
						<a href="<?php the_permalink(); ?>" class="post-title-link">
							<?php the_title(); ?>
						</a>
					</h3>

					<div class="entry-meta-bottom">
						<span class="author-info">
							<?php echo get_avatar( get_the_author_meta( 'ID' ), 24 ); ?>
							<span class="author-name">
								<?php the_author_posts_link(); ?>
							</span>
						</span>

						<?php if ( get_comments_number() > 0 ) : ?>
							<span class="meta-separator">&bull;</span>
							<span class="comments-count">
								<span class="dashicons dashicons-admin-comments"></span>
								<?php comments_number( '0', '1', '%' ); ?>
							</span>
						<?php endif; ?>

						<span class="meta-separator">&bull;</span>
						<span class="read-time">
							<?php
							$content = get_the_content();
							$word_count = str_word_count( strip_tags( $content ) );
							$reading_time = ceil( $word_count / 200 ); // Assuming 200 words per minute
							echo esc_html( $reading_time . ' min read' );
							?>
						</span>
					</div>
				</header>

				<div class="entry-summary">
					<?php
					if ( has_excerpt() ) {
						the_excerpt();
					} else {
						echo wp_trim_words( get_the_content(), 20, '...' );
					}
					?>
				</div>

				<footer class="entry-footer">
					<a href="<?php the_permalink(); ?>" class="read-more-link">
						<?php esc_html_e( 'Read More', 'buddypress-followers' ); ?>
						<span class="dashicons dashicons-arrow-right-alt"></span>
					</a>
				</footer>
			</div>
		</div>
	</article>
</li>
