<?php
/**
 * BuddyPress Follow Component (modernized).
 *
 * @package BuddyPress-Followers
 */

namespace Followers;

use BP_Component;
use Followers\Container;
use Followers\Service\FollowService;
use Followers\Service\NotificationService;
use Followers\Service\AjaxService;
use Followers\Service\EmailService;
use Followers\Service\ButtonService;
use Followers\REST\FollowController;
use stdClass;
use function add_action;
use function apply_filters;
use function bp_is_action_variable;
use function bp_is_active;
use function bp_is_current_action;
use function bp_is_current_component;
use function has_action;
use function is_admin;
use function bp_is_root_blog;
use function constant;
use function defined;
use function is_user_logged_in;
use function wp_cache_add_global_groups;
use function wp_doing_ajax;
use function wp_enqueue_script;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core component class for BuddyPress Follow.
 */
class Component extends BP_Component {
	/**
	 * Revision Date.
	 *
	 * @var string
	 */
	public $revision_date = '2014-08-07 22:00 UTC';

	/**
	 * Component parameters.
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * Updater instance.
	 *
	 * @var object|null
	 */
	public $updater;

	/**
	 * Follow Activity handler.
	 *
	 * @var object|null
	 */
	public $activity;

	/**
	 * Follow Blogs handler.
	 *
	 * @var object|null
	 */
	public $blogs;

	/**
	 * Global cache groups.
	 *
	 * @var array
	 */
	public $global_cachegroups = array();

	/**
	 * Followers data container.
	 *
	 * @var stdClass
	 */
	public $followers;

	/**
	 * Following data container.
	 *
	 * @var stdClass
	 */
	public $following;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	public $table_name = '';

	/**
	 * Activity scope flag.
	 *
	 * @var int
	 */
	public $activity_scope_set = 0;

	/**
	 * Service container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$bp = $GLOBALS['bp'];

		$this->container = new Container();
		$this->register_services();
		$this->params    = array(
			'adminbar_myaccount_order' => apply_filters( 'bp_follow_following_nav_position', 61 ),
		);

		// Use non-translatable string initially to avoid WordPress 6.7+ early translation warning
		parent::start(
			'follow',
			'Follow',
			constant( 'BP_FOLLOW_DIR' ) . '/includes',
			$this->params
		);

		// Add translation filter on init when textdomain is loaded
		add_action( 'init', array( $this, 'translate_component_name' ), 11 );

		$this->includes();
		$this->setup_hooks();

		$bp->active_components[ $this->id ] = '1';
	}

	/**
	 * Translate component name after textdomain is loaded.
	 *
	 * @since 1.3.0
	 */
	public function translate_component_name() {
		$this->name = __( 'Follow', 'buddypress-followers' );
	}

	/**
	 * Register core services with the container.
	 */
	protected function register_services() {
		$follow_service = new FollowService();
		$this->container->set( FollowService::class, $follow_service );
		$this->container->set( NotificationService::class, new NotificationService() );
		$this->container->set( AjaxService::class, new AjaxService() );
		$this->container->set( EmailService::class, new EmailService() );
		$this->container->set( ButtonService::class, new ButtonService() );
		$this->container->set( FollowController::class, new FollowController( $follow_service ) );
	}

	/**
	 * Includes component files.
	 *
	 * @param array $includes Included files.
	 */
	public function includes( $includes = array() ) {
		require $this->path . '/class-follow.php';
		require $this->path . '/functions.php';
		require $this->path . '/blocks.php';

		if ( true === (bool) apply_filters( 'bp_follow_enable_users', true ) ) {
			require $this->path . '/user/hooks.php';
			require $this->path . '/user/template.php';
			require $this->path . '/user/notifications.php';
			require $this->path . '/user/widgets.php';
			require $this->path . '/user/screens.php';

			add_action( 'bp_init', array( $this, 'load_user_runtime_includes' ) );
		}

		if ( bp_is_active( 'activity' ) ) {
			require $this->path . '/activity/class-activity.php';
		}

		if ( is_admin() ) {
			require_once $this->path . '/class-updater.php';
		}
	}

	/**
	 * Load runtime-only user files (actions/screens/AJAX).
	 */
	public function load_user_runtime_includes() {
		$path = $this->path . '/user';

		if ( wp_doing_ajax() && isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'follow' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			require_once $path . '/ajax.php';
		}

		if ( bp_is_current_component( $this->followers->slug ) || bp_is_current_component( $this->following->slug ) || bp_is_action_variable( 'feed', 0 ) ) {
			require_once $path . '/actions.php';
		}
	}

	/**
	 * Setup globals.
	 *
	 * @param array $args Args.
	 */
	public function setup_globals( $args = array() ) {
		if ( ! defined( 'BP_FOLLOWERS_SLUG' ) ) {
			define( 'BP_FOLLOWERS_SLUG', 'followers' );
		}

		if ( ! defined( 'BP_FOLLOWING_SLUG' ) ) {
			define( 'BP_FOLLOWING_SLUG', 'following' );
		}

		$bp = $GLOBALS['bp'];

		$this->global_cachegroups = array( 'bp_follow_data' );
		$this->followers         = new stdClass();
		$this->following         = new stdClass();
		$this->followers->slug   = constant( 'BP_FOLLOWERS_SLUG' );
		$this->following->slug   = constant( 'BP_FOLLOWING_SLUG' );

		parent::setup_globals( array(
			'notification_callback' => 'bp_follow_format_notifications',
			'global_tables'         => array(
				'table_name' => $bp->table_prefix . 'bp_follow',
			),
		) );
	}

	/**
	 * Setup navigation.
	 *
	 * @param array $main_nav Main navigation items.
	 * @param array $sub_nav Sub navigation items.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Call parent setup_nav first.
		parent::setup_nav( $main_nav, $sub_nav );

		// Trigger the setup_nav action for modules to hook into.
		do_action( 'bp_follow_setup_nav', $main_nav, $sub_nav );
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks() {
		add_action( 'bp_init', array( $this, 'register_global_cachegroups' ), 5 );
		add_action( 'bp_init', array( $this, 'register_notification_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register cache groups.
	 */
	public function register_global_cachegroups() {
		wp_cache_add_global_groups( (array) $this->global_cachegroups );
	}

	/**
	 * Register notification settings.
	 */
	public function register_notification_settings() {
		if ( has_action( 'bp_follow_screen_notification_settings' ) ) {
			add_action( 'bp_notification_settings', 'bp_follow_notification_settings_content' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! bp_is_root_blog() ) {
			return;
		}

		wp_enqueue_script( 'bp-follow-js', constant( 'BP_FOLLOW_URL' ) . 'assets/js/follow.js', array( 'jquery' ), strtotime( $this->revision_date ), true );
	}

	/**
	 * Retrieve the component container.
	 *
	 * @return Container
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Retrieve a service from the container.
	 *
	 * @param string        $id      Service identifier.
	 * @param callable|null $factory Optional factory.
	 * @return mixed
	 */
	public function service( $id, $factory = null ) {
		return $this->container->get( $id, $factory );
	}

	/**
	 * Register REST API routes.
	 *
	 * Supports both BP REST API v1 (BP < 15.0) and v2 (BP >= 15.0).
	 */
	public function register_rest_routes() {
		// Bail if BP REST API is not available.
		if ( ! function_exists( 'bp_rest_namespace' ) && ! defined( 'BP_REST_API_VERSION' ) ) {
			return;
		}

		$controller = $this->service( FollowController::class );

		if ( $controller && method_exists( $controller, 'register_routes' ) ) {
			$controller->register_routes();
		}
	}

}
