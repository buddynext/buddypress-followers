<?php
/**
 * Follow REST controller.
 *
 * @package BuddyPress-Followers
 */

namespace Followers\REST;

use Followers\Service\FollowService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function bp_rest_namespace;
use function bp_rest_version;
use function get_current_user_id;
use function is_numeric;
use function is_user_logged_in;
use function register_rest_route;
use function rest_ensure_response;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller exposing follow operations.
 */
class FollowController extends WP_REST_Controller {
	/**
	 * Follow service instance.
	 *
	 * @var FollowService
	 */
	protected $follow_service;

	/**
	 * Constructor.
	 *
	 * @param FollowService $follow_service Follow service dependency.
	 */
	public function __construct( FollowService $follow_service ) {
		// Support both BP REST API v1 and v2.
		$namespace = function_exists( 'bp_rest_namespace' ) ? bp_rest_namespace() : 'buddypress';
		$version   = function_exists( 'bp_rest_version' ) ? bp_rest_version() : 'v1';

		$this->namespace      = $namespace . '/' . $version;
		$this->rest_base      = 'follow';
		$this->follow_service = $follow_service;
	}

	/**
	 * Register routes with the REST API.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>\\d+)/followers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_followers' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_user_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>\\d+)/following',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_following' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_user_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>\\d+)/counts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_counts' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_user_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<leader_id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_follow' ),
					'permission_callback' => array( $this, 'ensure_logged_in' ),
					'args'                => $this->get_follow_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_follow' ),
					'permission_callback' => array( $this, 'ensure_logged_in' ),
					'args'                => $this->get_follow_args(),
				),
			)
		);
	}

	/**
	 * Fetch followers for a user.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_followers( WP_REST_Request $request ) {
		$user_id = (int) $request['user_id'];

		$followers = $this->follow_service->get_followers( array(
			'user_id' => $user_id,
		) );

		return rest_ensure_response( array(
			'followers' => array_map( 'intval', $followers ),
		) );
	}

	/**
	 * Fetch following ids for a user.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_following( WP_REST_Request $request ) {
		$user_id = (int) $request['user_id'];

		$following = $this->follow_service->get_following( array(
			'user_id' => $user_id,
		) );

		return rest_ensure_response( array(
			'following' => array_map( 'intval', $following ),
		) );
	}

	/**
	 * Fetch follower/following counts.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_counts( WP_REST_Request $request ) {
		$user_id = (int) $request['user_id'];

		$counts = $this->follow_service->get_counts( array(
			'user_id' => $user_id,
		) );

		return rest_ensure_response( $counts );
	}

	/**
	 * Follow a user.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_follow( WP_REST_Request $request ) {
		$leader_id   = (int) $request['leader_id'];
		$follower_id = get_current_user_id();

		if ( $leader_id === $follower_id ) {
			return new WP_Error( 'bp_follow_self', __( 'You cannot follow yourself.', 'buddypress-followers' ), array( 'status' => 400 ) );
		}

		$created = $this->follow_service->follow( array(
			'leader_id'   => $leader_id,
			'follower_id' => $follower_id,
		) );

		if ( ! $created ) {
			return new WP_Error( 'bp_follow_failed', __( 'Unable to follow this user.', 'buddypress-followers' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Unfollow a user.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_follow( WP_REST_Request $request ) {
		$leader_id   = (int) $request['leader_id'];
		$follower_id = get_current_user_id();

		$deleted = $this->follow_service->unfollow( array(
			'leader_id'   => $leader_id,
			'follower_id' => $follower_id,
		) );

		if ( ! $deleted ) {
			return new WP_Error( 'bp_unfollow_failed', __( 'Unable to unfollow this user.', 'buddypress-followers' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Ensure the request is made by a logged-in user.
	 *
	 * @return bool|WP_Error
	 */
	public function ensure_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'bp_follow_logged_out', __( 'You must be logged in to perform this action.', 'buddypress-followers' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Argument schema for user-based requests.
	 *
	 * @return array
	 */
	protected function get_user_args() {
		return array(
			'user_id' => array(
				'required'          => true,
				'validate_callback' => 'is_numeric',
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Argument schema for follow/unfollow requests.
	 *
	 * @return array
	 */
	protected function get_follow_args() {
		return array(
			'leader_id' => array(
				'required'          => true,
				'validate_callback' => 'is_numeric',
				'sanitize_callback' => 'absint',
			),
		);
	}
}
