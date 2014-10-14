<?php

/**
 * Class LLC_Options_Page Contains helper functions for the plugin's settings
 * page.
 *
 * This class encapsulates display and change of our plugin's options.
 *
 * @package Limit Login Countries
 * @author: Dirk Weise
 * @since:  0.3
 *
 */
class LLC_Options_Page {

	/**
	 * The constructor is declared private to make sure this helper class cannot
	 * be instantiated.
	 *
	 * @since 0.3
	 */
	private function __construct() {
	}

	/**
	 * Check the current settings and display errors.
	 * Fired on admin_init hook.
	 *
	 * @see   LLC_Admin::__construct()
	 * @since 0.3
	 */
	public static function check_settings() {

		// check only if not just submitted
		if ( ! isset( $_GET['settings-updated'] ) and ! isset( $_POST['llc_geoip_database_path'] ) ) {
			// check llc_database_path
			$db_path = get_option( 'llc_geoip_database_path' );
			require_once( __DIR__ . '/../../includes/LLC-GeoIP-Tools.class.php' );
			if ( ! LLC_GeoIP_Tools::is_valid_geoip_database( $db_path, $errmsg ) ) {
				global $pagenow;
				if ( 'options-general.php' === $pagenow and isset( $_GET['page'] ) and 'limit-login-countries' === $_GET['page'] ) {
					add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-existent', $errmsg );
				} else {
					require_once( __DIR__ . '/../../includes/LLC-Admin-Notice.class.php' );
					LLC_Admin_Notice::add_notice( $errmsg . ' ' . self::get_link(), 'error' );
				}
			}
		}
		// TODO: check if admin will be locked out after logout
	}

	/**
	 * Return link to the options page.
	 *
	 * @since 0.7
	 *
	 * @return string
	 */
	public static function get_link() {

		return sprintf( '<a href="' . admin_url( 'options-general.php?page=%s' ) . '">%s</a>', 'limit-login-countries', __( 'Settings', 'limit-login-countries' ) );
	}

	/**
	 * Registers all our settings with WP's settings API.
	 * Callback function for WP's admin_init hook.
	 *
	 * @see LLC_Admin::__construct()
	 * @see http://codex.wordpress.org/Settings_API
	 * @since 0.3
	 */
	public static function register_settings() {

		// we register all our settings
		register_setting( 'limit-login-countries', 'llc_geoip_database_path', array(
			'LLC_Options_PAGE',
			'geoip_database_path_validate',
		) );
		register_setting( 'limit-login-countries', 'llc_blacklist', array( 'LLC_Options_PAGE', 'blacklist_validate' ) );
		register_setting( 'limit-login-countries', 'llc_countries', array( 'LLC_Options_PAGE', 'countries_validate' ) );

		// we add settings sections
		add_settings_section( 'llc-general', __( 'General Settings', 'limit-login-countries' ), array(
			'LLC_Options_Page',
			'general_settings_callback',
		), 'limit-login-countries' );
		add_settings_section( 'llc-geoip', __( 'GeoIP Database', 'limit-login-countries' ), array(
			'LLC_Options_Page',
			'geoip_settings_callback',
		), 'limit-login-countries' );

		// we add settings to our settings sections
		add_settings_field( 'llc_blacklist', __( 'Act as:', 'limit-login-countries' ), array(
			'LLC_Options_Page',
			'blacklist_callback',
		), 'limit-login-countries', 'llc-general', array( 'label_for' => 'llc_blacklist' ) );

		// we figure out the appropriate label
		if ( 'whitelist' === get_option( 'llc_blacklist', 'whitelist' ) ) {
			$label = __( 'Exclusive list of allowed countries:', 'limit-login-countries' );
		} else {
			$label = __( 'Exclusive list of rejected countries:', 'limit-login-countries' );
		}
		add_settings_field( 'llc_countries', $label, array(
			'LLC_Options_Page',
			'countries_callback',
		), 'limit-login-countries', 'llc-general', array( 'label_for' => 'llc_countries' ) );

		add_settings_field( 'llc_geoip_database_path', __( 'GeoIP database file:', 'limit-login-countries' ), array(
			'LLC_Options_Page',
			'geoip_database_path_callback',
		), 'limit-login-countries', 'llc-geoip', array( 'label_for' => 'llc_geoip_database_path' ) );
	}

