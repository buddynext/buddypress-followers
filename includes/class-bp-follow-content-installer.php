<?php
/**
 * BP Follow Content Following Database Installer
 *
 * @package BP-Follow
 * @subpackage Installer
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Content Following Database Installer.
 *
 * Handles creation of database tables for the Blog Content Following System.
 * This installer creates 5 new tables to support following authors, categories, and tags.
 *
 * @since 2.1.0
 */
class BP_Follow_Content_Installer {

	/**
	 * Database version for content following feature.
	 *
	 * @var string
	 */
	const DB_VERSION = '2.1.0';

	/**
	 * Run the installer.
	 *
	 * Creates all necessary database tables for content following functionality.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function install() {
		global $wpdb;

		$bp = $GLOBALS['bp'];

		$charset_collate = ! empty( $wpdb->charset )
			? "DEFAULT CHARACTER SET $wpdb->charset"
			: '';

		$table_prefix = $bp->table_prefix;
		if ( ! $table_prefix ) {
			$table_prefix = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
		}

		$sql = array();

		// Table 1: Follow Counts Cache
		// Stores cached counts for quick retrieval
		$sql[] = "CREATE TABLE {$table_prefix}bp_follow_counts (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			object_id bigint(20) NOT NULL,
			object_type varchar(75) NOT NULL,
			follower_count bigint(20) NOT NULL DEFAULT 0,
			following_count bigint(20) NOT NULL DEFAULT 0,
			last_updated datetime NOT NULL default '0000-00-00 00:00:00',
			KEY object_lookup (object_id, object_type),
			KEY last_updated (last_updated)
		) {$charset_collate};";

		// Table 2: Content Metadata
		// Stores additional metadata for content follows (preferences, settings)
		$sql[] = "CREATE TABLE {$table_prefix}bp_follow_content_meta (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			follow_id bigint(20) NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			KEY follow_id (follow_id),
			KEY meta_key (meta_key(191))
		) {$charset_collate};";

		// Table 3: Notification Queue
		// Queues notifications for batch processing to improve performance
		$sql[] = "CREATE TABLE {$table_prefix}bp_follow_notification_queue (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id bigint(20) NOT NULL,
			item_id bigint(20) NOT NULL,
			item_type varchar(75) NOT NULL,
			notification_type varchar(75) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			priority int(11) NOT NULL DEFAULT 5,
			retry_count int(11) NOT NULL DEFAULT 0,
			scheduled_time datetime NOT NULL default '0000-00-00 00:00:00',
			created_time datetime NOT NULL default '0000-00-00 00:00:00',
			processed_time datetime DEFAULT NULL,
			KEY user_id (user_id),
			KEY status (status),
			KEY scheduled_time (scheduled_time),
			KEY notification_type (notification_type)
		) {$charset_collate};";

		// Table 4: Trending Content
		// Tracks trending authors, categories, tags based on follow activity
		$sql[] = "CREATE TABLE {$table_prefix}bp_follow_trending (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			object_id bigint(20) NOT NULL,
			object_type varchar(75) NOT NULL,
			trend_score decimal(10,2) NOT NULL DEFAULT 0.00,
			time_period varchar(20) NOT NULL DEFAULT 'daily',
			calculation_date date NOT NULL,
			KEY object_lookup (object_id, object_type),
			KEY time_period (time_period),
			KEY calculation_date (calculation_date),
			KEY trend_score (trend_score)
		) {$charset_collate};";

		// Table 5: Digest Preferences
		// Stores per-user digest preferences for content follows
		$sql[] = "CREATE TABLE {$table_prefix}bp_follow_digest_prefs (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id bigint(20) NOT NULL,
			post_type varchar(75) NOT NULL,
			digest_enabled tinyint(1) NOT NULL DEFAULT 1,
			digest_frequency varchar(20) NOT NULL DEFAULT 'daily',
			digest_mode varchar(20) NOT NULL DEFAULT 'combined',
			last_digest_sent datetime DEFAULT NULL,
			created_time datetime NOT NULL default '0000-00-00 00:00:00',
			updated_time datetime NOT NULL default '0000-00-00 00:00:00',
			UNIQUE KEY user_post_type (user_id, post_type),
			KEY user_id (user_id),
			KEY digest_frequency (digest_frequency)
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Set database version.
		bp_update_option( '_bp_follow_content_db_version', self::DB_VERSION );

		// Set installation timestamp.
		bp_update_option( '_bp_follow_content_installed', current_time( 'mysql' ) );

		return true;
	}

	/**
	 * Check if content following tables are installed.
	 *
	 * @return bool True if installed, false otherwise.
	 */
	public static function is_installed() {
		$db_version = bp_get_option( '_bp_follow_content_db_version', '' );
		return ! empty( $db_version );
	}

	/**
	 * Get current database version.
	 *
	 * @return string Database version or empty string.
	 */
	public static function get_db_version() {
		return bp_get_option( '_bp_follow_content_db_version', '' );
	}

	/**
	 * Check if upgrade is needed.
	 *
	 * @return bool True if upgrade needed, false otherwise.
	 */
	public static function needs_upgrade() {
		$current_version = self::get_db_version();

		if ( empty( $current_version ) ) {
			return true;
		}

		return version_compare( $current_version, self::DB_VERSION, '<' );
	}

	/**
	 * Drop all content following tables.
	 *
	 * WARNING: This will delete all data. Use with caution.
	 *
	 * @return bool True on success.
	 */
	public static function uninstall() {
		global $wpdb;

		$bp = $GLOBALS['bp'];

		$table_prefix = $bp->table_prefix;
		if ( ! $table_prefix ) {
			$table_prefix = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
		}

		$tables = array(
			"{$table_prefix}bp_follow_counts",
			"{$table_prefix}bp_follow_content_meta",
			"{$table_prefix}bp_follow_notification_queue",
			"{$table_prefix}bp_follow_trending",
			"{$table_prefix}bp_follow_digest_prefs",
		);

		foreach ( $tables as $table ) {
   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Delete options.
		bp_delete_option( '_bp_follow_content_db_version' );
		bp_delete_option( '_bp_follow_content_installed' );
		bp_delete_option( '_bp_follow_post_types' );
		bp_delete_option( '_bp_follow_taxonomies' );

		return true;
	}
}
