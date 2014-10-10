<?php

/**
 * The LLC_Admin_Notice class handles the plugins admin notices
 *
 * @since      0.7
 *
 * @package    Limit_Login_Countries
 * @subpackage Limit_Login_Countries/includes
 */
class LLC_Admin_Notice {

	/** @var $option_name String Stores the option name under which to store the notices. */
	protected static $option_name = 'llc_admin_notices';

	/**
	 * The constructor is declared private to make sure this helper class
	 * cannot be instantiated.
	 *
	 * @since 0.7
	 */
	private function __construct() {
	}

	public static function add_notice( $notice, $type, $capability = 'manage_options' ) {

		$notice_types = array( 'error', 'warning', 'notice' );
		if ( ! in_array( $type, $notice_types ) ) {
			trigger_error( "Unknown type [$type] of admin notice given.", E_USER_WARNING );
			$type = '';
		}
		$notices = get_option( self::$option_name, array() );
		$notice = array( 'text' => $notice, 'type' => $type, 'capability' => $capability );
		if ( ! in_array( $notice, $notices ) ) {
			$notices[] = $notice;
		}
		update_option( LLC_Admin_Notice::$option_name, $notices );
	}

	/**
	 * Displays registered admin_notices. Runs on admin_notices hook.
	 *
	 * @since 0.7
	 */
	public static function display_notices() {

		if ( $notices = get_option( LLC_Admin_Notice::$option_name ) ) {
			foreach ( $notices as $notice ) {
				if ( ! isset( $notice['capability'] ) or empty( $notice['capability'] ) or current_user_can( $notice['capability'] ) ) {
					switch ( $notice['type'] ) {
						case 'error':
							$class = 'error';
							break;
						case 'warning':
							$class = 'update-nag';
							break;
						case 'notice':
							$class = 'updated';
							break;
						default:
							$class = '';
					}
					echo "<div class='$class'><p><strong>Limit Login Countries: $notice[text]</strong></p></div>";
				}
			}
			delete_option( LLC_Admin_Notice::$option_name );
		}
	}
}
