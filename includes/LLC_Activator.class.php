<?php
/**
 * Fired during plugin activation
 *
 * @since 0.7
 *
 * @package Limit_Login_Countries
 * @subpackage Limit_Login_Countries/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0
 */
class LLC_Activator {

	/** @var string The minimum PHP version required by this plugin. */
	public static $minPHPVersion = '5.3.0';

	/**
	 * Plugin activation hook callback: checks system requirements.
	 *
	 * @since 1.0
	 */
	public static function activate() {
		if ( version_compare( PHP_VERSION, self::$minPHPVersion, '<' ) ) {
			deactivate_plugins( dirname( __DIR__ ) );
			wp_die( sprintf( __( 'Error: This plugin requires at least PHP version %1$s, your server is running version %2$s! ', 'limit-login-countries' ), self::$minPHPVersion, PHP_VERSION ) );
		}
	}
}
