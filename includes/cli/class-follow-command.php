<?php
/**
 * WP-CLI commands for BuddyPress Follow.
 *
 * @package BP-Follow
 * @subpackage CLI
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Manage BuddyPress Follow relationships via WP-CLI.
 *
 * ## EXAMPLES
 *
 *     # Follow a user
 *     $ wp bp follow start --leader=123 --follower=456
 *     Success: User 456 is now following user 123.
 *
 *     # Unfollow a user
 *     $ wp bp follow stop --leader=123 --follower=456
 *     Success: User 456 has unfollowed user 123.
 *
 *     # Get follower count
 *     $ wp bp follow count --user=123 --type=followers
 *     10
 *
 *     # List followers
 *     $ wp bp follow list --user=123 --type=followers
 *     +--------+----------+
 *     | ID     | Name     |
 *     +--------+----------+
 *     | 456    | John Doe |
 *     +--------+----------+
 *
 *     # Sync follow counts
 *     $ wp bp follow sync-counts
 *     Success: Synced follow counts for 100 users.
 *
 * @since 2.0.0
 */
class BP_Follow_CLI_Command extends WP_CLI_Command {

	/**
	 * Start following a user.
	 *
	 * Creates a new follow relationship between two users. The follower will
	 * start receiving updates from the leader in their activity stream.
	 *
	 * ## OPTIONS
	 *
	 * --leader=<user-id>
	 * : The user ID to follow.
	 *
	 * --follower=<user-id>
	 * : The user ID who will follow.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow start --leader=123 --follower=456
	 *
	 * @since 2.0.0
	 *
	 * @subcommand start
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with 'leader' and 'follower' user IDs.
	 */
	public function start( $args, $assoc_args ) {
		$leader_id   = isset( $assoc_args['leader'] ) ? (int) $assoc_args['leader'] : 0;
		$follower_id = isset( $assoc_args['follower'] ) ? (int) $assoc_args['follower'] : 0;

		if ( ! $leader_id || ! $follower_id ) {
			WP_CLI::error( 'Please provide both --leader and --follower user IDs.' );
		}

		// Check if users exist.
		if ( ! get_userdata( $leader_id ) ) {
			WP_CLI::error( "Leader user #{$leader_id} does not exist." );
		}

		if ( ! get_userdata( $follower_id ) ) {
			WP_CLI::error( "Follower user #{$follower_id} does not exist." );
		}

		// Check if already following.
		if ( bp_follow_is_following( array( 'leader_id' => $leader_id, 'follower_id' => $follower_id ) ) ) {
			WP_CLI::warning( "User #{$follower_id} is already following user #{$leader_id}." );
			return;
		}

		// Start following.
		$result = bp_follow_start_following( array(
			'leader_id'   => $leader_id,
			'follower_id' => $follower_id,
		) );

		if ( $result ) {
			WP_CLI::success( "User #{$follower_id} is now following user #{$leader_id}." );
		} else {
			WP_CLI::error( 'Failed to create follow relationship.' );
		}
	}

	/**
	 * Stop following a user.
	 *
	 * Removes an existing follow relationship between two users. The follower will
	 * stop receiving updates from the leader in their activity stream.
	 *
	 * ## OPTIONS
	 *
	 * --leader=<user-id>
	 * : The user ID to unfollow.
	 *
	 * --follower=<user-id>
	 * : The user ID who will unfollow.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow stop --leader=123 --follower=456
	 *
	 * @since 2.0.0
	 *
	 * @subcommand stop
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with 'leader' and 'follower' user IDs.
	 */
	public function stop( $args, $assoc_args ) {
		$leader_id   = isset( $assoc_args['leader'] ) ? (int) $assoc_args['leader'] : 0;
		$follower_id = isset( $assoc_args['follower'] ) ? (int) $assoc_args['follower'] : 0;

		if ( ! $leader_id || ! $follower_id ) {
			WP_CLI::error( 'Please provide both --leader and --follower user IDs.' );
		}

		// Check if following.
		if ( ! bp_follow_is_following( array( 'leader_id' => $leader_id, 'follower_id' => $follower_id ) ) ) {
			WP_CLI::warning( "User #{$follower_id} is not following user #{$leader_id}." );
			return;
		}

		// Stop following.
		$result = bp_follow_stop_following( array(
			'leader_id'   => $leader_id,
			'follower_id' => $follower_id,
		) );

		if ( $result ) {
			WP_CLI::success( "User #{$follower_id} has unfollowed user #{$leader_id}." );
		} else {
			WP_CLI::error( 'Failed to remove follow relationship.' );
		}
	}

