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

		require_once( __DIR__ . '/includes/LLC-Options-Page.class.php' );
		require_once( __DIR__ . '/../includes/LLC-Admin-Notice.class.php' );

		// we add a link to the plugin settings on the plugin page
		$meta            = new ReflectionClass( 'Limit_Login_Countries' );
		$plugin_basename = plugin_basename( $meta->getFileName() );
		add_filter('plugin_action_links_' . $plugin_basename,
			array( $this, 'plugin_settings_link' ), 10, 1
		);

		// check current settings and display a admin notice on error.
		add_action( 'admin_init', array( get_called_class(), 'check_settings' ) );

		// build the settings page when admin_menu hook fires.
		add_action( 'admin_menu', array( 'LLC_Options_Page', 'register_settings' ) );

		// we add a callback on the incredible admin_print_scripts-settings_limit-login-countries hook
		// to register and enqueue our scripts only on our own settings page
		add_action( 'admin_print_scripts-settings_page_limit-login-countries',
			array( 'LLC_Options_Page', 'enqueue_scripts' )
		);

		// hook in to display admin notices.
		add_action( 'admin_notices', array( 'LLC_Admin_Notice', 'display_notices' ) );

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
		if ( ! isset( $_GET['settings-updated'] ) and ! isset( $_POST['llc_geoip_database_path'] ) ) {
			// check llc_database_path
			$db_path = get_option( 'llc_geoip_database_path' );
			require_once( __DIR__ . '/../includes/LLC-GeoIP-Tools.class.php' );
			if ( ! LLC_GeoIP_Tools::is_valid_geoip_database( $db_path, $errmsg ) ) {
				global $pagenow;
				if ( 'options-general.php' === $pagenow and isset( $_GET['page'] ) and 'limit-login-countries' === $_GET['page'] ) {
					add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-existent', $errmsg );
				} else {
					require_once( __DIR__ . '/../includes/LLC-Admin-Notice.class.php' );
					LLC_Admin_Notice::add_notice( $errmsg . ' ' . LLC_Options_Page::get_link_tag(), 'error' );
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
}