	public static function general_settings_callback() {

		$r = '<p>' . __( 'Here you configure from which countries admin area logins are allowed.', 'limit-login-countries' ) . '</p>';
		$r .= '<p><em>' . sprintf( __( '<strong>Remember:</strong> In case you lock yourself out of WP\'s admin area you can disable the country check by adding %s to your <code>wp-config.php</code> file.', 'limit-login-countries' ), '<code>define(\'LIMIT_LOGIN_COUNTRIES_OVERRIDE\', TRUE);</code>' ) . '</em></p>';

		echo $r;
	}

	public static function geoip_settings_callback() {

		$r = '<p>' . sprintf( __( 'This plugin works with <a href="%1$s" target="_blank">Maxmind\'s GeoIP database</a>. If you are not a paying customer, you can download a lite version for free: <a href="%2$s" title="Direct Download">Download Maxmind\'s GeoIP Lite database</a>.', 'limit-login-countries' ), 'http://dev.maxmind.com/geoip/', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz' ) . '</p>';

		echo $r;
	}

	public static function geoip_database_path_callback() {

		$geoip_database_path = get_option( 'llc_geoip_database_path' );

		$setting = esc_attr( $geoip_database_path );
		echo "<input type='text' id='llc_geoip_database_path' name='llc_geoip_database_path' value='$setting' size='60' />";

		require_once( __DIR__ . '/../../includes/LLC-GeoIP-Tools.class.php' );
		if ( LLC_GeoIP_Tools::is_valid_geoip_database( $geoip_database_path, $msg ) ) {
			$dashicon = 'dashicons-yes';
			$color    = '#7ad03a';
		} else {
			$dashicon = 'dashicons-no';
			$color    = '#dd3d36';
		}
		echo sprintf( '<p><span style="color:%2$s;font-size:20px;" class="dashicons %3$s" title="%1$s"></span>&nbsp;<em>%1$s</em><br><br></p>', $msg, $color, $dashicon );

		if ( '' === $setting ) {
			require_once( dirname( dirname( __DIR__ ) ) . '/includes/LLC-GeoIP-Tools.class.php' );
			$gds = LLC_GeoIP_Tools::search_geoip_database();
			echo '<p>' . __( 'For your convenience we tried to find a database file.', 'limit-login-countries' ) . '</p>';
			if ( count( $gds ) > 0 ) {
				echo '<ul>';
				foreach ( $gds as $gd ) {
					echo '<li><code>' . $gd['filepath'] . '</code>, published ' . date( 'd. M Y', $gd['publish_date'] ) . '.</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>' . __( 'Unfortunately we couldn\'t find any database file.<br/>If you are sure you uploaded (and unzipped) one, it is probably just named differently from what we expected. No worries, just enter the correct absolute path to your database file above and we are all fine.', 'limit-login-countries' ) . '</p>';
			}
		}
	}

	public static function geoip_database_path_validate( $new_db_path ) {

		$current_db_path = get_option( 'llc_geoip_database_path' );
		require_once( __DIR__ . '/../../includes/LLC-GeoIP-Tools.class.php' );
		if ( ! LLC_GeoIP_Tools::is_valid_geoip_database( $new_db_path, $errmsg ) ) {
			add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-existent', $errmsg );
			if ( LLC_GeoIP_Tools::is_valid_geoip_database( $current_db_path ) ) {
				return $current_db_path;
			} elseif ( ! empty( $new_db_path ) ) {
				return $new_db_path;
			} else {
				return '';
			}
		} else {

			return $new_db_path;
		}
	}

	public static function blacklist_callback() {

		$s = get_option( 'llc_blacklist', 'whitelist' );

		if ( 'whitelist' === $s ) {
			$ws = ' selected="selected"';
			$bs = '';
		} else {
			$ws = '';
			$bs = ' selected="selected"';
		}

		$r = '';

		$r .= '<select id="llc_blacklist" name="llc_blacklist">';
		$r .= '<option value="whitelist"' . $ws . '>' . __( 'Whitelist', 'limit-login-countries' ) . '</option>';
		$r .= '<option value="blacklist"' . $bs . '>' . __( 'Blacklist', 'limit-login-countries' ) . '</option>';
		$r .= '</select>';

		$r .= '<ul>';
		$r .= '<li>' . __( '<em>Whitelist</em> means login is allowed from the countries listed below only.', 'limit-login-countries' ) . '</li>';
		$r .= '<li>' . __( '<em>Blacklist</em> means login is not allowed from the countries listed below only.', 'limit-login-countries' ) . '</li>';
		$r .= '</ul>';

		echo $r;
	}

	public static function blacklist_validate( $input ) {

		$output = get_option( 'llc_blacklist', 'whitelist' );
		if ( 'whitelist' === $input or 'blacklist' === $input ) {
			$output = $input;
		} else {
			add_settings_error( 'llc_blacklist', 'llc-invalid-value', __( 'Invalid value. You must select either whitelist or blacklist.', 'limit-login-countries' ) );
		}

		return $output;
	}

	public static function countries_callback() {

		$setting = esc_attr( get_option( 'llc_countries' ) );
		$r  = "<input type='text' id='llc_countries' name='llc_countries' value='$setting'>";
		$r .= "<div id='llc_test' />";

		$r .= '<ul>';
		$r .= '<li>' . __( 'List of 2-digit country codes.', 'limit-login-countries' ) . '</li>';
		$r .= '<li class="no-js">' . __( 'Use a comma as delimiter.', 'limit-login-countries' ) . '</li>';
		$r .= '<li>' . __( 'If list is empty, no login restriction applies.', 'limit-login-countries' ) . '</li>';
		$r .= '</ul>';

		echo $r;

		$llc_countries_label['whitelist'] = __( 'Exclusive list of allowed countries:', 'limit-login-countries' );
		$llc_countries_label['blacklist'] = __( 'Exclusive list of rejected countries:', 'limit-login-countries' );
		wp_localize_script( 'limit-login-countries', 'LLC_COUNTRIES_LABEL', $llc_countries_label );

		require_once( dirname( dirname( __DIR__ ) ) . '/includes/LLC-GeoIP-Countries.class.php' );
		$gc = new LLC_GeoIP_Countries();
		$gc->wp_localize_country_codes();
	}

	public static function countries_validate( $input ) {

		$countries = array_unique( explode( ',', trim( strtoupper( preg_replace( '/[^,a-zA-Z]/', '', $input ) ), ',' ) ) );
		require_once( dirname( dirname( __DIR__ ) ) . '/includes/LLC-GeoIP-Countries.class.php' );
		$gc = new LLC_GeoIP_Countries();

		$output = array_filter( $countries, function ( $var ) use ( $gc ) {
			return in_array( $var, $gc->country_codes );
		} );

		return implode( ',', $output );
	}

	/**
	 * Adds our options page to the admin area.
	 * Callback function for WP's hook 'admin_menu'.
	 *
	 * @see   LLC_Admin::__construct()
	 * @since 0.3
	 */
	public static function settings_menu() {

		add_options_page(
			// translators: this is the title of the plugin's settings page in the WordPress admin area.
			__( 'Limit Login Countries Settings', 'limit-login-countries' ),
			// translators: this is the menu entry title for the plugin's settings page in the WordPress admin area.
			__( 'Login Countries', 'limit-login-countries' ),
			'manage_options',
			'limit-login-countries',
			array( 'LLC_Options_Page', 'settings_page' )
		);
	}

	/**
	 * Prints the actual settings page.
	 * Callback function for add_option_page
	 *
	 * @see   LLC_Options_Page::settings_menu()
	 * @since 0.3
	 *
	 * @return bool
	 */
	public static function settings_page() {

		// we make sure the current user has sufficient capabilities to fiddle with our options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'limit-login-countries' ) );
		}?>
		<div class="wrap" id="llc-options-page">
			<div id="icon-options-general" class="icon32"></div><?php // The icon is outdated with MP6 in WP 4.0 but we keep it for backwards compatibility. ?>
			<h2><?php echo esc_html( get_admin_page_title(), 'limit-login-countries' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post"><?php
				settings_fields( 'limit-login-countries' );
				do_settings_sections( 'limit-login-countries' );
				submit_button();
				?>
			</form>
		</div>
		<?php
		return true;
	}
}