	/**
	 * Get follower or following count for a user.
	 *
	 * Displays the number of followers a user has or the number of users
	 * they are following. Useful for quick checks or scripting.
	 *
	 * ## OPTIONS
	 *
	 * --user=<user-id>
	 * : The user ID.
	 *
	 * [--type=<type>]
	 * : Type of count: 'followers' or 'following'. Default: 'followers'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow count --user=123 --type=followers
	 *     wp bp follow count --user=123 --type=following
	 *
	 * @since 2.0.0
	 *
	 * @subcommand count
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with 'user' ID and optional 'type'.
	 */
	public function count( $args, $assoc_args ) {
		$user_id = isset( $assoc_args['user'] ) ? (int) $assoc_args['user'] : 0;
		$type    = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'followers';

		if ( ! $user_id ) {
			WP_CLI::error( 'Please provide a --user ID.' );
		}

		if ( ! get_userdata( $user_id ) ) {
			WP_CLI::error( "User #{$user_id} does not exist." );
		}

		if ( ! in_array( $type, array( 'followers', 'following' ), true ) ) {
			WP_CLI::error( 'Invalid type. Use "followers" or "following".' );
		}

		$counts = bp_follow_get_counts( array( 'user_id' => $user_id ) );
		WP_CLI::line( $counts[ $type ] );
	}

	/**
	 * List followers or following for a user.
	 *
	 * Displays a list of users who follow a user or users that a user is following.
	 * Supports multiple output formats including table, CSV, JSON, IDs-only, or count.
	 *
	 * ## OPTIONS
	 *
	 * --user=<user-id>
	 * : The user ID.
	 *
	 * [--type=<type>]
	 * : Type of list: 'followers' or 'following'. Default: 'followers'.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format. Default: table.
	 * ---
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow list --user=123 --type=followers
	 *     wp bp follow list --user=123 --type=following --format=json
	 *     wp bp follow list --user=123 --type=followers --format=ids
	 *
	 * @since 2.0.0
	 *
	 * @subcommand list
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with 'user' ID, optional 'type', and optional 'format'.
	 */
	public function list_follow( $args, $assoc_args ) {
		$user_id = isset( $assoc_args['user'] ) ? (int) $assoc_args['user'] : 0;
		$type    = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'followers';
		$format  = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( ! $user_id ) {
			WP_CLI::error( 'Please provide a --user ID.' );
		}

		if ( ! get_userdata( $user_id ) ) {
			WP_CLI::error( "User #{$user_id} does not exist." );
		}

		if ( ! in_array( $type, array( 'followers', 'following' ), true ) ) {
			WP_CLI::error( 'Invalid type. Use "followers" or "following".' );
		}

		// Get user IDs.
		$user_ids = 'followers' === $type
			? bp_follow_get_followers( array( 'user_id' => $user_id ) )
			: bp_follow_get_following( array( 'user_id' => $user_id ) );

		if ( empty( $user_ids ) ) {
			WP_CLI::log( "No {$type} found for user #{$user_id}." );
			return;
		}

		// Handle format.
		if ( 'ids' === $format ) {
			WP_CLI::line( implode( ' ', $user_ids ) );
			return;
		}

		if ( 'count' === $format ) {
			WP_CLI::line( count( $user_ids ) );
			return;
		}

		// Build user data array.
		$users = array();
		foreach ( $user_ids as $uid ) {
			$user = get_userdata( $uid );
			if ( $user ) {
				$users[] = array(
					'ID'           => $uid,
					'user_login'   => $user->user_login,
					'display_name' => $user->display_name,
					'user_email'   => $user->user_email,
				);
			}
		}

		WP_CLI\Utils\format_items( $format, $users, array( 'ID', 'user_login', 'display_name', 'user_email' ) );
	}

	/**
	 * Sync follower/following counts for all users.
	 *
	 * Recalculates and updates the cached follower and following counts stored
	 * in user meta. Useful after database changes or to fix count discrepancies.
	 * Shows a progress bar during execution.
	 *
	 * ## OPTIONS
	 *
	 * [--user=<user-id>]
	 * : Sync counts for a specific user. If not provided, syncs all users.
	 *
	 * [--dry-run]
	 * : Preview changes without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow sync-counts
	 *     wp bp follow sync-counts --user=123
	 *     wp bp follow sync-counts --dry-run
	 *
	 * @since 2.0.0
	 *
	 * @subcommand sync-counts
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with optional 'user' ID and 'dry-run' flag.
	 */
	public function sync_counts( $args, $assoc_args ) {
		global $wpdb;

		$user_id = isset( $assoc_args['user'] ) ? (int) $assoc_args['user'] : 0;
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::line( 'DRY RUN MODE - No changes will be saved.' );
		}

		$bp = buddypress();
		$table = $bp->follow->table_name;

