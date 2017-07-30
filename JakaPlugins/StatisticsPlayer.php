<?php
/**
 * Created by PhpStorm.
 * User: jaka
 * Date: 7/26/2017
 * Time: 10:30 AM
 */

namespace JakaPlugins;


class StatisticsPlayer {
	public $allTimes;
	public $bestTime;
	public $nickname;
	public $login;
	public $rounds;
	public $giveUps;

	public function __construct($nickname, $login) {
		$this->allTimes = array();
		$this->bestTime = -1;
		$this->rounds = 0;
		$this->giveUps = 0;

		$this->nickname = $nickname;
		$this->login = $login;
	}

	static public function bestTimeSort($a, $b) {
		return $a->rounds < $b->rounds;
	}

}