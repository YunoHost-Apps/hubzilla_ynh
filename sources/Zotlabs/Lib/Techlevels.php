<?php

namespace Zotlabs\Lib;


class Techlevels {

	static public function levels() {
		$techlevels = [
			'0' => t('Beginner/Basic'),
			'1' => t('Novice - not skilled but willing to learn'),
			'2' => t('Intermediate - somewhat comfortable'),
			'3' => t('Advanced - very comfortable'),
			'4' => t('Expert - I can write computer code'),			
			'5' => t('Wizard - I probably know more than you do')
		];
		return $techlevels;
	}

}

