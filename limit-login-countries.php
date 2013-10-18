<?php
/*
Plugin Name: Limit Login Countries
Plugin URI: http://wordpress.org/extend/plugins/limit-login-countries/
Description: This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in.
Version: 0.5
Text Domain: limit-login-countries
Author: Dirk Weise
Author URI: http://dirk-weise.de/

License: GPLv2 or later
*/

Limit_Login_Countries::get_instance();

/**
 * Encapsulates Limit_Login_Countries functionality.
 * To keep the global namespace clean this plugin's main class is implemented as a singleton.
 *
 * @since 0.1
 */
class Limit_Login_Countries {

	/** @var $instance Limit_Login_Countries Stores the single plugin object instance. */
	protected static $instance;

	/** @var $options Array Stores the plugin's options. */
	protected $options = array();

	/** @var $geoInfo llc_geoInfo Stores the visitor's geo information. */
	protected $geoInfo;

	/**
	 * Constructor of plugin class.
	 * Uses singleton pattern. There is no reason for more than one instance of the plugin and we don't pollute the
	 * global namespace. Use Limit_Login_Countries::get_instance() if the plugin object needs to be accessed.
	 *
	 * @since 0.1
	 */
	protected function __construct() {

		// we add an init hook for loading options and our textdomain for l10n
		add_action( 'init', array($this, 'loadOptions') );

		// We add the authentication filter
		add_filter( 'wp_authenticate_user', array($this, 'limit_login_countries'), 31, 1 );

		// we set up everything needed for our settings page
		require_once(__DIR__ . '/includes/LLC_Options_Page.class.php');
		LLC_OPTIONS_PAGE::init();
	}

	/**
	 * Returns and if necessary creates the plugin object instance
	 *
	 * @since 0.2
	 *
	 * @return Limit_Login_Countries
	 */
	public static function get_instance() {
		if( NULL === self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Loads the plugin's options
	 *
	 * @since 0.2
	 *
	 * @return void
	 */
	public function loadOptions() {

		$this->options['plugindir'] = plugin_dir_path(__FILE__);

		$this->options['geoip_database'] = get_option('llc_geoip_database_path');
		$this->options['blacklist'] = 'whitelist' === get_option('llc_blacklist', 'whitelist') ? FALSE : TRUE;
		$this->options['countryList'] = explode(',', get_option('llc_countries'));

		load_plugin_textdomain('limit-login-countries', FALSE, basename(__DIR__).'/l10n/');

	}

	/**
	 * Look up of visitor's geo information.
	 * Done seperately from __construct() because we only need it when authenticating.
	 *
	 * @since 0.2
	 *
	 * @return bool Returns TRUE on success and FALSE if there is no geo information available (e.g. localhost) or an error occurred.
	 */
	protected function geoLookUp() {

		// we check whether geo info is already loaded
		if( !is_object($this->geoInfo) ) {
			require_once(__DIR__ . '/includes/LLC_GeoIP_Tools.class.php');
			$this->geoInfo = LLC_GeoIP_Tools::getGeoInfo($this->options['geoip_database']);
		}

		// return false if no info was found (e.g. localhost) or there was an error
		return (NULL === $this->geoInfo OR FALSE === $this->geoInfo) ? FALSE : TRUE;
	}

	/**
	 * Checks whether visitor is allowed to login from his country.
	 *
	 * @since 0.1
	 *
	 * @return bool Returns TRUE if visitor's country is allowed to login, FALSE if not.
	 */
	protected function isAllowedCountry() {

		return $this->options['blacklist'] xor in_array($this->geoInfo->country_code, $this->options['countryList']);
	}

	/**
	 * WP_Authenticate_User filter callback function. Here the magic is done: we stop authentication if needed.
	 * This filter is registred with priority 31. We can prevent login by returning a WP_Error object.
	 *
	 * @since 0.1
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_authenticate_user
	 *
	 * @param $user Mixed NULL: no authentication yet. WP_Error: another process has already failed the authentication. WP_User: another process has authenticated the user.
	 *
	 * @return Mixed We return WP_Error when the visitor's country is not allowed. In all other cases we pass on whatever we got via $user.
	 */
	public function limit_login_countries($user) {

		// In these cases we don't throw an error, but pass on what we got:
		if(
			is_wp_error($user)							// there already is an authentication error
			or !$this->geoLookUp()						// there is no geo info available
			or empty($this->options['countryList'])		// there is no country set in options
			or $this->isAllowedCountry()				// the user's country is allowed
			or (defined('LIMIT_LOGIN_COUNTRIES_OVERRIDE') and TRUE === LIMIT_LOGIN_COUNTRIES_OVERRIDE)	// override constant is defined
		)
			return $user;

		// we are still here, so we complain about the user's country
		$user = new WP_Error('country_error', sprintf(__('Login not allowed from your country (%s)!', 'limit-login-countries'),__($this->geoInfo->country_name, 'limit-login-countries')));

		// we save the unsuccessful country code
		$log = get_option( 'limit_login_countries_log', array() );
		$log[$this->geoInfo->country_code] += 1;
		update_option( 'limit_login_countries_log', $log );

		return $user;
	}
}
