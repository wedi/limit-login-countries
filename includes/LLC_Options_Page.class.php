<?php
/**
 * Class LLC_Options_Page Contains helper functions for the plugin's settings page.
 *
 * This class encapsulates display and change of our plugin's options.
 *
 * @package Limit Login Countries
 * @author: Dirk Weise
 * @since: 0.3
 *
 */
class LLC_Options_Page {

	/**
	 * The constructor is declared private to make sure this helper class cannot be instantiated.
	 *
	 * @since 0.3
	 */
	private function __construct() {}

	/**
	 * Sets up hooks and stuff.
	 *
	 * @since 0.3
	 */
	public static function init() {

		// we add a callback on admin_init hook to register our settings
		add_action('admin_init', array('LLC_Options_Page', 'register_settings'));

		// we add a callback on admin_menu hook to add our options page
		add_action('admin_menu', array('LLC_Options_Page', 'settings_menu'));

		// we add a callback on the incredible admin_print_scripts-settings_limit-login-countries hook to register and enqueue our scripts only on our own settings page
		add_action('admin_print_scripts-settings_page_limit-login-countries', array('LLC_Options_Page', 'enqueue_scripts'));

	}

	/**
	 * Registers all our settings with WP's settings API.
     * Callback function for WP's admin_init hook.
	 *
     * @see LLC_Options_Page::init()
	 * @see http://codex.wordpress.org/Settings_API
	 * @since 0.3
	 */
	public static function register_settings() {

		// we register all our settings
		register_setting( 'limit-login-countries', 'llc_geoip_database_path', array('LLC_Options_PAGE', 'geoip_database_path_validate') );
		register_setting( 'limit-login-countries', 'llc_blacklist', array('LLC_Options_PAGE', 'blacklist_validate') );
		register_setting( 'limit-login-countries', 'llc_countries', array('LLC_Options_PAGE', 'countries_validate') );

		// we add settings sections
		add_settings_section( 'llc-general', __('General Settings','limit-login-countries'), array('LLC_Options_Page', 'general_settings_callback'), 'limit-login-countries' );
		add_settings_section( 'llc-geoip', __('GeoIP Database','limit-login-countries'), array('LLC_Options_Page', 'geoip_settings_callback'), 'limit-login-countries' );

		// we add settings to our settings sections
		add_settings_field( 'llc_blacklist', __('Act as', 'limit-login-countries'), array('LLC_Options_Page', 'blacklist_callback'), 'limit-login-countries', 'llc-general', array( 'label_for' => 'llc_blacklist' ) );

        // we figure out the appropriate label
		if(get_option('llc_blacklist', 'whitelist') === 'whitelist')
            $label = __('Exclusive list of allowed countries:', 'limit-login-countries');
        else
            $label = __('Exclusive list of rejected countries:', 'limit-login-countries');
        add_settings_field( 'llc_countries', $label, array('LLC_Options_Page', 'countries_callback'), 'limit-login-countries', 'llc-general', array( 'label_for' => 'llc_countries' ) );

		add_settings_field( 'llc_geoip_database_path', __('GeoIP database file', 'limit-login-countries'), array('LLC_Options_Page', 'geoip_database_path_callback'), 'limit-login-countries', 'llc-geoip', array( 'label_for' => 'llc_geoip_database_path' ) );
	}

	public static function general_settings_callback() {

		$r = '<p>'.__('Here you configure from which countries admin area logins are allowed.', 'limit-login-countries').'</p>';
		$r .= '<p><em>' . sprintf(__('<strong>Remember:</strong> In case you lock yourself out of WP\'s admin area you can disable the country check by adding %s to your <code>wp-config.php</code> file.', 'limit-login-countries'), '<code>define(\'LIMIT_LOGIN_COUNTRIES_OVERRIDE\', TRUE);</code>').'</em></p>';

		echo $r;
	}

