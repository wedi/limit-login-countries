<?php

/**
 * Encapsulates the admin functionality of the plugin.
 *
 * @since 0.7
 */
class LLC_Admin {

	/** @var $instance LLC_Admin Stores the single plugin object instance. */
	protected static $instance;


	/**
	 * Initialize the class, set its properties and register needed WordPress Hooks.
	 *
	 * @since 0.7
	 */
	protected function __construct() {

		require_once( dirname( __DIR__ ) . '/includes/LLC-GeoIP-Tools.class.php' );
		LLC_GeoIP_Tools::$geoIPDatabase       = get_option( 'llc_geoip_database_path' );
		LLC_GeoIP_Tools::$proxy_client_header = get_option( 'llc_proxy_client_header' );
		require_once( __DIR__ . '/../includes/LLC-Admin-Notice.class.php' );
		require_once( __DIR__ . '/includes/LLC-Options-Page.class.php' );

		// we add a link to the plugin settings on the plugin page
		$meta            = new ReflectionClass( 'Limit_Login_Countries' );
		$plugin_basename = plugin_basename( $meta->getFileName() );
		add_filter('plugin_action_links_' . $plugin_basename,
			array( $this, 'plugin_settings_link' ), 10, 1
		);

		if ( static::is_settings_page( true ) ) {
			// check current settings and display a admin notice on error.
			add_action( 'admin_init', array( get_called_class(), 'check_settings' ) );
		}

		// build the settings page when admin_menu hook fires.
		add_action( 'admin_menu', array( 'LLC_Options_Page', 'register_settings' ) );

		// register and enqueue our scripts on admin_print_scripts hook execution.
		add_action( 'admin_print_scripts',
			array( 'LLC_Options_Page', 'enqueue_scripts' )
		);

		// hook in to display admin notices.
		add_action( 'admin_notices', array( get_called_class(), 'display_admin_notices' ) );

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
	 * Check the current settings and display errors.
	 * Fired on admin_init hook.
	 *
	 * @see   LLC_Admin::__construct()
	 * @since 0.7
	 */
	public static function check_settings() {

		// check only if not just submitted
		if ( ! ( isset( $_POST['option_page'] ) and 'limit-login-countries' == $_POST['option_page'] ) ) {

			// check llc_database_path ---------------------------------------

			$db_path = get_option( 'llc_geoip_database_path' );
			if ( ! LLC_GeoIP_Tools::is_valid_geoip_database( $db_path, $errmsg ) ) {
				if ( static::is_settings_page() ) {
					add_settings_error( 'llc_geoip_database_path', 'geoip-database-error', $errmsg );
				} else {
					LLC_Admin_Notice::add_notice( $errmsg . ' ' . LLC_Options_Page::get_link_tag(), 'error' );
				}
			}

			// check proxy settings ------------------------------------------

			$proxy_settings = LLC_Options_Page::proxy_get_options();
			if ( LLC_GeoIP_Tools::proxy_detected() and ! $proxy_settings['header'] and ! $proxy_settings['disable_warning'] ) {
				$errmsg = __( 'You need to adjust your proxy settings for Limit Login Countries to work correctly.', 'limit-login-countries' );
				if ( static::is_settings_page() ) {
					LLC_Admin_Notice::add_notice( $errmsg, 'warning', array( 'prefix' => '' ) );
				} else {
					LLC_Admin_Notice::add_notice( $errmsg . ' ' . LLC_Options_Page::get_link_tag(), 'warning' );
				}
			}

			// TODO: check if admin will be locked out after logout
		}
	}


	/**
	 * Add a link to plugin settings on the plugin list page.
	 * Callback function for 'plugin_action_links' filter.
	 *
	 * @see LLC_Admin::__construct()
	 * @since 0.7
	 *
	 * @param $links Array of plugin action links.
	 *
	 * @return Array of modified plugin action links.
	 */
	public static function plugin_settings_link( $links ) {

		array_unshift( $links, LLC_Options_Page::get_link_tag() );

		return $links;
	}

	public static function is_settings_page( $or_dashboard = false ) {
		global $pagenow;
		return ( 'options-general.php' === $pagenow and isset( $_GET['page'] ) and 'limit-login-countries' === $_GET['page'] )
			or $or_dashboard and 'index.php' === $pagenow;
	}

	public static function display_admin_notices() {
		if ( static::is_settings_page( true ) ) {
			LLC_Admin_Notice::display_notices();
		}
	}

}
