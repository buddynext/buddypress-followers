<?php
/**
 * Content Following User Settings.
 *
 * @package BP-Follow
 * @subpackage Content
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add Content Follows settings sub-nav.
 *
 * @since 2.1.0
 */
function bp_follow_content_setup_settings_nav() {
	if ( ! bp_is_active( 'settings' ) ) {
		return;
	}

	// Only add for logged-in user viewing their own profile.
	if ( ! bp_is_my_profile() ) {
		return;
	}

	bp_core_new_subnav_item(
		array(
			'name'            => __( 'Content Follows', 'buddypress-followers' ),
			'slug'            => 'content-follows',
			'parent_url'      => trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() ),
			'parent_slug'     => bp_get_settings_slug(),
			'screen_function' => 'bp_follow_content_settings_screen',
			'position'        => 60,
			'user_has_access' => bp_is_my_profile(),
		)
	);
}
add_action( 'bp_setup_nav', 'bp_follow_content_setup_settings_nav', 100 );

/**
 * Content follows settings screen.
 *
 * @since 2.1.0
 */
function bp_follow_content_settings_screen() {
	// Handle form submission.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in bp_follow_content_settings_save().
	if ( isset( $_POST['bp-follow-content-settings-submit'] ) ) {
		bp_follow_content_settings_save();
	}

	add_action( 'bp_template_content', 'bp_follow_content_settings_screen_content' );
	bp_core_load_template( apply_filters( 'bp_settings_screen_content_follows', 'members/single/settings/general' ) );
}

/**
 * Save content follows settings.
 *
 * @since 2.1.0
 */