		if ( $user_id ) {
			// Sync specific user.
			if ( ! get_userdata( $user_id ) ) {
				WP_CLI::error( "User #{$user_id} does not exist." );
			}

			$users = array( $user_id );
		} else {
			// Get all users who have follow relationships.
			$users = $wpdb->get_col( "SELECT DISTINCT leader_id FROM {$table} UNION SELECT DISTINCT follower_id FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$count = 0;
		$progress = \WP_CLI\Utils\make_progress_bar( 'Syncing counts', count( $users ) );

		foreach ( $users as $uid ) {
			$followers_count = count( bp_follow_get_followers( array( 'user_id' => $uid ) ) );
			$following_count = count( bp_follow_get_following( array( 'user_id' => $uid ) ) );

			if ( ! $dry_run ) {
				bp_update_user_meta( $uid, 'bp_follow_followers_count', $followers_count );
				bp_update_user_meta( $uid, 'bp_follow_following_count', $following_count );
			}

			$count++;
			$progress->tick();
		}

		$progress->finish();

		if ( $dry_run ) {
			WP_CLI::success( "Would sync counts for {$count} users." );
		} else {
			WP_CLI::success( "Synced follow counts for {$count} users." );
		}
	}

	/**
	 * Delete all follow relationships (dangerous).
	 *
	 * Permanently removes all follow relationships from the database and clears
	 * all related user meta. This action cannot be undone. Use with extreme caution.
	 * Requires confirmation unless --yes flag is provided.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow delete-all --yes
	 *
	 * @since 2.0.0
	 *
	 * @subcommand delete-all
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments with optional 'yes' confirmation flag.
	 */
	public function delete_all( $args, $assoc_args ) {
		global $wpdb;

		WP_CLI::confirm( 'This will delete ALL follow relationships. Are you sure?', $assoc_args );

		$bp = buddypress();
		$table = $bp->follow->table_name;

		$count = $wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $count ) {
			WP_CLI::error( 'Failed to delete follow relationships.' );
		}

		// Clear all user meta.
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bp_follow_%'" );

		// Clear cache.
		wp_cache_flush();

		WP_CLI::success( "Deleted {$count} follow relationships." );
	}

	/**
	 * Get statistics about follow relationships.
	 *
	 * Displays comprehensive statistics about the follow system, including total
	 * relationships, number of active users, and users with the most followers/following.
	 * Useful for understanding community engagement and network effects.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow stats
	 *
	 * @since 2.0.0
	 *
	 * @subcommand stats
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments (unused).
	 */
	public function stats( $args, $assoc_args ) {
		global $wpdb;

		$bp = buddypress();
		$table = $bp->follow->table_name;

		// Total relationships.
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Total users with followers.
		$users_with_followers = $wpdb->get_var( "SELECT COUNT(DISTINCT leader_id) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Total users following others.
		$users_following = $wpdb->get_var( "SELECT COUNT(DISTINCT follower_id) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// User with most followers.
		$most_followers = $wpdb->get_row( "SELECT leader_id as user_id, COUNT(*) as count FROM {$table} GROUP BY leader_id ORDER BY count DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// User following the most people.
		$most_following = $wpdb->get_row( "SELECT follower_id as user_id, COUNT(*) as count FROM {$table} GROUP BY follower_id ORDER BY count DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		WP_CLI::line( '' );
		WP_CLI::line( 'Follow Statistics:' );
		WP_CLI::line( '==================' );
		WP_CLI::line( "Total follow relationships: {$total}" );
		WP_CLI::line( "Users with followers: {$users_with_followers}" );
		WP_CLI::line( "Users following others: {$users_following}" );

		if ( $most_followers ) {
			$user = get_userdata( $most_followers->user_id );
			$name = $user ? $user->display_name : 'Unknown';
			WP_CLI::line( "Most followers: {$name} (#{$most_followers->user_id}) with {$most_followers->count} followers" );
		}

		if ( $most_following ) {
			$user = get_userdata( $most_following->user_id );
			$name = $user ? $user->display_name : 'Unknown';
			WP_CLI::line( "Following the most: {$name} (#{$most_following->user_id}) following {$most_following->count} users" );
		}

		WP_CLI::line( '' );
	}

	/**
	 * Send pending digest emails manually.
	 *
	 * Processes all queued digest notifications and sends emails to users.
	 * Normally handled by cron, but useful for testing or manual execution.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bp follow send-digests
	 *
	 * @since 2.0.0
	 *
	 * @subcommand send-digests
	 *
	 * @param array $args         Positional arguments (unused).
	 * @param array $assoc_args   Associative arguments (unused).
	 */
	public function send_digests( $args, $assoc_args ) {
		$service = bp_follow_service( '\\Followers\\Service\\DigestService' );

		if ( ! $service ) {
			WP_CLI::error( 'DigestService not available.' );
			return;
		}

		WP_CLI::line( 'Processing digest emails...' );
		$sent = $service->process_all_digests();
		WP_CLI::success( sprintf( 'Sent %d digest emails.', $sent ) );
	}
}
