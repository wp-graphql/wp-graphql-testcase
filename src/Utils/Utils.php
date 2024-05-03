<?php
/**
 * Utils class
 * 
 * @since v3.0.0
 * @package Tests\WPGraphQL\Utils
 */

namespace Tests\WPGraphQL\Utils;

class Utils {
    /**
	 * The value returned for undefined resolved values.
	 *
	 * Clone of the "get" function from the Lodash JS libra
	 *
	 * @param array  $object   The object to query.
	 * @param string $path     The path of the property to get.
	 * @param mixed  $default  The value returned for undefined resolved values.
	 * @return void
	 */
    public static function lodashGet( array $data, string $string, $default = null ) {
		$arrStr = explode( '.', $string );
		if ( ! is_array( $arrStr ) ) {
			$arrStr = [ $arrStr ];
		}

		$result = $data;
		foreach ( $arrStr as $lvl ) {
			if ( ! is_null( $lvl ) && isset( $result[ $lvl ] ) ) {
				$result = $result[ $lvl ];
			} else {
				$result = $default;
			}
		}

		return $result;
	}
}