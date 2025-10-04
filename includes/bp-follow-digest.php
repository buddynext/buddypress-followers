<?php
/**
 * BP Follow Digest Functions
 *
 * @package BP-Follow
 * @subpackage Digest
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add custom cron schedules for digest emails.
 *
 * @since 2.0.0
 *
 * @param array $schedules Existing cron schedules.
 * @return array Modified schedules.
 */
function bp_follow_add_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = array(
			'interval' => 604800, // 7 days in seconds.
			'display'  => __( 'Once Weekly', 'buddypress-followers' ),
		);
	}
	return $schedules;
}
add_filter( 'cron_schedules', 'bp_follow_add_cron_schedules' );

/**
 * Schedule digest cron jobs.
 *
 * @since 2.0.0
 */
function bp_follow_schedule_digest_cron() {
	// Schedule daily digest processing.
	if ( ! wp_next_scheduled( 'bp_follow_send_daily_digests' ) ) {
		wp_schedule_event( strtotime( 'tomorrow 9:00am' ), 'daily', 'bp_follow_send_daily_digests' );
	}

	// Schedule weekly digest processing (every Monday at 9am).
	if ( ! wp_next_scheduled( 'bp_follow_send_weekly_digests' ) ) {
		wp_schedule_event( strtotime( 'next Monday 9:00am' ), 'weekly', 'bp_follow_send_weekly_digests' );
	}
}
add_action( 'bp_init', 'bp_follow_schedule_digest_cron' );

/**
 * Send daily digests.
 *
 * @since 2.0.0
 */
function bp_follow_send_daily_digests() {
	$service = bp_follow_service( '\\Followers\\Service\\DigestService' );

	if ( ! $service ) {
		return;
	}

	$sent = $service->process_all_digests();

	// Log digest sending for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( 'BP Follow: Sent %d daily digest emails', $sent ) );
	}
}
add_action( 'bp_follow_send_daily_digests', 'bp_follow_send_daily_digests' );

/**
 * Send weekly digests.
 *
 * @since 2.0.0
 */
function bp_follow_send_weekly_digests() {
	// Same handler as daily, but triggered weekly.
	bp_follow_send_daily_digests();
}
add_action( 'bp_follow_send_weekly_digests', 'bp_follow_send_weekly_digests' );

/**
 * WP-CLI command to manually send digests.
 *
 * @since 2.0.0
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Send pending digest emails.
	 *
	 * @since 2.0.0
	 */
	function bp_follow_cli_send_digests() {
		$service = bp_follow_service( '\\Followers\\Service\\DigestService' );

		if ( ! $service ) {
			WP_CLI::error( 'DigestService not available.' );
			return;
		}

		WP_CLI::line( 'Processing digest emails...' );
		$sent = $service->process_all_digests();
		WP_CLI::success( sprintf( 'Sent %d digest emails.', $sent ) );
	}

	WP_CLI::add_command( 'bp follow send-digests', 'bp_follow_cli_send_digests' );
}
