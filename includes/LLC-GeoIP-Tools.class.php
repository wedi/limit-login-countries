<?php

/**
 * Class LLC_GeoIP_Tools Contains helper functions for handling the GeoIP
 * database.
 *
 * @package Limit Login Countries
 * @author: Dirk Weise
 * @since 0.4
 */
class LLC_GeoIP_Tools {

	public static $geoIPDatabase;

	public static $proxy_client_header = false;

	/**
	 * The constructor is declared private to make sure this helper class
	 * cannot be instantiated.
	 *
	 * @since 0.4
	 */
	private function __construct() {
	}

	/**
	 * Look up visitor geo information in GeoIP database.
	 *
	 * @since 0.4
	 *
	 * @param null $ip Optional IP address to retrieve info for. Uses the current
	 *                 visitor's IP if not set.
	 *
	 * @return geoiprecord|null|false geoiprecord on sucess, null if no
	 *                                record was found and false on error.
	 */
	public static function get_geo_info( $ip = null ) {

		if ( ! $ip ) {
			$ip = static::get_visitor_ip();
		}

		require_once( dirname( __DIR__ ) . '/vendor/geoip/geoipcity.inc' );
		$gi = geoip_open( static::$geoIPDatabase, GEOIP_STANDARD );

		if ( self::is_ip_v4( $ip ) ) {
			$geoInfo = geoip_record_by_addr( $gi, $ip );
		} elseif ( self::is_ip_v6( $ip ) ) {
			$geoInfo = geoip_record_by_addr_v6( $gi, $ip );
		} else {
			$geoInfo = false;
			trigger_error( 'Invalid IP address [' . $ip . ']', E_USER_WARNING );
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
	 * @param int &$build_date Variable is set to build date timestamp if GeoIP
	 *                         database is valid.
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
	 * Return GeoIP database publish date or false if no GeoIP database.
	 *
	 * @param $geoip_db_file string Path to GeoIP database file to check.
	 *
	 * @return int|false GeoIP database release date timestamp or false if no
	 *                     valid GeoIP database is recognized.
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
	 * Check if IP address is v4.
	 *
	 * @since 0.1
	 *
	 * @param $ip String IP address to check (since 0.7).
	 *
	 * @return bool True if user's IP address is IPv4, false otherwise.
	 */
	public static function is_ip_v4( $ip ) {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * Check if IP address is v6.
	 *
	 * @since 0.1
	 *
	 * @param $ip String IP address to check (since 0.7).
	 *
	 * @return bool True if user's IP address is IPv6, false otherwise.
	 */
	public static function is_ip_v6( $ip ) {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}

	/**
	 * Return the current visitor's IP address according to proxy setting.
	 *
	 * @return string IP Address of current visitor.
	 */
	public static function get_visitor_ip() {
		return
			static::$proxy_client_header
			? $_SERVER[ strtoupper( static::$proxy_client_header ) ]
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Try to detect a proxy to notify the admin in settings.
	 * Based on http://stackoverflow.com/a/21765661
	 *
	 * @return array|false array of proxy headers (might be empty) or
	 *                     false if no proxy detected
	 */
	public static function detect_proxy() {
		$proxy_headers = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_VIA',
		);

		$detected = false;

		// check for common proxy set headers
		$detected_headers = array();
		foreach ( $proxy_headers as $proxy_header ) {
			if ( isset( $_SERVER[ $proxy_header ] ) and $_SERVER[ $proxy_header ] != $_SERVER['REMOTE_ADDR'] ) {
				$detected = true;
				if ( filter_var( $_SERVER[ $proxy_header ], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					$detected_headers[ $proxy_header ] = $_SERVER[ $proxy_header ];
				}
			}
		}

		// one last check
		if ( ! $detected
			and filter_var(
				$_SERVER['REMOTE_ADDR'],
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			)
			and fsockopen( $_SERVER['REMOTE_ADDR'], 80, $errno, $errstr, 8 )
		) {
			$detected = true;
		};

		return $detected ? $detected_headers : false;
	}

	/**
	 * Search for GeoIP database files.
	 *
	 * This function searches the current plugindirs folder as well as
	 * WordPress' upload folder for files with various geoip related names
	 *
	 * @since 0.4
	 *
	 * @return array List of Arrays with info about found GeoIP databases. [0] => path, [1] => FileMTime
	 */
	public static function search_geoip_database() {

		$search_paths[] = WP_CONTENT_DIR;
		$search_paths[] = dirname( __DIR__ );
		$wpud = wp_upload_dir();
		$wpud = $wpud['basedir'];
		if ( false === strpos( $wpud, $search_paths[0] ) ) {
			$search_paths[] = $wpud;
		}
		if ( defined( 'LIMIT_LOGIN_COUNTRIES_SEARCH_PATH' ) ) {
			$search_paths =
				is_array( LIMIT_LOGIN_COUNTRIES_SEARCH_PATH )
				? LIMIT_LOGIN_COUNTRIES_SEARCH_PATH
				: array( LIMIT_LOGIN_COUNTRIES_SEARCH_PATH );
		}

		$res = array();
		foreach ( $search_paths as $p ) {
			if ( ! is_readable( $p ) ) {
				continue;
			}
			$p = realpath( $p );

			$iterator = new RecursiveDirectoryIterator( $p, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_SELF | FilesystemIterator::KEY_AS_PATHNAME );
			$iterator = new GeoIPDatabaseSearchFilter( $iterator );
			$iterator = new RecursiveIteratorIterator( $iterator );
			$iterator->setMaxDepth( 5 );
			foreach ( $iterator as $file ) {
				$filepath = $file->key();
				$res[ $filepath ] = array( 'filepath' => $filepath, 'CTime' => filectime( $filepath ) );
			}
			foreach ( $res as $key => $r ) {
				if ( self::is_valid_geoip_database( $r['filepath'], $msg, $ts ) ) {
					$res[ $key ]['publish_date'] = $ts;
				} else {
					unset( $res[ $key ] );
				}
			}
		}

		return $res;
	}

}

/**
 * A filter class for PHP's iterator that filters for typical GeoIP database
 * file names.
 *
 * @since 0.4
 *
 * @extends RecursiveFilterIterator
 *
 */
class GeoIPDatabaseSearchFilter extends RecursiveFilterIterator {

	static $cache = array();

	/**
	 * Check if readable files match a typical GeoIP database file name.
	 *
	 * @since 0.4
	 *
	 * @return bool True if file passes our filter, false otherwise.
	 */
	public function accept() {

		$file = $this->current()->key();
		$filename = strtolower( basename( $file ) );
		// skip everything starting with a dot, thinking of huge .git folders.
		if ( '.' === $filename[0] ) {
			return false;
		}
		if ( isset( self::$cache[ $file ] ) ) {
			return false;
		} else {
			self::$cache[ $file ] = 1;
		}
		if ( is_dir( $file ) ) {
			return true;
		}
		if ( '.dat' == substr( $filename, -4 ) and self::looks_like_geoip_db_file( $file, $filename ) and is_readable( $file ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given file(path) looks like a GeoIP file path.
	 *
	 * To pass a file path the following conditions must be met.
	 *  - Given path ends with '.dat'
	 *  - Given path contains either 'geoip' or 'geolite' or
	 *    filename contains 'geo'.
	 *
	 * @since    0.7
	 *
	 * @param $full_path
	 * @param $filename
	 *
	 * @internal param $geoip_db_file
	 *
	 * @return bool
	 */
	public static function looks_like_geoip_db_file( $full_path, $filename = null ) {
		return (
			stripos( $filename, 'geoip' ) !== false
			or stripos( $filename, 'geolite' ) !== false
			or stripos( basename( $full_path ), 'geo' ) !== false
		);
	}
}
