<?php
/**
 * Created by PhpStorm.
 * User: jaka
 * Date: 7/18/2017
 * Time: 9:28 PM
 */

namespace Jaka;


class TrackmaniaPlayer {
	public $login;
	public $nickname;
	public $bestTime;
	public $roundPoints;
	public $mapPoints;
	public $matchPoints;

	public function __construct($login, $nickname, $bestTime, $roundPoints, $mapPoints, $matchPoints) {
		$this->login = $login;
		$this->nickname = $nickname;
		$this->bestTime = $bestTime;
		$this->roundPoints = $roundPoints;
		$this->mapPoints = $mapPoints;
		$this->matchPoints = $matchPoints;
	}

}