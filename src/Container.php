<?php
/**
 * Simple service container for BuddyPress Follow.
 *
 * @package BuddyPress-Followers
 */

namespace Followers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Very small dependency container.
 */
class Container {
	/**
	 * Stored services.
	 *
	 * @var array
	 */
	protected $services = array();

	/**
	 * Set a service.
	 *
	 * @param string $id Identifier.
	 * @param mixed  $service Service instance.
	 */
	public function set( $id, $service ) {
		$this->services[ $id ] = $service;
	}

	/**
	 * Check if a service exists.
	 *
	 * @param string $id Identifier.
	 * @return bool
	 */
	public function has( $id ) {
		return array_key_exists( $id, $this->services );
	}

	/**
	 * Retrieve a service.
	 *
	 * @param string   $id Identifier.
	 * @param callable $factory Optional factory to lazily create the service.
	 * @return mixed
	 */
	public function get( $id, $factory = null ) {
		if ( $this->has( $id ) ) {
			return $this->services[ $id ];
		}

		if ( null === $factory ) {
			return null;
		}

		$service = $factory();
		$this->set( $id, $service );

		return $service;
	}
}