function bp_follow_content_settings_save() {
	// Check nonce.
	check_admin_referer( 'bp_follow_content_settings' );

	$user_id = bp_displayed_user_id();

	// Save digest preferences.
	if ( isset( $_POST['digest_frequency'] ) ) {
		bp_update_user_meta( $user_id, 'bp_follow_digest_frequency', sanitize_text_field( wp_unslash( $_POST['digest_frequency'] ) ) );
	}

	// Save per-post-type digest mode preferences.
	if ( isset( $_POST['post_type_digest_mode'] ) && is_array( $_POST['post_type_digest_mode'] ) ) {
		global $wpdb, $bp;

		$table_prefix = $bp->table_prefix;
		$prefs_table  = $table_prefix . 'bp_follow_digest_prefs';

		$post_type_modes = wp_unslash( $_POST['post_type_digest_mode'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
		foreach ( $post_type_modes as $post_type => $mode ) {
			$post_type = sanitize_key( $post_type );
			$mode      = sanitize_text_field( $mode );

			if ( ! in_array( $mode, array( 'combined', 'separate' ), true ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare(
				"INSERT INTO {$prefs_table}
				(user_id, post_type, digest_mode, created_time, updated_time)
				VALUES (%d, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				digest_mode = %s,
				updated_time = %s",
				$user_id,
				$post_type,
				$mode,
				current_time( 'mysql' ),
				current_time( 'mysql' ),
				$mode,
				current_time( 'mysql' )
			) );
		}
	}

	bp_core_add_message( __( 'Settings saved successfully.', 'buddypress-followers' ) );
	bp_core_redirect( bp_displayed_user_domain() . bp_get_settings_slug() . '/content-follows/' );
}

/**
 * Content follows settings screen content.
 *
 * @since 2.1.0
 */
function bp_follow_content_settings_screen_content() {
	$user_id = bp_displayed_user_id();

	// Get current settings.
	$digest_frequency = bp_get_user_meta( $user_id, 'bp_follow_digest_frequency', true );
	if ( ! $digest_frequency ) {
		$digest_frequency = 'daily';
	}

	// Get enabled post types.
	$enabled_post_types = bp_follow_get_enabled_post_types();
	$enabled_taxonomies = bp_follow_get_enabled_taxonomies();

	?>
	<h2><?php esc_html_e( 'Content Follows Settings', 'buddypress-followers' ); ?></h2>

	<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/content-follows/' ); ?>" method="post">

		<!-- Followed Authors Section -->
		<?php if ( ! empty( $enabled_post_types ) ) : ?>
			<h3><?php esc_html_e( 'Followed Authors', 'buddypress-followers' ); ?></h3>

			<?php foreach ( $enabled_post_types as $post_type ) : ?>
				<?php
				$service      = bp_follow_get_author_service();
				$author_ids   = $service->get_followed_authors( $user_id, $post_type, array( 'per_page' => 100 ) );
				$post_type_obj = get_post_type_object( $post_type );
				?>

				<div class="bp-follow-followed-authors-section">
					<h4><?php
					/* translators: %s: Post type name (e.g., "Posts", "Pages") */
					echo esc_html( sprintf( __( '%s Authors', 'buddypress-followers' ), $post_type_obj->labels->name ) ); ?></h4>

					<?php if ( ! empty( $author_ids ) ) : ?>
						<ul class="bp-follow-content-list">
							<?php foreach ( $author_ids as $author_id ) : ?>
								<?php $author = get_userdata( $author_id ); ?>
								<?php if ( $author ) : ?>
									<li class="bp-follow-content-item">
										<div class="bp-follow-content-info">
											<?php echo get_avatar( $author_id, 40 ); ?>
											<div class="bp-follow-content-details">
												<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
													<?php echo esc_html( $author->display_name ); ?>
												</a>
												<span class="bp-follow-content-meta">
													<?php
													$count = $service->get_follower_count( $author_id, $post_type );
													printf(
														/* translators: %d: follower count */
														esc_html( _n( '%d follower', '%d followers', $count, 'buddypress-followers' ) ),
														esc_html( number_format_i18n( $count ) )
													);
													?>
												</span>
											</div>
										</div>
										<div class="bp-follow-content-actions">
											<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'unfollow', 'author_id' => $author_id, 'post_types' => $post_type ) ), 'bp_follow_author_unfollow' ) ); ?>" class="button">
												<?php esc_html_e( 'Unfollow', 'buddypress-followers' ); ?>
											</a>
										</div>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="bp-follow-no-content"><?php esc_html_e( 'You are not following any authors for this post type yet.', 'buddypress-followers' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<!-- Followed Categories/Tags Section -->
		<?php if ( ! empty( $enabled_taxonomies ) ) : ?>
			<h3><?php esc_html_e( 'Followed Categories & Tags', 'buddypress-followers' ); ?></h3>

			<?php foreach ( $enabled_taxonomies as $taxonomy ) : ?>
				<?php
				$service      = bp_follow_get_category_service();
				$term_ids     = $service->get_followed_terms( $user_id, $taxonomy, array( 'per_page' => 100 ) );
				$taxonomy_obj = get_taxonomy( $taxonomy );
				?>

				<div class="bp-follow-followed-terms-section">
					<h4><?php echo esc_html( $taxonomy_obj->labels->name ); ?></h4>

					<?php if ( ! empty( $term_ids ) ) : ?>
						<ul class="bp-follow-content-list">
							<?php foreach ( $term_ids as $term_id ) : ?>
								<?php $term = get_term( $term_id, $taxonomy ); ?>
								<?php if ( $term && ! is_wp_error( $term ) ) : ?>
									<li class="bp-follow-content-item">
										<div class="bp-follow-content-info">
											<div class="bp-follow-content-details">
												<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
													<?php echo esc_html( $term->name ); ?>
												</a>
												<span class="bp-follow-content-meta">
													<?php
													$count = $service->get_follower_count( $term_id, $taxonomy );
													printf(
														/* translators: %d: follower count */
														esc_html( _n( '%d follower', '%d followers', $count, 'buddypress-followers' ) ),
														esc_html( number_format_i18n( $count ) )
													);
													?>
													&bull;
													<?php
													printf(
														/* translators: %d: post count */
														esc_html( _n( '%d post', '%d posts', $term->count, 'buddypress-followers' ) ),
														esc_html( number_format_i18n( $term->count ) )
													);
													?>
												</span>
											</div>
										</div>
										<div class="bp-follow-content-actions">
											<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'unfollow', 'term_id' => $term_id, 'taxonomy' => $taxonomy ) ), 'bp_follow_term_unfollow' ) ); ?>" class="button">
												<?php esc_html_e( 'Unfollow', 'buddypress-followers' ); ?>
											</a>
										</div>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="bp-follow-no-content"><?php esc_html_e( 'You are not following any terms for this taxonomy yet.', 'buddypress-followers' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<!-- Digest Preferences Section -->
		<h3><?php esc_html_e( 'Digest Preferences', 'buddypress-followers' ); ?></h3>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="digest_frequency"><?php esc_html_e( 'Digest Frequency', 'buddypress-followers' ); ?></label>
					</th>
					<td>
						<select name="digest_frequency" id="digest_frequency">
							<option value="daily" <?php selected( $digest_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'buddypress-followers' ); ?></option>
							<option value="weekly" <?php selected( $digest_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'buddypress-followers' ); ?></option>
							<option value="disabled" <?php selected( $digest_frequency, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'buddypress-followers' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How often you want to receive digest emails for new content.', 'buddypress-followers' ); ?></p>
					</td>
				</tr>

				<?php
				// Check if admin allows user choice for digest mode.
				$post_types_settings = bp_get_option( '_bp_follow_post_types', array() );
				$has_user_choice = false;

				foreach ( $enabled_post_types as $post_type ) {
					if ( isset( $post_types_settings[ $post_type ]['digest_mode'] ) && 'user_choice' === $post_types_settings[ $post_type ]['digest_mode'] ) {
						$has_user_choice = true;
						break;
					}
				}
				?>

				<?php if ( $has_user_choice ) : ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Digest Organization', 'buddypress-followers' ); ?>
						</th>
						<td>
							<?php foreach ( $enabled_post_types as $post_type ) : ?>
								<?php
								if ( ! isset( $post_types_settings[ $post_type ]['digest_mode'] ) || 'user_choice' !== $post_types_settings[ $post_type ]['digest_mode'] ) {
									continue;
								}

								$post_type_obj = get_post_type_object( $post_type );

								global $wpdb, $bp;
								$table_prefix = $bp->table_prefix;
								$prefs_table  = $table_prefix . 'bp_follow_digest_prefs';

								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$user_mode = $wpdb->get_var( $wpdb->prepare(
									"SELECT digest_mode FROM {$prefs_table} WHERE user_id = %d AND post_type = %s",
									$user_id,
									$post_type
								) );

								if ( ! $user_mode ) {
									$user_mode = 'combined';
								}
								?>

								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html( $post_type_obj->labels->name ); ?></legend>
									<label>
										<strong><?php echo esc_html( $post_type_obj->labels->name ); ?>:</strong><br>
										<input type="radio" name="post_type_digest_mode[<?php echo esc_attr( $post_type ); ?>]" value="combined" <?php checked( $user_mode, 'combined' ); ?>>
										<?php esc_html_e( 'Combined with other content', 'buddypress-followers' ); ?>
										<br>
										<input type="radio" name="post_type_digest_mode[<?php echo esc_attr( $post_type ); ?>]" value="separate" <?php checked( $user_mode, 'separate' ); ?>>
										<?php esc_html_e( 'Separate email for this content type', 'buddypress-followers' ); ?>
									</label>
								</fieldset>
								<br>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Choose how you want to receive digest emails for different content types.', 'buddypress-followers' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<?php wp_nonce_field( 'bp_follow_content_settings' ); ?>

		<p class="submit">
			<input type="submit" name="bp-follow-content-settings-submit" id="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'buddypress-followers' ); ?>">
		</p>
	</form>

	<style>
		.bp-follow-content-list {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.bp-follow-content-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 15px;
			border-bottom: 1px solid #ddd;
		}

		.bp-follow-content-item:last-child {
			border-bottom: none;
		}

		.bp-follow-content-info {
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.bp-follow-content-details {
			display: flex;
			flex-direction: column;
		}

		.bp-follow-content-details a {
			font-weight: 600;
			text-decoration: none;
		}

		.bp-follow-content-meta {
			color: #666;
			font-size: 13px;
			margin-top: 5px;
		}

		.bp-follow-followed-authors-section,
		.bp-follow-followed-terms-section {
			margin-bottom: 30px;
			background: #f9f9f9;
			padding: 20px;
			border-radius: 5px;
		}

		.bp-follow-followed-authors-section h4,
		.bp-follow-followed-terms-section h4 {
			margin-top: 0;
			border-bottom: 2px solid #ddd;
			padding-bottom: 10px;
		}

		.bp-follow-no-content {
			color: #666;
			font-style: italic;
		}
	</style>
	<?php
}
