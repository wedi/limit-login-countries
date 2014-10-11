<?php

/**
 * Class LLC_GeoIP_Tools Contains helper functions for handling the GeoIP database.
 *
 * @package Limit Login Countries
 * @author: Dirk Weise
 * @since 0.4
 */
class LLC_GeoIP_Tools {

	/**
	 *    The constructor is declared private to make sure this helper class cannot be instantiated.
	 *
	 * @since 0.4
	 */
	private function __construct() {
	}

	/**
	 * Looks up visitor's geo information in GeoIP database.
	 *
	 * @since 0.4
	 *
	 * @param $geoIPDatabase
	 *
	 * @return bool|geoiprecord|null Returns geoiprecord on sucess, NULL if no geo info is available and FALSE on error.
	 */
	public static function getGeoInfo( $geoIPDatabase ) {

		require_once( dirname( __DIR__ ) . '/vendor/geoip/geoipcity.inc' );

		if ( ! empty( $geoIPDatabase ) and is_readable( $geoIPDatabase ) ) {
			$gi = geoip_open( $geoIPDatabase, GEOIP_STANDARD );
		} else {
			return false;
		}

		if ( LLC_GeoIP_Tools::isIPv4() ) {
			$geoInfo = geoip_record_by_addr( $gi, $_SERVER['REMOTE_ADDR'] );
		} elseif ( LLC_GeoIP_Tools::isIPv6() ) {
			$geoInfo = geoip_record_by_addr_v6( $gi, $_SERVER['REMOTE_ADDR'] );
		} else {
			$geoInfo = false;
			trigger_error( 'Invalid IP address in $_SERVER[\'REMOTE_ADDR\']: [' . $_SERVER['REMOTE_ADDR'] . ']', E_USER_WARNING );
		}
		geoip_close( $gi );

		return $geoInfo;
	}

	/**
	 * Check if a given GeoIP database file exists, is readable and valid.
	 *
	 * @since 0.7
	 *
	 * @param $geoip_db_file string Path to GeoIP database file to check.
	 * @param &$msg string Variable is set to a result message.
	 * @param int &$build_date Variable is set to build date timestamp if GeoIP database is valid.
	 *
	 * @return bool True if valid GeoIP database file given, false otherwise.
	 */
	public static function is_valid_geoip_database( $geoip_db_file, &$msg = '', &$build_date = 0 ) {

		if ( empty( $geoip_db_file ) ) {
			$msg = __( 'You have not specified a GeoIP database.', 'limit-login-countries' );

		} elseif ( ! file_exists( $geoip_db_file ) ) {
			$msg = __( 'The specified GeoIP database file does not exist or your file access permissons aren\'t sufficient.', 'limit-login-countries' );

		} elseif ( ! is_readable( $geoip_db_file ) ) {
			$msg = __( 'The specified GeoIP database file is not readable.', 'limit-login-countries' );

		} elseif ( false === $ts = self::get_database_build_date( $geoip_db_file ) ) {
			$msg = __( 'The specified GeoIP database file is invalid.', 'limit-login-countries' );

		} else {
			$build_date = $ts;
			$msg = sprintf( __( 'The GeoIP database file (published on %s) is valid.', 'limit-login-countries' ), date( 'd-m-Y', $ts ) );

			return true;
		}

		// we only expose whether a file exists / is readable if it looks like a geoip file to prevent arbitrary filesystem exploration
		if ( ! GeoIPDatabaseSearchFilter::looks_like_geoip_db_file( $geoip_db_file ) ) {
			$msg = __( 'The specified GeoIP database file is invalid.', 'limit-login-countries' );
		}

		return false;
	}

