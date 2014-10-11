<?php
/*
Plugin Name: Limit Login Countries
Plugin URI: http://wordpress.org/extend/plugins/limit-login-countries/
GitHub Plugin URI: https://github.com/wedi/limit-login-countries/
Description: This plugin gives you the ability to limit WordPress admin logins based on the country the visitor's IP address is located in.
Version: 0.6.4
Text Domain: limit-login-countries
Author: Dirk Weise
Author URI: http://dirk-weise.de/

License: GPLv2 or later
*/

/** Run the plugin. */
Limit_Login_Countries::get_instance();

/**
 * Encapsulates Limit_Login_Countries functionality.
 * To keep the global namespace clean this plugin's main class is implemented as a singleton.
 *
 * @since 0.1
 */
class Limit_Login_Countries {

	/** @var $instance LLC_Public Stores the single plugin object instance. */
	protected static $instance;

	/** @var $instance Limit_Login_Countries Stores the single plugin object instance. */
	protected static $slug = 'limit-login-countries';


	/**
	 * Constructor of plugin class.
	 * Uses singleton pattern. There is no reason for more than one instance of the plugin and we don't pollute the
	 * global namespace. Use Limit_Login_Countries::get_instance() if the plugin object needs to be accessed.
	 *
	 * @since 0.1
	 */
	protected function __construct() {

		// The code that runs during plugin activation.
		require_once __DIR__ . '/includes/LLC_Activator.class.php';
		register_activation_hook( __FILE__, array( 'LLC_Activator', 'activate' ) );

		// load translation
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// load the public functionality of the plugin
		require_once( __DIR__ . '/public/LLC_Public.class.php' );
		LLC_Public::get_instance();

		// load the admin functionality of the plugin
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			require_once( __DIR__ . '/admin/LLC_Admin.class.php' );
			LLC_Admin::get_instance();
		}

	}

	/**
	 * Returns and if necessary creates the plugin object instance
	 *
	 * @since 0.2
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
	 * Load the plugin text domain for translation.
	 *
	 * @since 0.7
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			self::$slug,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}
}
