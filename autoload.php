<?php
/**
 * Simple PSR-4 autoloader for the BuddyPress Follow plugin.
 *
 * @package BuddyPress-Followers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Followers\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$relative = str_replace( '\\', '/', $relative );

		$file = constant( 'BP_FOLLOW_DIR' ) . '/src/' . $relative . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);
