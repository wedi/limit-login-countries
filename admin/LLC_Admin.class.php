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

		// we add a callback on the incredible admin_print_scripts-settings_limit-login-countries hook
		// to register and enqueue our scripts only on our own settings page
		add_action( 'admin_print_scripts-settings_page_limit-login-countries',
			array( $this, 'enqueue_scripts' )
		);

		// we add a link to the plugin settings on the plugin page
		$meta            = new ReflectionClass( 'Limit_Login_Countries' );
		$plugin_basename = plugin_basename( $meta->getFileName() );
		add_filter('plugin_action_links_' . $plugin_basename,
			array( $this, 'plugin_settings_link' ), 10, 1
		);

		require_once( __DIR__ . '/includes/LLC_Options_Page.class.php' );

		// we add a callback on admin_init hook to register our settings
		add_action( 'admin_init', array( 'LLC_Options_Page', 'register_settings' ) );

		// we add a callback on admin_menu hook to add our options page
		add_action( 'admin_menu', array( 'LLC_Options_Page', 'settings_menu' ) );

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
	 * Registers and enqueues scripts and stylesheets on options page.
	 * Callback function for automagically created WP hook 'admin_print_scripts-settings_page_limit-login-countries'
	 *
	 * @see LLC_Admin::__construct()
	 * @since 0.7
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		$url       = plugins_url( '/', __DIR__ );
		$admin_url = plugins_url( '/', __FILE__ );
		wp_register_script( 'textext-core', $url . 'vendor/TextExt/js/textext.core.js', array( 'jquery-core' ), '1.3.1', true );
		wp_register_script( 'textext-autocomplete', $url . 'vendor/TextExt/js/textext.plugin.autocomplete.js', array( 'textext-core' ), '1.3.1', true );
		wp_register_script( 'textext-filter', $url . 'vendor/TextExt/js/textext.plugin.filter.js', array( 'textext-core' ), '1.3.1', true );
		wp_register_script( 'textext-tags', $url . 'vendor/TextExt/js/textext.plugin.tags.js', array( 'textext-core' ), '1.3.1', true );
		//wp_register_script('textext-suggestions', $url . 'vendor/TextExt/js/textext.plugin.suggestions.js', array('textext-core'), '1.3.1', true);
		wp_enqueue_script( 'limit-login-countries', $admin_url . 'js/limit-login-countries.js', array(
			'textext-autocomplete',
			'textext-tags',
			'textext-filter'
		), '0.4', true );


		wp_register_style( 'textext-core', $url . 'vendor/TextExt/css/textext.core.css', array(), '0.4' );
		wp_register_style( 'textext-autocomplete', $url . 'vendor/TextExt/css/textext.plugin.autocomplete.css', array( 'textext-core' ), '0.4' );
		wp_register_style( 'textext-tags', $url . 'vendor/TextExt/css/textext.plugin.tags.css', array( 'textext-core' ), '0.4' );
		wp_enqueue_style( 'limit-login-countries', $admin_url . 'css/limit-login-countries.css', array(
			'textext-autocomplete',
			'textext-tags'
		), '0.4' );
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

		$settings_link = sprintf( '<a href="' . admin_url( 'options-general.php?page=%s' ) . '">%s</a>', 'limit-login-countries', __( 'Settings', 'limit-login-countries' ) );
		array_unshift( $links, $settings_link );

		return $links;
	}

}
