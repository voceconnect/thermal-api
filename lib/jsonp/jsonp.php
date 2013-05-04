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
		$syntax = '/^[_$a-zA-Z][_$a-zA-Z0-9]*[.]*[_$a-zA-Z0-9]*[\[{1}]*["_$a-zA-Z0-9.]*[\]{1}]*/';
		preg_match($syntax, $identifier, $matches);
		if ($matches && $matches[0] === $identifier) {
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