<?php
/*
Plugin Name: BuddyPress Follow
Plugin URI: http://wordpress.org/extend/plugins/buddypress-followers
Description: Follow members on your BuddyPress site with this nifty plugin.
Version: 2.0.0
Author: Andy Peatling, r-a-y, vapvarun
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: buddypress-followers
Domain Path: /languages
*/

/**
 * BuddyPress Follow bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin paths.
define( 'BP_FOLLOW_DIR', dirname( __FILE__ ) );
define( 'BP_FOLLOW_URL', plugins_url( basename( BP_FOLLOW_DIR ) ) . '/' );

/**
 * Load the plugin only when the required BuddyPress version is present.
 */
function bp_follow_init() {
	if ( ! defined( 'BP_VERSION' ) || version_compare( BP_VERSION, '14.4.0', '<' ) ) {
		add_action( 'admin_notices', 'bp_follow_version_notice' );
		return;
	}

	require_once BP_FOLLOW_DIR . '/autoload.php';

	add_action( 'bp_loaded', 'bp_follow_register_component', 5 );

	// Register WP-CLI commands.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once BP_FOLLOW_DIR . '/includes/cli/class-follow-command.php';
		WP_CLI::add_command( 'bp follow', 'BP_Follow_CLI_Command' );
	}
}
add_action( 'bp_include', 'bp_follow_init' );

/**
 * Load translations on init hook to comply with WordPress 6.7+.
 */
add_action( 'init', 'bp_follow_localization' );

/**
 * Display admin notice for BuddyPress version requirement.
 */
function bp_follow_version_notice() {
	echo '<div class="error"><p>' . esc_html__( 'BuddyPress Follow requires BuddyPress 14.4.0 or newer.', 'buddypress-followers' ) . '</p></div>';
}

/**
 * Instantiate the Follow component and register it with BuddyPress.
 */
function bp_follow_register_component() {
	global $bp;

	if ( ! class_exists( '\\Followers\\Component' ) ) {
		require_once BP_FOLLOW_DIR . '/src/Component.php';
	}

	if ( ! class_exists( 'BP_Follow_Component', false ) ) {
		class_alias( '\\Followers\\Component', 'BP_Follow_Component' );
	}

	if ( isset( $bp->follow ) && $bp->follow instanceof \Followers\Component ) {
		return;
	}

	$bp->follow = new \Followers\Component();

	do_action( 'bp_follow_loaded' );
}

/**
 * Custom textdomain loader.
 *
 * @since 1.1.0
 *
 * @return bool True if textdomain loaded; false if not.
 */
function bp_follow_localization() {
	static $loaded = false;

	if ( $loaded ) {
		return true;
	}

	$domain        = 'buddypress-followers';
	$mofile_custom = trailingslashit( WP_LANG_DIR ) . sprintf( '%s-%s.mo', $domain, get_locale() );

	if ( is_readable( $mofile_custom ) ) {
		$loaded = load_textdomain( $domain, $mofile_custom );
		return $loaded;
	}

	$loaded = load_plugin_textdomain( $domain, false, basename( BP_FOLLOW_DIR ) . '/languages/' );
	return $loaded;
}
