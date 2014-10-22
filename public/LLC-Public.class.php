<?php
/**
 * Encapsulates the public functionality of the plugin aka the login checking.
 *
 * @since 0.7
 */
class LLC_Public {

	/** @var $instance LLC_Public Stores the single plugin object instance. */
	protected static $instance;

	/** @var $options Array Stores the plugin's options. */
	protected $options = array();

	/** @var $geoInfo geoiprecord Stores the visitor's geo information. */
	protected $geoInfo;


	/**
	 * Initialize the class, set its properties and register needed WordPress Hooks.
	 *
	 * @since 0.7
	 */
	protected function __construct() {

		// We add the authentication filter
		add_filter( 'wp_authenticate_user', array( $this, 'limit_login_countries' ), 31, 1 );

		add_filter( 'shake_error_codes', array( $this, 'add_shake_error' ) );

	}

	/**
	 * Returns and if necessary creates the plugin object instance
	 *
	 * @since 0.7
	 *
	 * @return Limit_Login_Countries
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * WP_Authenticate_User filter callback function. Here the magic is done: we stop authentication if needed.
	 * This filter is registred with priority 31. We can prevent login by returning a WP_Error object.
	 *
	 * @since 0.7
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_authenticate_user
	 *
	 * @param $user Mixed NULL: no authentication yet. WP_Error: another process has already failed the authentication. WP_User: another process has authenticated the user.
	 *
	 * @return Mixed We return WP_Error when the visitor's country is not allowed. In all other cases we pass on whatever we got via $user.
	 */
	public function limit_login_countries( $user ) {

		// In these cases we don't interceot but pass on what we got:
		//   - there already is an authentication error
		//   - we allow the current user to login from his IP
		if ( is_wp_error( $user ) or $this->is_allowed_country( $user ) ) {
			return $user;
		}

		// we are still here, so we complain about the user's country
		// translators: %s stands for the country name the user tries to login from
		$user = new WP_Error( 'invalid_country', sprintf( __( 'Login not allowed from your country (%s)!', 'limit-login-countries' ), __( $this->geoInfo->country_name, 'limit-login-countries' ) ) );

		return $user;
	}

	/**
	 * Checks whether visitor is allowed to login from his country.
	 *
	 * @since 0.7
	 *
	 * @param WP_User $user Used for logging (and maybe later
	 *                      for per user settings).
	 *
	 * @return bool Returns TRUE if visitor's country is allowed to login, FALSE if not.
	 */
	protected function is_allowed_country( WP_User $user = null ) {

		# set own error handler to gracefully handle errors triggered by geoip lookup
		set_error_handler(
			function ( $errno, $errstr, $errfile, $errline ) {
				// Having no whitespace here looks a bit messy but phpcs has an
				// issue with empty lines
				// https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/249
				if ( $errno & E_USER_ERROR ) {
					require_once( __DIR__ . '/../includes/LLC-Admin-Notice.class.php' );
					LLC_Admin_Notice::add_notice( $errstr, 'error' );
					error_log( "Fatal error: $errstr in $errfile on line $errline" );
					return true;
				} elseif ( $errno & ( E_WARNING | E_USER_WARNING ) ) {
					# we collect warnings too, so we won't need any @-operators.
					require_once( __DIR__ . '/../includes/LLC-Admin-Notice.class.php' );
					LLC_Admin_Notice::add_notice( $errstr, 'warning' );
					error_log( "Warning: $errstr in $errfile on line $errline" );
					return true;
				} elseif ( $errno & ( E_NOTICE | E_USER_NOTICE ) ) {
					# suppress notices usually preceding an E_USER_ERROR
					if ( error_reporting() & E_NOTICE ) {
						error_log( "Notice: $errstr in $errfile on line $errline" );
					}
					return true;
				}
				return false;
			}
		);

		$this->load_options();

		$allow_login = false;

		// check if override is active
		if ( defined( 'LIMIT_LOGIN_COUNTRIES_OVERRIDE' ) and true == LIMIT_LOGIN_COUNTRIES_OVERRIDE ) {
			$allow_login = true;
		}

		// check if plugin is deactivated
		if ( empty( $this->options['countryList'] ) ) {
			$allow_login = true;
		}

		// look up geo info
		if ( ! $this->geo_look_up() ) {
			$allow_login = true;

			// we log the unresolved IP address
			$log = get_option( 'llc_log_unresolved', array() );
			$log[ $_SERVER['REMOTE_ADDR'] ] += 1;
			update_option( 'llc_log_unresolved', $log );
		} else {
			$allow_login = ( $allow_login or $this->options['is_blacklist'] xor in_array( $this->geoInfo->country_code, $this->options['countryList'] ) );

			if ( ! $allow_login ) {
				// we log the forbidden country code
				$log = get_option( 'limit_login_countries_log', array() );
				$log[ $this->geoInfo->country_code ] += 1;
				update_option( 'limit_login_countries_log', $log );
			} else {
				// we log the allowed country code
				$log = get_option( 'llc_log_success', array() );
				$log[ $this->geoInfo->country_code ] += 1;
				update_option( 'llc_log_success', $log );

				//hook: wp_login
				// we keep track of each user's last login country to warn after locking someone out.
				update_user_meta( $user->ID, 'llc_last_login_country', $this->geoInfo->country_code );
			}
		}

		# stop own error handling
		restore_error_handler();

		return $allow_login;
	}

	/**
	 * Look up of visitor's geo information.
	 * Done seperately from __construct() because we only need it when authenticating.
	 *
	 * @since 0.7
	 *
	 * @return bool Returns TRUE on success and FALSE if there is no geo information available (e.g. localhost) or an error occurred.
	 */
	protected function geo_look_up() {

		// we check whether geo info is already loaded
		if ( ! is_object( $this->geoInfo ) ) {
			require_once( dirname( __DIR__ ) . '/includes/LLC-GeoIP-Tools.class.php' );
			$this->geoInfo = LLC_GeoIP_Tools::get_geo_info( $this->options['geoip_database'] );
		}

		// return false if no info was found (e.g. localhost) or there was an error
		return ! ( null === $this->geoInfo or false === $this->geoInfo or empty( $this->geoInfo->country_code ) );
	}

	/**
	 * Loads the plugin's options
	 *
	 * @since 0.7
	 *
	 * @return void
	 */
	public function load_options() {
		$this->options['geoip_database'] = get_option( 'llc_geoip_database_path' );
		// TODO: per user settings
		$this->options['is_blacklist'] = 'whitelist' === get_option( 'llc_blacklist', 'whitelist' ) ? false : true;
		$this->options['countryList']  = explode( ',', get_option( 'llc_countries' ) );
	}

	/**
	 * Enable shaking of the login form on 'invalid_country' error.
	 *
	 * @param $shake_error_codes array List of error codes for shaking the
	 *                           login form.
	 *
	 * @return array Extended list of error codes for shaking the login
	 *               form.
	 */
	public function add_shake_error( $shake_error_codes ) {
		$shake_error_codes[] = 'invalid_country';
		return $shake_error_codes;
	}
}
