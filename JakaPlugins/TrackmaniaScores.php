<?php
/**
 * Created by PhpStorm.
 * User: jaka
 * Date: 7/18/2017
 * Time: 5:26 PM
 */

namespace JakaPlugins;


class TrackmaniaScores {

	public $mapPointsBlueTeam;
	public $mapPointsRedTeam;
	public $round;
	public $blueTeamPlayers;
	public $redTeamPlayers;

	public $blueTeamPlayerPointsSum;
	public $redTeamPlayerPointsSum;

	function __construct() {
		$this->round = 0;

		$this->mapPointsBlueTeam = array();
		$this->mapPointsRedTeam = array();

		$this->blueTeamPlayerPointsSum = array();
		$this->redTeamPlayerPointsSum = array();

		$this->blueTeamPlayers = array();
		$this->redTeamPlayers = array();
	}
}