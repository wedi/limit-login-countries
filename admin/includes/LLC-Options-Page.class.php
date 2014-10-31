<?php

/**
 * Class LLC_Options_Page Contains helper functions for the plugin's settings
 * page.
 *
 * This class encapsulates display and change of our plugin's options.
 *
 * @package Limit Login Countries
 * @author  Dirk Weise
 * @since   0.3
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
	 * Registers all our settings with WP's settings API.
	 * Callback function for WP's admin_init hook.
	 *
	 * @see   LLC_Admin::__construct()
	 * @see   http://codex.wordpress.org/Settings_API
	 * @since 0.3
	 */
	public static function register_settings() {

		/* -------------------------------------------------------------------
		 * Add settings page to menu
		 * ---------------------------------------------------------------- */

		add_options_page(
		// translators: The title of the plugin settings page (page & browser title).
			__( 'Limit Login Countries Settings', 'limit-login-countries' ),
			// translators: The menu item of the settings page in the WordPress admin area.
			__( 'Login Countries', 'limit-login-countries' ),
			'manage_options',
			'limit-login-countries',
			array( get_called_class(), 'settings_page' )
		);

		/* -------------------------------------------------------------------
		 * Add section 'Country Settings' - llc_country_settings
		 * ---------------------------------------------------------------- */

		add_settings_section(
		// ID used to identify this section and with which to register options
			'llc_country_settings',
			// Title to be displayed on the administration page
			__( 'Login Country Settings', 'limit-login-countries' ),
			// Callback used to render the description of the section
			array( get_called_class(), 'country_settings_description' ),
			// Page on which to add this section of options
			'limit-login-countries'
		);

		// Add fields to section ---------------------------------------------

		add_settings_field(
		// ID used to identify the field throughout the plugin
			'llc_blacklist',
			// The label to the left of the option interface element
			__( 'Act as:', 'limit-login-countries' ),
			// The name of the function responsible for rendering the option interface
			array( get_called_class(), 'country_list_type_callback' ),
			// The page on which this option will be displayed
			'limit-login-countries',
			// The name of the section to which this field belongs
			'llc_country_settings',
			// The array of arguments to pass to the callback.
			array( 'label_for' => 'llc_blacklist' )
		);

		// we figure out the appropriate label
		if ( 'whitelist' === get_option( 'llc_blacklist', 'whitelist' ) ) {
			$label = __( 'Exclusive list of allowed countries:', 'limit-login-countries' );
		} else {
			$label = __( 'Exclusive list of rejected countries:', 'limit-login-countries' );
		}
		add_settings_field(
			'llc_countries',
			$label,
			array( get_called_class(), 'country_list_callback' ),
			'limit-login-countries',
			'llc_country_settings',
			array( 'label_for' => 'llc_countries' )
		);

		// Register section --------------------------------------------------

		register_setting(
		// A settings group name. Should correspond to a whitelisted option key name.
			'limit-login-countries',
			// The name of an option or section to sanitize and save.
			'llc_blacklist',
			// A callback function that sanitizes the option's value.
			array( get_called_class(), 'country_list_type_sanitize' )
		);
		register_setting(
			'limit-login-countries',
			'llc_countries',
			array( get_called_class(), 'country_list_sanitize' )
		);

		/* -------------------------------------------------------------------
		 * Add 'GeoIP Database Settings' - llc_geoip_settings
		 * ---------------------------------------------------------------- */

		add_settings_section(
			'llc_geoip_settings',
			__( 'GeoIP Database Settings', 'limit-login-countries' ),
			array( get_called_class(), 'geoip_settings_description' ),
			'limit-login-countries'
		);

		// Add fields to section ---------------------------------------------

		add_settings_field(
			'llc_geoip_database_path',
			__( 'GeoIP database file:', 'limit-login-countries' ),
			array( get_called_class(), 'geoip_database_path_callback' ),
			'limit-login-countries',
			'llc_geoip_settings',
			array( 'label_for' => 'llc_geoip_database_path' )
		);

		// Register section --------------------------------------------------

		register_setting(
			'limit-login-countries',
			'llc_geoip_database_path',
			array( get_called_class(), 'geoip_database_path_sanitize' )
		);
	}

	/**
	 * Render the actual settings page.
	 * Callback function for add_option_page.
	 *
	 * @see   LLC_Options_Page::register_settings()
	 * @since 0.3
	 *
	 * @return void
	 */
	public static function settings_page() {

		// we make sure the current user has sufficient capabilities to fiddle with our options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'limit-login-countries' ) );
		} ?>
		<div class="wrap" id="llc-options-page">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>"
			      method="post">
				<?php
				settings_fields( 'limit-login-countries' );
				do_settings_sections( 'limit-login-countries' );
				submit_button();
				?>
			</form>
		</div>
	<?php
	}

	/* -----------------------------------------------------------------------
	 * Section 'Country Settings' callback functions - llc_country_settings
	 * -------------------------------------------------------------------- */

	/**
	 * Render the description for country settings
	 */
	public static function country_settings_description() {

		$r = '<p>' . __( 'Here you configure from which countries admin area logins are allowed.', 'limit-login-countries' ) . '</p>';
		$r .= '<p><em>' . sprintf( __( '<strong>Remember:</strong> In case you lock yourself out of WP\'s admin area you can disable the country check by adding %s to your <code>wp-config.php</code> file.', 'limit-login-countries' ), '<code>define(\'LIMIT_LOGIN_COUNTRIES_OVERRIDE\', TRUE);</code>' ) . '</em></p>';

		echo $r;
	}

	/**
	 * Render the country list type (white-/blacklist) input field
	 */
	public static function country_list_type_callback() {

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

	/**
	 * Sanitize the country list type (white-/blacklist) input.
	 *
	 * @param $input string The country list type sent by the user.
	 *
	 * @return string Sanitized input.
	 */
	public static function country_list_type_sanitize( $input ) {

		$output = get_option( 'llc_blacklist', 'whitelist' );

		if ( 'whitelist' === $input or 'blacklist' === $input ) {
			$output = $input;
		} else {
			add_settings_error( 'llc_blacklist', 'llc-invalid-value', __( 'Invalid value. You must select either whitelist or blacklist.', 'limit-login-countries' ) );
		}

		return $output;
	}

	/**
	 * Render the country list input field.
	 */
	public static function country_list_callback() {

		$setting = esc_attr( get_option( 'llc_countries', '' ) );
		$r       = "<input type='text' id='llc_countries' name='llc_countries' value='$setting'>";
		$r .= "<div id='llc_test' />";

		$r .= '<ul>';
		$r .= '<li>' . __( 'List of 2-letter country codes.', 'limit-login-countries' ) . '</li>';
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

	/**
	 * Sanitize the country list input. Expects comma separated list of 2-char country codes.
	 *
	 * @param $input string The country list sent by the user.
	 *
	 * @return string
	 */
	public static function country_list_sanitize( $input ) {

		$countries = array_unique(
			explode(
				',',
				trim( strtoupper( preg_replace( '/[^,a-zA-Z]/', '', $input ) ), ',' )
			)
		);
		require_once( dirname( dirname( __DIR__ ) ) . '/includes/LLC-GeoIP-Countries.class.php' );
		$gc     = new LLC_GeoIP_Countries();
		$output = array_filter( $countries, function ( $var ) use ( $gc ) {
			return in_array( $var, $gc->country_codes );
		} );

		return implode( ',', $output );
	}

	/* -----------------------------------------------------------------------
	 * Section 'GeoIP Settings' callback functions - llc_geoip_settings
	 * -------------------------------------------------------------------- */

	/**
	 * Render the description for the GeoIP settings.
	 */
	public static function geoip_settings_description() {

		$r = '<p>' . sprintf( __( 'This plugin works with <a href="%1$s" target="_blank">Maxmind\'s GeoIP database</a>. If you are not a paying customer, you can download a lite version for free: <a href="%2$s" title="Direct Download">Download Maxmind\'s GeoIP Lite database</a>.', 'limit-login-countries' ), 'http://dev.maxmind.com/geoip/', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz' ) . '</p>';

		echo $r;
	}

	/**
	 * Render the GeoIP database path input field.
	 */
	public static function geoip_database_path_callback() {

		$geoip_database_path = get_option( 'llc_geoip_database_path', '' );

		$setting = esc_attr( $geoip_database_path );
		echo "<input type='text' id='llc_geoip_database_path' name='llc_geoip_database_path' value='$setting' size='60' />";

		if ( LLC_GeoIP_Tools::is_valid_geoip_database( $geoip_database_path, $msg ) ) {
			$dashicon = 'dashicons-yes';
			$color    = '#7ad03a';
		} else {
			$dashicon = 'dashicons-no';
			$color    = '#dd3d36';
		}
		echo sprintf( '<p><span style="color:%2$s;font-size:20px;" class="dashicons %3$s" title="%1$s"></span>&nbsp;<em>%1$s</em><br><br></p>', $msg, $color, $dashicon );

		if ( '' === $setting ) {
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

	/**
	 * Sanitize the GeoIP database path input.
	 *
	 * @param $new_db_path string The GeoIP database path sent by the user.
	 *
	 * @return string
	 */
	public static function geoip_database_path_sanitize( $new_db_path ) {

		$current_db_path = get_option( 'llc_geoip_database_path', '' );
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

	/**
	 * Return link to the options page.
	 *
	 * @since 0.7
	 *
	 * @return string
	 */
	public static function get_link() {
		return admin_url( 'options-general.php?page=limit-login-countries' );
	}

	/**
	 * Return link to the options page.
	 *
	 * @since 0.7
	 *
	 * @return string
	 */
	public static function get_link_tag() {
		return sprintf( '<a href="%1$s">%2$s</a>', static::get_link(), __( 'Settings', 'limit-login-countries' ) );
	}

	/**
	 * Registers and enqueues scripts and stylesheets on options page.
	 * Callback function for automagically created WP hook
	 * 'admin_print_scripts-settings_page_limit-login-countries'
	 *
	 * @see   LLC_Admin::__construct()
	 * @since 0.3
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {

		$url       = plugins_url( '/', dirname( __DIR__ ) );
		$admin_url = plugins_url( '/', __DIR__ );

		/* -------------------------------------------------------------------
		 * Add custom JavaScript to settings page
         * ---------------------------------------------------------------- */

		// Register scripts --------------------------------------------------

		wp_register_script(
			'textext-core',
			$url . 'vendor/TextExt/js/textext.core.js',
			array( 'jquery-core' ),
			'1.3.1',
			true
		);
		wp_register_script(
			'textext-autocomplete',
			$url . 'vendor/TextExt/js/textext.plugin.autocomplete.js',
			array( 'textext-core' ),
			'1.3.1',
			true
		);
		wp_register_script(
			'textext-filter',
			$url . 'vendor/TextExt/js/textext.plugin.filter.js',
			array( 'textext-core' ),
			'1.3.1',
			true
		);
		wp_register_script(
			'textext-tags',
			$url . 'vendor/TextExt/js/textext.plugin.tags.js',
			array( 'textext-core' ),
			'1.3.1',
			true
		);
		wp_register_script(
			'are-you-sure',
			$url . 'vendor/are-you-sure/jquery.are-you-sure.js',
			array( 'jquery-core' ),
			'1.9.0',
			true
		);
		wp_localize_script(
			'are-you-sure',
			'LLC_AYS',
			array(
				'message' => esc_html__(
					'The changes you made will be lost if you navigate away from this page.',
					'limit-login-countries'
				)
			)
		);

		// Enqueue scripts ---------------------------------------------------

		wp_enqueue_script(
			'limit-login-countries',
			$admin_url . 'js/limit-login-countries.js',
			array(
				'are-you-sure',
				'textext-autocomplete',
				'textext-tags',
				'textext-filter',
			),
			'0.7',
			true
		);

		/* -------------------------------------------------------------------
		 * Add custom CSS to settings page
         * ---------------------------------------------------------------- */

		// Register styles ---------------------------------------------------

		wp_register_style(
			'textext-core',
			$url . 'vendor/TextExt/css/textext.core.css',
			array(),
			'0.4'
		);
		wp_register_style(
			'textext-autocomplete',
			$url . 'vendor/TextExt/css/textext.plugin.autocomplete.css',
			array( 'textext-core' ),
			'0.4'
		);
		wp_register_style(
			'textext-tags',
			$url . 'vendor/TextExt/css/textext.plugin.tags.css',
			array( 'textext-core' ),
			'0.4'
		);

		// Enqueue styles ----------------------------------------------------

		wp_enqueue_style(
			'limit-login-countries',
			$admin_url . 'css/limit-login-countries.css',
			array(
				'textext-autocomplete',
				'textext-tags',
			),
			'0.4'
		);
	}
}
