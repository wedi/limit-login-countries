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
			__( 'Login Countries', 'limit-login-countries' ),
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
			__( 'Blocking mode:', 'limit-login-countries' ),
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
			__( 'GeoIP Database', 'limit-login-countries' ),
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

		/* -------------------------------------------------------------------
		 * Add 'Proxy Settings' - llc_proxy_settings
		 * ---------------------------------------------------------------- */

		add_settings_section(
			'llc_proxy_settings',
			__( 'Proxy', 'limit-login-countries' ),
			array( get_called_class(), 'proxy_settings_description' ),
			'limit-login-countries'
		);

		// Add fields to section ---------------------------------------------

		add_settings_field(
			'proxy_header',
			__( 'Client IP HTTP header:', 'limit-login-countries' ),
			array( get_called_class(), 'proxy_header_callback' ),
			'limit-login-countries',
			'llc_proxy_settings',
			array( 'label_for' => 'proxy_header' )
		);

		// Register section --------------------------------------------------

		register_setting(
			'limit-login-countries',
			'llc_proxy_settings',
			array( get_called_class(), 'proxy_settings_sanitize' )
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
		// translators: %s is a PHP statement already wrapped in <code>.
		$r .= '<p><em>' . sprintf( __( '<strong>Remember:</strong> In case you lock yourself out of WP\'s admin area you can disable the country check by adding %s to your <code>wp-config.php</code> file.', 'limit-login-countries' ), '<code>define( \'LIMIT_LOGIN_COUNTRIES_OVERRIDE\', TRUE );</code>' ) . '</em></p>';

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
		$r .= '<li><a href="http://dev.maxmind.com/geoip/legacy/codes/iso3166/" target="_blank">'
				. __( 'List of 2-letter country codes.', 'limit-login-countries' )
				. '</a></li>';
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

		$r = '<p>' . sprintf( __( 'This plugin works with <a href="%1$s" target="_blank">Maxmind\'s GeoIP databases</a>. Just grab <a href="%2$s" title="Direct Download">the free version</a>, and move it (unzipped!) to your WordPress uploads directory.', 'limit-login-countries' ), 'http://dev.maxmind.com/geoip/legacy/geolite/', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz' ) . '</p>';

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
		echo sprintf( '<p><span style="color:%2$s;font-size:20px;" class="dashicons %3$s" title="%1$s"></span>&nbsp;<em>%1$s</em></p>', $msg, $color, $dashicon );
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
			add_settings_error( 'llc_geoip_database_path', 'geoip-database-invalid', $errmsg );
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

	/* -----------------------------------------------------------------------
	 * Section 'Proxy Settings' functions - llc_proxy_settings
	 * -------------------------------------------------------------------- */

	/**
	 * Render the description for the Proxy settings.
	 */
	public static function proxy_settings_description() {

		echo '<p>';
		_e( 'Here you can adjust how the plugin retrieves the client IP to use for geo information look up.', 'limit-login-countries' );
		echo '</p><p>';
		_e( 'In most cases everything will work out of the box, but sometimes a so-called <a href="http://en.wikipedia.org/wiki/Reverse_proxy" target="_blank">reverse proxy</a> blocks the easy way (e.g., for CDNs or load balancing).', 'limit-login-countries' );
		echo '</p>';

		if ( LLC_GeoIP_Tools::proxy_detected( $headers ) ) {
			echo '<div class="update-nag inline"><p><strong>';
			_e(
				'It seems that your WordPress installation lives behind a proxy.',
				'limit-login-countries'
			);
			echo '</strong></p><p>';
			_e( "You should ask your hosting or CDN provider which HTTP header contains the client's real IP address.",
				'limit-login-countries'
			);

			if ( count( $headers ) > 0 ) {
				echo '<br>';
				_e( 'However, here is a list of relevant HTTP headers found. <a href="http://ipaddress.com/" target="_blank">Look up your current IP address</a>. If any of the following headers match, it\'s probably the correct setting.', 'limit-login-countries' );

				echo '</p><ul><li><code>' .
					'REMOTE_ADDR = ' . esc_html( $_SERVER['REMOTE_ADDR'] ) .
					'</code></li>';
				foreach ( $headers as $header ) {
					printf(
						'<li><code>%1$s = "%2$s"</code></li>',
						esc_html( $header ),
						esc_html( $_SERVER[ $header ] )
					);
				}
				echo '</ul>';
			}
			$proxy_settings = static::proxy_get_options();
			$proxy_disable_warning = $proxy_settings['disable_warning'] ? ' checked' : '';
			printf(
				'<p><input type="checkbox" id="%1$s" name="%1$s" value="%1$s"%2$s><label for="%1$s">%3$s</label></p>',
				'proxy_disable_warning',
				$proxy_disable_warning,
				__( 'Stop nagging me. My settings are all ok and I accept full responsibility!', 'limit-login-countries' )
			);
			echo '</div>';
		} else {
			echo '<div class="updated inline"><p><strong>';
			_e( 'Cool. Chances are high that your WordPress installation does <em>not</em> need any special settings to deal with your proxy.', 'limit-login-countries' );
			echo '</strong></p><p>';

			_e( "However, that guess might be incorrect. If, as a matter of fact, your WordPress installation is behind a proxy, you should ask your hosting or CDN provider which HTTP header contains the client's real IP address.", 'limit-login-countries' );
			echo '</p></div>';
		}
	}

	/**
	 * Render the proxy header input field.
	 */
	public static function proxy_header_callback() {
		$proxy_settings = self::proxy_get_options();
		$proxy_header = esc_attr( $proxy_settings['header'] );

		echo "<input type='text' id='proxy_header' name='proxy_header' value='$proxy_header' size='30' />";

		// display feedback for setting
		if ( $proxy_header ) {
			static::proxy_check_header( $proxy_header, $msg, $status, true );

			if ( 'error' === $status ) {
				$dashicon = 'dashicons-no';
				$color    = '#dd3d36';
			} elseif ( 'warning' === $status ) {
				$dashicon = 'dashicons-info';
				$color    = '#ffba00';
			} else {
				$dashicon = 'dashicons-yes';
				$color    = '#7ad03a';
			}
			printf(
				'<p><span style="color:%2$s;font-size:20px;" class="dashicons %3$s" title="%1$s"></span>&nbsp;<em>%1$s</em></p>',
				$msg,
				$color,
				$dashicon
			);
		}
	}

	/**
	 * Sanitize submitted proxy settings.
	 *
	 * @param array $proxy_settings The proxy settings to satitize.
	 *
	 * @return array The sanitized proxy settings
	 */
	public static function proxy_settings_sanitize() {

		$proxy_settings         = array();
		$current_proxy_settings = self::proxy_get_options();

		$msg = $status = '';
		if ( isset( $_POST['proxy_header'] ) and ! static::proxy_check_header( $_POST['proxy_header'], $msg, $status ) ) {
			add_settings_error( 'settings', 'proxy-header-invalid', '<label for="proxy_header">' . __( 'Client IP HTTP header:', 'limit-login-countries' ) . ' ' . $msg . '</label>' );
			$proxy_settings['header'] = $current_proxy_settings['header'];
		} elseif ( 'warning' === $status and ! isset( $_POST['proxy_disable_warning'] ) ) {
			add_settings_error( 'settings', 'proxy-header-warning', '<label for="proxy_header">' . __( 'Client IP HTTP header:', 'limit-login-countries' ) . ' ' . $msg . '</label>' , 'update-nag' );
			$proxy_settings['header'] = $_POST['proxy_header'];
		} else {
			$proxy_settings['header'] = $_POST['proxy_header'];
		}

		// reset to false if a proxy header is set
		if ( ! $proxy_settings['header'] and isset( $_POST['proxy_disable_warning'] ) and ! empty( $_POST['proxy_disable_warning'] ) ) {
			$proxy_settings['disable_warning'] = true;
		} else {
			$proxy_settings['disable_warning'] = false;
		}

		return $proxy_settings;
	}

	/**
	 * Check if $proxy_header is a valid proxy header.
	 *
	 * The HTTP header $proxy_header must be set to a valid non private IP address.
	 *
	 * @since 0.7
	 *
	 * @param string $proxy_header HTTP header to check.
	 * @param bool $geocheck Perform geoIP Lookup during check. default: false.
	 * @param string $msg Contains result as text message.
	 * @param string $status error|warning|success
	 *
	 * @return bool Return true if given proxy header is valid, false otherwise (true on warning).
	 */
	public static function proxy_check_header( &$proxy_header, &$msg = null, &$status = null, $geocheck = false ) {

		$proxy_header = str_replace( '-', '_', strtoupper( $proxy_header ) );
		$proxy_header = filter_var( $proxy_header, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );

		if ( empty( $proxy_header ) ) {
			if ( LLC_GeoIP_Tools::proxy_detected() ) {
				$status = 'warning';
				$msg = 'Reverse proxy detected but no Client IP header set.';
			} else {
				$status = 'success';
				$msg    = '';
			}
			return true;
		}
		elseif ( ! isset( $_SERVER[ $proxy_header ] ) or empty( $_SERVER[ $proxy_header ] ) ) {
			$status = 'error';
			$msg = __( 'The specified HTTP header is not set on the server!', 'limit-login-countries' );

			return false;

		} elseif ( ! filter_var( $_SERVER[ $proxy_header ], FILTER_VALIDATE_IP ) ) {
			$status = 'error';
			// translators: %s is any string.
			$msg = sprintf(
				__(
					'The specified HTTP header does not contain a valid IP address!<br>Header content: <code>%s</code>.',
					'limit-login-countries'
				),
				esc_html( $_SERVER[ $proxy_header ] )
			);

			return false;

		} elseif ( ! filter_var( $_SERVER[ $proxy_header ], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			$status = 'warning';
			// translators: %s is a IP address.
			$msg = sprintf(
				__(
					'Found a private IP address (<code>%s</code>)!',
					'limit-login-countries'
				),
				$_SERVER[ $proxy_header ]
			);

			return true;

		} elseif ( $geocheck and ! $geoInfo = LLC_GeoIP_Tools::get_geo_info( $_SERVER[ $proxy_header ] ) ) {
			$status = 'warning';
			$msg      = sprintf(
			// translators: %1$s is an IP address.
				__(
					'Found IP <code>%1$s</code> which could not be resolved to a country.',
					'limit-login-countries'
				),
				$_SERVER[ $proxy_header ]
			);

			return true;

		} else {
			$status = 'success';
			if ( $geocheck ) {
				/** @noinspection PhpUndefinedVariableInspection */
				$msg      = sprintf(
				// translators: %1$s is an IP address, %2$s a country name and %3$s the corresponding 2-letter country code.
					__(
						'Found IP <code>%1$s</code> which is located in %2$s (%3$s).',
						'limit-login-countries'
					),
					$_SERVER[ $proxy_header ],
					$geoInfo->country_name,
					$geoInfo->country_code
				);
			} else {
				$msg = '';
			}

			return true;
		}
	}

	/**
	 * @return array
	 */
	public static function proxy_get_options() {
		return get_option(
			'llc_proxy_settings',
			array( 'header' => '', 'disable_warning' => false )
		);
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

		global $pagenow;
		if ( ! LLC_Admin::is_settings_page( true ) ) {
			return;
		}

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
