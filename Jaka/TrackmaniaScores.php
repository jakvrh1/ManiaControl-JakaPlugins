<?php
/**
 * Created by PhpStorm.
 * User: jaka
 * Date: 7/18/2017
 * Time: 5:26 PM
 */

namespace Jaka;


class TrackmaniaScores {

	public $matchPointsBlueTeam;
	public $matchpointsRedTeam;
	public $round;

	function __construct() {
		$this->round = 0;
		$this->matchPointsBlueTeam = 0;
		$this->matchpointsRedTeam = 0;
	}
}