	/**
	 * Return GeoIP database edition or false if no GeoIP database.
	 *
	 * @param $geoip_db_file string Path to GeoIP database file to check.
	 *
	 * @return string|bool GeoIP database release date timestamp or false if no valid GeoIP database is recognized.
	 */
	public static function get_database_build_date( $geoip_db_file ) {

		$transient = 'llc_geoip_file_' . sha1_file( $geoip_db_file );
		if ( $d = get_transient( $transient ) ) {
			return $d;
		}

		if ( ! $h = fopen( $geoip_db_file, 'rb' ) ) {
			return false;
		}
		if ( ! fseek( $h, -100, SEEK_END ) === -1 ) {
			return false;
		}
		if ( ! $d = fread( $h, 100 ) ) {
			return false;
		}
		fclose( $h );

		if ( false === strpos( strtolower( $d ), 'maxmind inc' ) ) {
			return false;
		}
		if ( false === $d = substr( $d, strpos( $d, 'GEO-' ) ) ) {
			return false;
		}
		if ( false === $d = substr( $d, 0, strpos( $d, 'Reserved' ) + 8 ) ) {
			return false;
		}
		// From now on we assume the file is valid and don't return false anymore.
		// By now $d should look like:
		// GEO-106FREE 20141007 Build 1 Copyright (c) 2014 MaxMind Inc All Rights Reserved
		$d = explode( ' ', $d );
		try {
			// Using 'America/New_York' because that's MaxMind's timezone.
			if ( ! $d = DateTime::createFromFormat( '!Ymd', $d[1], new DateTimeZone( 'America/New_York' ) ) ) {
				return 1;
			}
			set_transient( $transient, $d->getTimestamp(), 20 );
		} catch ( Exception $e ) {
			return 1;
		}

		return $d->getTimestamp();
	}

	/**
	 * Check if user's IP address is v4.
	 *
	 * @since 0.1
	 *
	 * @return bool Returns TRUE if user's IP address is an IPv4 address, FALSE otherwise.
	 */
	public static function isIPv4() {
		return false !== filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * Check if user's IP address is v6.
	 *
	 * @since 0.1
	 *
	 * @return bool Returns TRUE if user's IP address is an IPv6 address, FALSE otherwise.
	 */
	public static function isIPv6() {
		return false !== filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}

	/**
	 * Searches for GeoIP database files
	 * This function searches the current plugindirs folder as well as WordPress' upload folder for files with various geoip related names
	 *
	 * @since 0.4
	 *
	 * @return array List of Arrays with info about found GeoIP databases. [0] => path, [1] => FileMTime
	 */
	public static function search_geoip_database() {

		$wpud        = wp_upload_dir();
		$try_paths[] = $wpud['basedir'];
		$try_paths[] = dirname( __DIR__ );

		$res = array();

		foreach ( $try_paths as $p ) {
			$iterator = new RecursiveDirectoryIterator( $p, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
			$iterator = new GeoIPDatabaseSearchFilter( $iterator );
			$iterator = new RecursiveIteratorIterator( $iterator );
			foreach ( $iterator as $file ) {
				// we don't want directories.
				if ( $file->isDir() ) {
					continue;
				}
				$res[] = array( $file->getPathInfo() . DIRECTORY_SEPARATOR . $file->getBasename(), $file->getMTime() );
			}
		}

		return $res;
	}

}

/**
 * Class GeoIPDatabaseSearchFilter extends RecursiveFilterIterator
 * A filter class for PHP's iterator that (hopefully) matches GeoIP database files.
 *
 * @since 0.4
 */
class GeoIPDatabaseSearchFilter extends RecursiveFilterIterator {
	/**
	 * Implements filter.
	 *
	 * @since 0.4
	 *
	 * @return bool Returns TRUE if file passes our filter, FALSE otherwise.
	 */
	public function accept() {
		$names = array( 'geoip', 'geolite', 'geoiplite', 'geoipv6', 'geoipcity', 'geolitecity', 'geolitecityv6' );

		return $this->current()->isReadable() and in_array( strtolower( $this->current()->getBasename( '.dat' ) ), $names ) and 'dat' === strtolower( $this->current()->getExtension() );
	}

	/**
	 * Check if a given file(path) looks like a GeoIP file path.
	 *
	 * To pass a file path the following conditions must be met.
	 *  - Given path ends with '.dat'
	 *  - Given path contains either 'geoip' or 'geolite' or
	 *    filename contains 'geo'.
	 *
	 * @since 0.7
	 *
	 * @param $geoip_db_file
	 *
	 * @return bool
	 */
	public static function looks_like_geoip_db_file( $geoip_db_file ) {
		$geoip_db_file = strtolower( $geoip_db_file );
		return ( '.dat' === substr( $geoip_db_file, - 4, 4 ) and (
			strpos( $geoip_db_file, 'geoip' ) !== false
			or strpos( $geoip_db_file, 'geolite' ) !== false
			or strpos( basename( $geoip_db_file ), 'geo' ) !== false )
		);
	}
}
