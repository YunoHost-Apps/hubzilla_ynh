<?php

namespace Zotlabs\Text;


class Tagadelic {

	static public function calc($arr) {

		$tags = array();
		$min = 1e9;
		$max = -1e9;

		$x = 0;
		if(! $arr)
			return array();

		foreach($arr as $rr) {
			$tags[$x][0] = $rr['term'];
			$tags[$x][1] = log($rr['total']);
			$tags[$x][2] = 0;
			$min = min($min,$tags[$x][1]);
			$max = max($max,$tags[$x][1]);
			$x ++;
		}

		usort($tags,'self::tags_sort');

		$range = max(.01, $max - $min) * 1.0001;

		for($x = 0; $x < count($tags); $x ++) {
			$tags[$x][2] = 1 + floor(9 * ($tags[$x][1] - $min) / $range);
		}

		return $tags;
	}

	static public function tags_sort($a,$b) {
		if(strtolower($a[0]) == strtolower($b[0]))
			return 0;
		return((strtolower($a[0]) < strtolower($b[0])) ? -1 : 1);
	}

}