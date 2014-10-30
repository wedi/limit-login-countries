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
	public static $option_name = 'llc_admin_notices';

	/** @var $option_name array Default options for new notices. */
	public static $default_options = array(
		'prefix'        => 'Limit Login Countries: ',
		'postfix'       => '',
		'before'        => '<p><strong>',
		'after'         => '</strong></p>',
		'class'         => 'admin-notice',
		'capability'    => 'manage_options',
	);

	/** @var $option_name array Stores the notices to display. */
	protected static $notices = array();

	/** @var $option_name bool Initialization status. */
	protected static $initialized = false;


	/**
	 * The constructor is declared private to make sure this helper class
	 * cannot be instantiated.
	 *
	 * @since 0.7
	 */
	private function __construct() {
	}

	public static function init() {
		if ( static::$initialized ) {
			return;
		}

		add_action( 'shutdown', array( get_called_class(), 'persist' ) );

		// Possible race condition with another request persisting notices
		// just between this get and delete call. Accepting this issue as
		// this can only happen if no user is logged in, because user_meta
		// is used otherwise.
		if ( static::$notices = get_option( self::$option_name, array() ) ) {
			delete_option( self::$option_name );
		}
		if ( $id = get_current_user_id() ) {
			// Possible race condition with another request with the same user
			// id persisting notices just between the get and delete call.
			// Might happen when the browser gets started and many tabs load
			// or user id gets shared. We don't care as notices are persisted
			// only if notices could not be displayed right away.
			if ( $notices = get_user_meta( $id, static::$option_name, true ) ) {
				delete_user_meta( $id, static::$option_name );
				foreach ( $notices as $notice ) {
					if ( ! in_array( $notice, static::$notices ) ) {
						static::$notices[] = $notice;
					}
				}
			}
		}

		static::$initialized = true;
	}

	public static function persist() {
		if ( count( static::$notices ) > 0 ) {
			if ( $id = get_current_user_id() ) {
				update_user_meta( $id, self::$option_name, static::$notices );
			} else {
				update_option( self::$option_name, static::$notices );
			}
		}
	}

	/**
	 * Add a new admin notice to display.
	 *
	 * @param $notice string
	 * @param $type string Message type. One of error|warning|success.
	 * @param array $args Options.
	 */
	public static function add_notice( $notice, $type, $args = array() ) {
		static::init();

		$args = array_merge( static::$default_options, $args );

		$notice_types = array( 'error', 'warning', 'success' );
		if ( ! in_array( $type, $notice_types ) ) {
			trigger_error( "Unknown type [$type] of admin notice given.", E_USER_WARNING );
			$type = '';
		}

		$notice = array( 'text' => $notice, 'type' => $type, 'args' => $args );
		if ( ! in_array( $notice, static::$notices ) ) {
			static::$notices[] = $notice;
		}
	}

	/**
	 * Displays registered admin_notices. Runs on admin_notices hook.
	 *
	 * @since 0.7
	 */
	public static function display_notices() {
		static::init();
		foreach ( static::$notices as $key => $notice ) {
			if ( ! isset( $notice['args']['capability'] ) or empty( $notice['args']['capability'] ) or current_user_can( $notice['args']['capability'] ) ) {
				switch ( $notice['type'] ) {
					case 'error':
						$notice['args']['class'] .= ' error';
						break;
					case 'warning':
						$notice['args']['class'] .= ' update-nag';
						break;
					case 'success':
						$notice['args']['class'] .= ' updated';
						break;
				}
				$notice['args']['class'] .= ' seq-' . $key;
				echo "<div class='{$notice['args']['class']}'>{$notice['args']['before']}{$notice['args']['prefix']}$notice[text]{$notice['args']['postfix']}{$notice['args']['after']}</div>";
				unset( static::$notices[ $key ] );
			}
		}
	}
}
