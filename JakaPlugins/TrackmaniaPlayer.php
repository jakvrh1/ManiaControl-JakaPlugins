<?php
/**
 * Created by PhpStorm.
 * User: jaka
 * Date: 7/18/2017
 * Time: 9:28 PM
 */

namespace JakaPlugins;


class TrackmaniaPlayer {
	public $login;
	public $nickname;
	public $bestTime;
	public $roundPoints;
	public $mapPoints;
	public $matchPoints;
	public $currentBestTime;
	public $isSpectator;
	public $teamId;

	public function __construct($login, $nickname, $bestTime, $roundPoints, $mapPoints, $matchPoints, $teamId, $isSpectator = false) {
		$this->login = $login;
		$this->nickname = $nickname;
		$this->bestTime = $bestTime;
		$this->roundPoints = $roundPoints;
		$this->mapPoints = $mapPoints;
		$this->matchPoints = $matchPoints;
		$this->currentBestTime = -1;
		$this->isSpectator = $isSpectator;
		$this->teamId = $teamId;
	}

	static public function mapPointsSort($a, $b) {
		return $a->mapPoints < $b->mapPoints;
	}

}