#!/usr/bin/php
<?php

/**
 * This script reads country data from GeoIP API and creates
 * 'includes/LLC-GeoIP-Countries.class.php' with translatable
 * country data arrays
 *
 * @package Limit Login Countries
 * @author: Dirk Weise
 * @since 0.4
 *
 */

$output_file = '/includes/LLC-GeoIP-Countries.class.php';

echo 'Extracting country data from vendor/geoip/geoip.inc ... ';
require_once( __DIR__ . '/../vendor/geoip/geoip.inc' );
echo 'OK.' . chr( 10 );

$g = new GeoIP();

$countryCodeNumbers = $g->GEOIP_COUNTRY_CODE_TO_NUMBER;
ksort( $countryCodeNumbers );
$countryNames = $g->GEOIP_COUNTRY_NAMES;
$countryCodes = $g->GEOIP_COUNTRY_CODES;

$r = <<<'EOF'
<?php

/**
 * Contains translatable country data, programmatically extracted from
 * '/vendor/geoip/geoip.inc'
 *
 * @package Limit Login Countries
 * @author  Dirk Weise
 * @since   0.4
 */
class LLC_GeoIP_Countries {

	/**
	 * @var array $country_data Country names in associative array, key is the respective two letter country code.
	 */
	public $country_data = array();

	/**
	 * @var array $country_data_r Country codes in associative array, key is the localized country name.
	 */
	public $country_data_r = array();

	/**
	 * @var array $country_data_r Country codes in associative array, key is the localized country name.
	 */
	public $country_names = array();

	/**
	 * @var array $country_codes Country codes in array.
	 */
	public $country_codes =
EOF;

$v = var_export( $countryCodes, true );
// we do some transformations to make the output look nice / adhere to the coding style
$v = preg_replace( '/^  /m', '		', $v );
$v = preg_replace( '/array \(/', 'array(', $v );
$v = preg_replace( '/^\)/m', '	)', $v );
$v = preg_replace( '/\t\t([0-9]{1,1}) =>/m', '		\1   =>', $v );
$v = preg_replace( '/\t\t([0-9]{2,2}) =>/m', '		\1  =>', $v );

$r .= ' ' . $v . ';';
$r .= <<<'EOF'


	/**
	 * Constructor fills our country data arrays. Static definition is not
	 * possible because we want to translate all strings.
	 *
	 * @since 0.4
	 */
	public function __construct() {

EOF;

//$numCountries = count($countryCodeNumbers);
//$i = 0;
$r2 = $r3 = '';
foreach ( $countryCodeNumbers as $countryCode => $countryCodeNumber ) {

	// skip emtpy entrys
	if ( '' === $countryCode ) {
		continue;
	}

	// we check if country name contains a comma, and swap parts if needed
	if ( strstr( $countryNames[ $countryCodeNumber ], ',' ) and ! strstr( $countryNames[ $countryCodeNumber ], ' and ' ) ) {
		list( $a, $b ) = explode( ',', $countryNames[ $countryCodeNumber ] );
		$cCountryNames = trim( $b ) . ' ' . trim( $a );
	} else {
		$cCountryNames = $countryNames[ $countryCodeNumber ];
	}

	// we create
	$r .= chr( 9 ) . chr( 9 ) . '$this->country_data[\'' . $countryCode . '\'] = __( \'' . addslashes( $cCountryNames ) . '\', \'limit-login-countries\' );' . chr( 10 );
	$r2 .= chr( 9 ) . chr( 9 ) . '$this->country_data_r[ __( \'' . addslashes( $cCountryNames ) . '\', \'limit-login-countries\' ) ] = \'' . $countryCode . '\';' . chr( 10 );
	$r3 .= chr( 9 ) . chr( 9 ) . '$this->country_names[] = __( \'' . addslashes( $cCountryNames ) . '\', \'limit-login-countries\' );' . chr( 10 );
}

$r .= chr( 10. ) . $r2;
$r .= chr( 10. ) . $r3;

$r .= <<<'EOF'
	}

	/**
	 * Prints an array of all available country codes for use in JavaScript.
	 *
	 * @since 0.6
	 */
	public function wp_localize_country_codes() {
		wp_localize_script( 'limit-login-countries', 'LLC_COUNTRY_CODES', $this->country_codes );
	}
}

EOF;

echo "Writing to $output_file ... ";
$of = fopen( dirname( __DIR__ ) . $output_file, 'w' ) or die( 'ERROR: Could not open output file for writing!' . chr( 10 ) );

if ( file_exists( dirname( __DIR__ ) . $output_file ) ) {
	echo 'WARNING! Output file exists and will be overwritten! ';
}

fwrite( $of, $r );
echo 'OK.' . chr( 10 );
fclose( $of );

echo 'We\'re done!' . chr( 10 );
