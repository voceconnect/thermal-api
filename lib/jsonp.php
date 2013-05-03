<?php
namespace Voce;

/**
 * Class JSONP
 *
 * @package Voce
 */
class JSONP {

	/**
	 * @var array
	 */
	public static $reserved_words
		= array(
			'abstract', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class',
			'const', 'continue', 'debugger', 'default', 'delete', 'do', 'double',
			'else', 'enum', 'export', 'extends', 'false', 'final', 'finally', 'float',
			'for', 'function', 'goto', 'if', 'implements', 'import', 'in', 'instanceof',
			'int', 'interface', 'long', 'native', 'new', 'null', 'package', 'private',
			'protected', 'public', 'return', 'short', 'static', 'super', 'switch',
			'synchronized', 'this', 'throw', 'throws', 'transient', 'true', 'try',
			'typeof', 'var', 'void', 'volatile', 'while', 'with',
		);

	/**
	 * @param $identifier
	 *
	 * @return bool
	 */
	public static function is_utf8($identifier) {
		if (mb_check_encoding($identifier, 'UTF-8')) {
			return true;
		}
		return false;
	}

	/**
	 * @param $identifier
	 *
	 * @return bool
	 */
	public static function has_reserved_word($identifier) {

		return in_array( $identifier, self::$reserved_words );

	}

	/**
	 * @param $identifier
	 *
	 * @return bool
	 */
	public static function has_valid_syntax($identifier) {
		// TODO: accept square bracket notation (nested?)
		$syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}\.]*+$/u';

		if (preg_match($syntax, $identifier) == 1) {
			return true;
		}
		return false;
	}

	/**
	 * @param $identifier
	 *
	 * @return bool
	 */
	public static function is_valid_callback($identifier) {
		if (self::is_utf8($identifier) === false) {
			return false;
		}
		if (self::has_reserved_word($identifier) === true) {
			return false;
		}
		if (self::has_valid_syntax($identifier) === false) {
			return false;
		}
		return true;
	}

}