	public static function geoip_settings_callback() {

		$r = '<p>' . sprintf(__('This plugin works with <a href="%1$s" target="_blank">Maxmind\'s GeoIP database</a>. If you are not a paying customer, you can download a lite version for free: <a href="%2$s" title="Direct Download">Download Maxmind\'s GeoIP Lite database</a>.', 'limit-login-countries'), 'http://dev.maxmind.com/geoip/', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz') . '</p>';
		$r .= '<p><em>' . __('<strong>Note:</strong> It\'s planned to upgrade this plugin to use the new database format ("GeoIP2") soon, but at the moment we still use the legacy ("old") format.', 'limit-login-countries') . '</em></p>';

		echo $r;
	}

	public static function geoip_database_path_callback() {

		$setting = esc_attr(get_option('llc_geoip_database_path'));
		echo "<input type='text' id='llc_geoip_database_path' name='llc_geoip_database_path' value='$setting' size='60' />";

		if('' === $setting) {
			require_once('LLC_GeoIP_Tools.class.php');
			$gds = LLC_GeoIP_Tools::search_geoip_database();
			echo '<p>'.__('For your convenience we tried to find a database file. We searched this plugin\'s directory as well as your WordPress upload directory.','limit-login-countries').'</p>';
			if( count($gds) > 0 ) {
				echo '<ul>';
				foreach($gds as $gd) {
					echo '<li><code>' . $gd[0] . '</code> (' . strftime('%x %T %Z', $gd[1]) . ')</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>'.__('Unfortunately we couldn\'t find any database file.<br/>If you are sure you uploaded (and unzipped) one, it is probably just named differently from what we expected. No worries, just enter the correct absolute path to your database file above and we are all fine.','limit-login-countries').'</p>';
			}
		}

	}
    public static function geoip_database_path_validate( $input ) {

        $output = get_option( 'llc_geoip_database_path' );
        $input = realpath($input);

        if( 0 !== stripos($input, ABSPATH) )
            add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-existent', __('The specified GeoIP database file is outside the WordPress directory and thus not allowed for security reasons.', 'limit-login-countries') );
        elseif ( !file_exists( $input ) )
            add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-existent', __('The specified GeoIP database file does not exist.', 'limit-login-countries') );
        elseif ( !is_readable( $input ) )
            add_settings_error( 'llc_geoip_database_path', 'geoip-database-not-readable', __('The specified GeoIP database file is not readable.', 'limit-login-countries') );
        else
            $output = $input;

        return $output;
    }

	public static function blacklist_callback() {

		$s = get_option('llc_blacklist', 'whitelist');

		if('whitelist'===$s) {
			$ws = ' selected="selected"';
			$bs = '';
		} else {
			$ws = '';
			$bs = ' selected="selected"';
		}

        $r = '';

        $r .= '<select id="llc_blacklist" name="llc_blacklist">';
		$r .= '<option value="whitelist"'.$ws.'>'.__('Whitelist', 'limit-login-countries').'</option>';
		$r .= '<option value="blacklist"'.$bs.'>'.__('Blacklist', 'limit-login-countries').'</option>';
		$r .= '</select>';

        $r .= '<ul>';
        $r .= '<li>'.__('<em>Whitelist</em> means login is allowed from the countries listed below only.', 'limit-login-countries').'</li>';
        $r .= '<li>'.__('<em>Blacklist</em> means login is not allowed from the countries listed below only.', 'limit-login-countries').'</li>';
        $r .= '</ul>';

        echo $r;
	}

    public static function blacklist_validate( $input ) {

        $output = get_option('llc_blacklist', 'whitelist');
        if ( 'whitelist' === $input or 'blacklist' === $input )
            $output = $input;
        else
            add_settings_error( 'llc_blacklist', 'llc-invalid-value', __('Invalid value. You must select either whitelist or blacklist.', 'limit-login-countries') );

        return $output;
    }

    public static function countries_callback() {

		$setting = esc_attr(get_option('llc_countries'));
        $r = "<input type='text' id='llc_countries' name='llc_countries' value='$setting'>";
        $r .= "<div id='llc_test' />";

        $r .= '<ul>';
        $r .= '<li>'.__('List of 2-digit country codes.', 'limit-login-countries').'</li>';
        $r .= '<li class="no-js">'.__('Use a comma as delimiter.', 'limit-login-countries').'</li>';
        $r .= '<li>'.__('If list is empty, no login restriction applies.', 'limit-login-countries').'</li>';
        $r .= '</ul>';

        echo $r;


        $llc_countries_label['whitelist'] = __('Exclusive list of allowed countries:', 'limit-login-countries');
        $llc_countries_label['blacklist'] = __('Exclusive list of rejected countries:', 'limit-login-countries');
        wp_localize_script('limit-login-countries', 'llc_countries_label', $llc_countries_label) ;

        require_once('LLC_GeoIP_Countries.class.php');
        $gc = new LLC_GeoIP_Countries();
        $gc->wp_localize_country_codes();
    }

    public static function countries_validate( $input ) {


        $countries = array_unique( explode(',', trim( strtoupper( preg_replace("/[^,a-zA-Z]/", "", $input) ), ',' ) ) );
        require_once('LLC_GeoIP_Countries.class.php');
        $gc = new LLC_GeoIP_Countries();

        $output = array_filter($countries, function ($var) use ($gc) {return in_array($var, $gc->country_codes);});

        return implode(',', $output);
    }

	/**
	 * Adds our options page to the admin area.
     * Callback function for WP's hook 'admin_menu'.
	 *
     * @see LLC_Options_Page::init()
	 * @since 0.3
	 */
	public static function settings_menu() {

		add_options_page( __('Limit Login Countries Options', 'limit-login-countries'),
						  __('Login Countries', 'limit-login-countries'),
						  'manage_options',
						  'limit-login-countries',
						  array('LLC_Options_Page', 'settings_page')
		);
	}

	/**
	 * Prints the actual settings page.
     * Callback function for add_option_page
	 *
     * @see LLC_Options_Page::settings_menu()
	 * @since 0.3
	 *
	 * @return bool
	 */
	public static function settings_page() {

		// we make sure the current user has sufficient capabilities to fiddle with our options
		if ( !current_user_can('manage_options') )  {
			wp_die( __('You do not have sufficient permissions to access this page.', 'limit-login-countries') );
		}?>

		<div class="wrap" id="llc-options-page">
		<div id="icon-options-general" class="icon32"><br></div>
		<h2><?php echo __('Settings', 'limit-login-countries') . '&nbsp;&rsaquo;&nbsp;' . __('Limit Login Countries', 'limit-login-countries'); ?></h2>
		<form action="options.php" method="post"><?php
        settings_fields('limit-login-countries');
		do_settings_sections('limit-login-countries');
		submit_button();
        ?>
		</form>
		</div>
        <?php
		return TRUE;
	}

    /**
     * Registers and enqueues scripts and stylesheets on options page.
     * Callback function for automagically created WP hook 'admin_print_scripts-settings_page_limit-login-countries'
     *
     * @see LLC_Options_Page::init()
     * @since 0.4
     *
     * @return void
     */
    public static function enqueue_scripts() {
		$url = plugins_url('/', __DIR__);
		wp_register_script('textext-core', $url . 'vendor/TextExt/js/textext.core.js', array('jquery-core'), '1.3.1', true);
		wp_register_script('textext-autocomplete', $url . 'vendor/TextExt/js/textext.plugin.autocomplete.js', array('textext-core'), '1.3.1', true);
		wp_register_script('textext-filter', $url . 'vendor/TextExt/js/textext.plugin.filter.js', array('textext-core'), '1.3.1', true);
		wp_register_script('textext-tags', $url . 'vendor/TextExt/js/textext.plugin.tags.js', array('textext-core'), '1.3.1', true);
		//wp_register_script('textext-suggestions', $url . 'vendor/TextExt/js/textext.plugin.suggestions.js', array('textext-core'), '1.3.1', true);
		wp_enqueue_script('limit-login-countries', $url . 'js/limit-login-countries.js', array('textext-autocomplete', 'textext-tags', 'textext-filter'), '0.4', true);


		wp_register_style('textext-core', $url . 'vendor/TextExt/css/textext.core.css', array(), '0.4');
		wp_register_style('textext-autocomplete', $url . 'vendor/TextExt/css/textext.plugin.autocomplete.css', array('textext-core'), '0.4');
		wp_register_style('textext-tags', $url . 'vendor/TextExt/css/textext.plugin.tags.css', array('textext-core'), '0.4');
		wp_enqueue_style('limit-login-countries', $url . 'css/limit-login-countries.css', array('textext-autocomplete','textext-tags'), '0.4');
	}

}