<?php

/**
 * RightPack
 *
 * pack() and unpack(), done the right way.
 *
 * @package		RightPack
 * @author		Daniil Gentili <daniil@daniil.it>
 * @license		MIT license
*/

class FileServe {
	const $formatinfo = [];
	const $modifiers = ["<" => ];
	public function pack($format, ...$data) {
		$count = count($data);
		$packcommand = [];
		$current = 0;
		foreach (str_split($format) as $currentformat) {
			if(isset($modifiers[$currentformat])) {
				$packcommand[$current]["format_info"] = $modifiers[$currentformat];
			} elseif(isset($formatinfo[$currentformat])) {
				$packcommand[$current]["format"]
	}
}
