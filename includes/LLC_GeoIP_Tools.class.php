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
	 *	The constructor is declared private to make sure this helper class cannot be instantiated.
	 *
	 * @since 0.4
	 */
	private function __construct() {}

	/**
	 * Looks up visitor's geo information in GeoIP database.
	 *
	 * @since 0.4
	 *
	 * @param $geoIPDatabase
	 *
	 * @return bool|geoiprecord|null Returns geoiprecord on sucess, NULL if no geo info is available and FALSE on error.
	 */
	public static function getGeoInfo($geoIPDatabase) {

		require_once(dirname(__DIR__).'/vendor/geoip/geoipcity.inc');

		if( !empty($geoIPDatabase) and is_readable($geoIPDatabase) )
			$gi = @geoip_open($geoIPDatabase, GEOIP_STANDARD);
		else
			return FALSE;

		if( LLC_GeoIP_Tools::isIPv4() ) {
			$geoInfo = geoip_record_by_addr($gi, $_SERVER['REMOTE_ADDR']);
		} elseif( LLC_GeoIP_Tools::isIPv6() ) {
			$geoInfo = geoip_record_by_addr_v6($gi, $_SERVER['REMOTE_ADDR']);
		} else {
			$geoInfo = FALSE;
			trigger_error('Invalid IP address in $_SERVER[\'REMOTE_ADDR\']: [' . $_SERVER['REMOTE_ADDR'] . ']', E_USER_WARNING);
		}
		geoip_close( $gi );

		return $geoInfo;
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

		$wpud = wp_upload_dir();
		$try_paths[] = $wpud['basedir'];
		$try_paths[] = dirname(__DIR__);

		$res = array();

		foreach($try_paths as $p) {
			$iterator = new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
			$iterator = new GeoIPDatabaseSearchFilter($iterator);
			$iterator = new RecursiveIteratorIterator($iterator);
			foreach($iterator as $file) {
				// we don't want directories.
				if( $file->isDir() ) continue;
				$res[] = array($file->getPathInfo().DIRECTORY_SEPARATOR.$file->getBasename(), $file->getMTime());
			}
		}
		return $res;
	}

	/**
	 * Check if user's IP address is v4.
	 *
	 * @since 0.1
	 *
	 * @return bool Returns TRUE if user's IP address is an IPv4 address, FALSE otherwise.
	 */
	public static function isIPv4() {
		return FALSE !== filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * Check if user's IP address is v6.
	 *
	 * @since 0.1
	 *
	 * @return bool Returns TRUE if user's IP address is an IPv6 address, FALSE otherwise.
	 */
	public static function isIPv6() {
		return FALSE !== filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
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
		$names= array('geoip', 'geoiplite', 'geoipv6', 'geoipcity', 'geolitecity', 'geolitecityv6');
		return $this->current()->isReadable() && in_array(strtolower($this->current()->getBasename('.dat')), $names) && 'dat' === strtolower($this->current()->getExtension());
	}
}