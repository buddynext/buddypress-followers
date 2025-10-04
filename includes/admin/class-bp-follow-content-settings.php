<?php
/**
 * BP Follow Admin Settings.
 *
 * @package BP-Follow
 * @subpackage Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin settings class for BuddyPress Follow.
 *
 * @since 2.1.0
 */
class BP_Follow_Admin_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'BuddyPress Follow Settings', 'buddypress-followers' ),
			__( 'BP Follow', 'buddypress-followers' ),
			'manage_options',
			'bp-follow-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// General Settings.
		register_setting( 'bp_follow_general', '_bp_follow_general_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
		) );

		// Post Type Settings.
		register_setting( 'bp_follow_post_types', '_bp_follow_post_types', array(
			'sanitize_callback' => array( $this, 'sanitize_post_types' ),
		) );

		// Taxonomy Settings.
		register_setting( 'bp_follow_taxonomies', '_bp_follow_taxonomies', array(
			'sanitize_callback' => array( $this, 'sanitize_taxonomies' ),
		) );

		// Notification Settings.
		register_setting( 'bp_follow_notifications', '_bp_follow_notification_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_notification_settings' ),
		) );

		// Advanced Settings.
		register_setting( 'bp_follow_advanced', '_bp_follow_advanced_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
		) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_bp-follow-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'bp-follow-admin', BP_FOLLOW_URL . 'assets/css/admin.css', array(), '2.1.0' );
		wp_enqueue_script( 'bp-follow-admin', BP_FOLLOW_URL . 'assets/js/admin.js', array( 'jquery' ), '2.1.0', true );
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BuddyPress Follow Settings', 'buddypress-followers' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=bp-follow-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'buddypress-followers' ); ?>
				</a>
				<a href="?page=bp-follow-settings&tab=post-types" class="nav-tab <?php echo 'post-types' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Post Types', 'buddypress-followers' ); ?>
				</a>
				<a href="?page=bp-follow-settings&tab=taxonomies" class="nav-tab <?php echo 'taxonomies' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Taxonomies', 'buddypress-followers' ); ?>
				</a>
				<a href="?page=bp-follow-settings&tab=notifications" class="nav-tab <?php echo 'notifications' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Notifications', 'buddypress-followers' ); ?>
				</a>
				<a href="?page=bp-follow-settings&tab=advanced" class="nav-tab <?php echo 'advanced' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Advanced', 'buddypress-followers' ); ?>
				</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				switch ( $active_tab ) {
					case 'general':
						$this->render_general_tab();
						break;
					case 'post-types':
						$this->render_post_types_tab();
						break;
					case 'taxonomies':
						$this->render_taxonomies_tab();
						break;
					case 'notifications':
						$this->render_notifications_tab();
						break;
					case 'advanced':
						$this->render_advanced_tab();
						break;
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render General tab.
	 */
	protected function render_general_tab() {
		settings_fields( 'bp_follow_general' );
		$settings = bp_get_option( '_bp_follow_general_settings', array() );

		$enable_member_following = isset( $settings['enable_member_following'] ) ? $settings['enable_member_following'] : true;
		$enable_author_following = isset( $settings['enable_author_following'] ) ? $settings['enable_author_following'] : true;
		$enable_taxonomy_following = isset( $settings['enable_taxonomy_following'] ) ? $settings['enable_taxonomy_following'] : true;
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Features', 'buddypress-followers' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="_bp_follow_general_settings[enable_member_following]" value="1" <?php checked( $enable_member_following, true ); ?>>
								<?php esc_html_e( 'Member Following', 'buddypress-followers' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Allow members to follow other members.', 'buddypress-followers' ); ?></p>

							<br>

							<label>
								<input type="checkbox" name="_bp_follow_general_settings[enable_author_following]" value="1" <?php checked( $enable_author_following, true ); ?>>
								<?php esc_html_e( 'Author Following', 'buddypress-followers' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Allow members to follow authors and get notified of new posts.', 'buddypress-followers' ); ?></p>

							<br>

							<label>
								<input type="checkbox" name="_bp_follow_general_settings[enable_taxonomy_following]" value="1" <?php checked( $enable_taxonomy_following, true ); ?>>
								<?php esc_html_e( 'Category/Tag Following', 'buddypress-followers' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Allow members to follow categories and tags.', 'buddypress-followers' ); ?></p>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
		<?php
	}

	/**
	 * Render Post Types tab.
	 */
	protected function render_post_types_tab() {
		settings_fields( 'bp_follow_post_types' );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$settings   = bp_get_option( '_bp_follow_post_types', array() );

		?>
		<table class="form-table bp-follow-post-types-table" role="presentation">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post Type', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Enabled', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Label', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Settings', 'buddypress-followers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $post_types as $post_type => $post_type_obj ) : ?>
					<?php
					$enabled        = isset( $settings[ $post_type ]['enabled'] ) ? $settings[ $post_type ]['enabled'] : false;
					$label          = isset( $settings[ $post_type ]['label'] ) ? $settings[ $post_type ]['label'] : $post_type_obj->labels->name;
					$instant_notify = isset( $settings[ $post_type ]['instant_notifications'] ) ? $settings[ $post_type ]['instant_notifications'] : true;
					$digest_notify  = isset( $settings[ $post_type ]['digest_notifications'] ) ? $settings[ $post_type ]['digest_notifications'] : true;
					$digest_mode    = isset( $settings[ $post_type ]['digest_mode'] ) ? $settings[ $post_type ]['digest_mode'] : 'combined';
					?>
					<tr class="bp-follow-post-type-row">
						<td>
							<strong><?php echo esc_html( $post_type_obj->labels->name ); ?></strong>
							<br>
							<code><?php echo esc_html( $post_type ); ?></code>
						</td>
						<td>
							<input type="checkbox" name="_bp_follow_post_types[<?php echo esc_attr( $post_type ); ?>][enabled]" value="1" <?php checked( $enabled, true ); ?>>
						</td>
						<td>
							<input type="text" name="_bp_follow_post_types[<?php echo esc_attr( $post_type ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text">
						</td>
						<td>
							<label>
								<input type="checkbox" name="_bp_follow_post_types[<?php echo esc_attr( $post_type ); ?>][instant_notifications]" value="1" <?php checked( $instant_notify, true ); ?>>
								<?php esc_html_e( 'Instant notifications', 'buddypress-followers' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="_bp_follow_post_types[<?php echo esc_attr( $post_type ); ?>][digest_notifications]" value="1" <?php checked( $digest_notify, true ); ?>>
								<?php esc_html_e( 'Digest notifications', 'buddypress-followers' ); ?>
							</label>
							<br><br>
							<label>
								<?php esc_html_e( 'Digest mode:', 'buddypress-followers' ); ?>
								<select name="_bp_follow_post_types[<?php echo esc_attr( $post_type ); ?>][digest_mode]">
									<option value="combined" <?php selected( $digest_mode, 'combined' ); ?>><?php esc_html_e( 'Combined', 'buddypress-followers' ); ?></option>
									<option value="separate" <?php selected( $digest_mode, 'separate' ); ?>><?php esc_html_e( 'Separate', 'buddypress-followers' ); ?></option>
									<option value="user_choice" <?php selected( $digest_mode, 'user_choice' ); ?>><?php esc_html_e( 'User Choice', 'buddypress-followers' ); ?></option>
								</select>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
		<?php
	}

	/**
	 * Render Taxonomies tab.
	 */
	protected function render_taxonomies_tab() {
		settings_fields( 'bp_follow_taxonomies' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$settings   = bp_get_option( '_bp_follow_taxonomies', array() );

		?>
		<table class="form-table bp-follow-taxonomies-table" role="presentation">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Taxonomy', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Enabled', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Label', 'buddypress-followers' ); ?></th>
					<th><?php esc_html_e( 'Settings', 'buddypress-followers' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $taxonomies as $taxonomy => $taxonomy_obj ) : ?>
					<?php
					$enabled        = isset( $settings[ $taxonomy ]['enabled'] ) ? $settings[ $taxonomy ]['enabled'] : false;
					$label          = isset( $settings[ $taxonomy ]['label'] ) ? $settings[ $taxonomy ]['label'] : $taxonomy_obj->labels->name;
					$instant_notify = isset( $settings[ $taxonomy ]['instant_notifications'] ) ? $settings[ $taxonomy ]['instant_notifications'] : true;
					$digest_notify  = isset( $settings[ $taxonomy ]['digest_notifications'] ) ? $settings[ $taxonomy ]['digest_notifications'] : true;
					?>
					<tr class="bp-follow-taxonomy-row">
						<td>
							<strong><?php echo esc_html( $taxonomy_obj->labels->name ); ?></strong>
							<br>
							<code><?php echo esc_html( $taxonomy ); ?></code>
						</td>
						<td>
							<input type="checkbox" name="_bp_follow_taxonomies[<?php echo esc_attr( $taxonomy ); ?>][enabled]" value="1" <?php checked( $enabled, true ); ?>>
						</td>
						<td>
							<input type="text" name="_bp_follow_taxonomies[<?php echo esc_attr( $taxonomy ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text">
						</td>
						<td>
							<label>
								<input type="checkbox" name="_bp_follow_taxonomies[<?php echo esc_attr( $taxonomy ); ?>][instant_notifications]" value="1" <?php checked( $instant_notify, true ); ?>>
								<?php esc_html_e( 'Instant notifications', 'buddypress-followers' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="_bp_follow_taxonomies[<?php echo esc_attr( $taxonomy ); ?>][digest_notifications]" value="1" <?php checked( $digest_notify, true ); ?>>
								<?php esc_html_e( 'Digest notifications', 'buddypress-followers' ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
		<?php
	}

	/**
	 * Render Notifications tab.
	 */
	protected function render_notifications_tab() {
		settings_fields( 'bp_follow_notifications' );
		$settings = bp_get_option( '_bp_follow_notification_settings', array() );

		$daily_digest_time  = isset( $settings['daily_digest_time'] ) ? $settings['daily_digest_time'] : '09:00';
		$weekly_digest_day  = isset( $settings['weekly_digest_day'] ) ? $settings['weekly_digest_day'] : 'monday';
		$weekly_digest_time = isset( $settings['weekly_digest_time'] ) ? $settings['weekly_digest_time'] : '09:00';
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Daily Digest Time', 'buddypress-followers' ); ?></th>
					<td>
						<input type="time" name="_bp_follow_notification_settings[daily_digest_time]" value="<?php echo esc_attr( $daily_digest_time ); ?>">
						<p class="description"><?php esc_html_e( 'Time to send daily digest emails.', 'buddypress-followers' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Weekly Digest Schedule', 'buddypress-followers' ); ?></th>
					<td>
						<select name="_bp_follow_notification_settings[weekly_digest_day]">
							<option value="monday" <?php selected( $weekly_digest_day, 'monday' ); ?>><?php esc_html_e( 'Monday', 'buddypress-followers' ); ?></option>
							<option value="tuesday" <?php selected( $weekly_digest_day, 'tuesday' ); ?>><?php esc_html_e( 'Tuesday', 'buddypress-followers' ); ?></option>
							<option value="wednesday" <?php selected( $weekly_digest_day, 'wednesday' ); ?>><?php esc_html_e( 'Wednesday', 'buddypress-followers' ); ?></option>
							<option value="thursday" <?php selected( $weekly_digest_day, 'thursday' ); ?>><?php esc_html_e( 'Thursday', 'buddypress-followers' ); ?></option>
							<option value="friday" <?php selected( $weekly_digest_day, 'friday' ); ?>><?php esc_html_e( 'Friday', 'buddypress-followers' ); ?></option>
							<option value="saturday" <?php selected( $weekly_digest_day, 'saturday' ); ?>><?php esc_html_e( 'Saturday', 'buddypress-followers' ); ?></option>
							<option value="sunday" <?php selected( $weekly_digest_day, 'sunday' ); ?>><?php esc_html_e( 'Sunday', 'buddypress-followers' ); ?></option>
						</select>
						<input type="time" name="_bp_follow_notification_settings[weekly_digest_time]" value="<?php echo esc_attr( $weekly_digest_time ); ?>">
						<p class="description"><?php esc_html_e( 'Day and time to send weekly digest emails.', 'buddypress-followers' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
		<?php
	}

	/**
	 * Render Advanced tab.
	 */
	protected function render_advanced_tab() {
		settings_fields( 'bp_follow_advanced' );
		$settings = bp_get_option( '_bp_follow_advanced_settings', array() );

		$queue_batch_size = isset( $settings['queue_batch_size'] ) ? $settings['queue_batch_size'] : 50;
		$cache_duration   = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 3600;
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Queue Batch Size', 'buddypress-followers' ); ?></th>
					<td>
						<input type="number" name="_bp_follow_advanced_settings[queue_batch_size]" value="<?php echo esc_attr( $queue_batch_size ); ?>" min="10" max="500">
						<p class="description"><?php esc_html_e( 'Number of notifications to process per batch.', 'buddypress-followers' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cache Duration', 'buddypress-followers' ); ?></th>
					<td>
						<input type="number" name="_bp_follow_advanced_settings[cache_duration]" value="<?php echo esc_attr( $cache_duration ); ?>" min="300" max="86400">
						<p class="description"><?php esc_html_e( 'Cache duration in seconds (300-86400).', 'buddypress-followers' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
		<?php
	}

	/**
	 * Sanitize post types settings.
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_post_types( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $post_type => $settings ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$sanitized[ $post_type ] = array(
				'enabled'               => ! empty( $settings['enabled'] ),
				'label'                 => sanitize_text_field( $settings['label'] ),
				'instant_notifications' => ! empty( $settings['instant_notifications'] ),
				'digest_notifications'  => ! empty( $settings['digest_notifications'] ),
				'digest_mode'           => in_array( $settings['digest_mode'], array( 'combined', 'separate', 'user_choice' ), true ) ? $settings['digest_mode'] : 'combined',
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize taxonomies settings.
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_taxonomies( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $input as $taxonomy => $settings ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$sanitized[ $taxonomy ] = array(
				'enabled'               => ! empty( $settings['enabled'] ),
				'label'                 => sanitize_text_field( $settings['label'] ),
				'instant_notifications' => ! empty( $settings['instant_notifications'] ),
				'digest_notifications'  => ! empty( $settings['digest_notifications'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize general settings.
	 *
	 * @since 2.1.0
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_general_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize notification settings.
	 *
	 * @since 2.1.0
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_notification_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $key => $value ) {
			if ( is_bool( $value ) || in_array( $value, array( '0', '1', 'true', 'false' ), true ) ) {
				$sanitized[ sanitize_key( $key ) ] = (bool) $value;
			} else {
				$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}
}

// Initialize admin settings.
if ( is_admin() ) {
	new BP_Follow_Admin_Settings();
}
