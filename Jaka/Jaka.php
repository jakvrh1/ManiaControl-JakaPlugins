<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace Jaka;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerRequestActionChange;
use ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\Callbacks;
use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Bgs1InRace;
use ManiaControl\Utils\Formatter;

// TODO: Maintain plugin class PHPDoc
/**
 * Plugin Description
 *
 * @author  Template Author
 * @version 1.0
 */
class Jaka implements Plugin, CallbackListener {
	/*
	 * Constants
	 */
	// TODO: Maintain plugin metadata constants
	const ID      = 118;
	const VERSION = 1.0;
	const NAME                                  = 'Jaka';
	const AUTHOR                                = 'Jaka Vrhovec';
	/**
	 * Private Properties
	 */
	const SETTING_WIDGET_TITLE        = 'Team Score';

	const SETTING_WIDGET_TEAMSCORES   = 'Team scores';
	const SETTING_WIDGET_POSX_TEAMSCORES = 'TeamScore Position: X';
	const SETTING_WIDGET_POSY_TEAMSCORES = 'TeamScore Position: Y';
	const SETTING_WIDGET_WIDTH_TEAMSCORES = 'TeamScore Width';
	const SETTING_WIDGET_LINE_COUNT_TEAMSCORES = 'TeamScore Displayed Lines Count';
	const SETTING_WIDGET_LINE_HEIGHT_TEAMSCORES = 'TeamScore Line Height';
	const SETTING_WIDGET_HEIGHT_TEAMSCORES = 'TeamScore Height';

	const SETTING_WIDGET_INDIVIDUAL_SCORES = 'Individual scores';
	const INDIVIDUAL_SCORES = 'individual_scores';

	const SETTING_WIDGET_POSX_INDIVIDUAL_SCORES = 'InScores Position: X';
	const SETTING_WIDGET_POSY_INDIVIDUAL_SCORES = 'InScores Position: Y';
	const SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES = 'InScores Width';
	const SETTING_WIDGET_LINE_COUNT_INDIVIDUAL_SCORES= 'InScores Displayed Lines Count';
	const SETTING_WIDGET_LINE_HEIGHT_INDIVIDUAL_SCORES = 'InScores Line Height';
	const SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES = 'InScores Height';

	const SETTING_WIDGET_TEAMSCORE_LIVE = 'TeamScoreLive';

	const ACTION_SPEC = 'Spec.Action';


	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	// Gamemodes supported by the plugin
	private $gamemode = "Team.Script.txt";
	private $script = array();
	private $active = false;

	// TeamScore data
	private $round = 0;

	/** @var \Jaka\TrackmaniaScores $matchScore */
	private $matchScore = null;
	private $playerBestTimes = array();

	private $redPlayersBestTimes = array();
	private $bluePlayersBestTimes = array();

	const BLUE_TEAM = 0;
	const RED_TEAM = 1;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		// TODO: Implement prepare() method.

	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		// TODO: Implement load() method.
		$this->matchScore = new TrackmaniaScores();

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_TITLE, 'Team Scores');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_TEAMSCORES, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_INDIVIDUAL_SCORES, true);


		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX_TEAMSCORES, 70);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY_TEAMSCORES, 57);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES, 42);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES, 31);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT_TEAMSCORES, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT_TEAMSCORES, 18);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES, -25);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY_INDIVIDUAL_SCORES, 57);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES, 150);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES, 31);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT_INDIVIDUAL_SCORES, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT_INDIVIDUAL_SCORES, 18);

		// Callbacks
		/*$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'scoresCallback');*/
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'updateScores');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDSTART, $this, 'endRoundStart');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDSTART, $this, 'displayIndividualScores');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDEND, $this, 'endRoundEnd');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'beginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONGIVEUP, $this, 'handleGiveUp');


		$this->updateManialink = true;

		$script = $this->maniaControl->getClient()->getScriptName();
		$this->script = $script["CurrentValue"];
		if($this->script == $this->gamemode) {
			$this->active = true;
		}
		else {
			$this->active = false;
		}

		$this->displayWidget();
		//$this->displayIndividualScores();


		return true;
	}

	public function resetScores() {
		$this->matchScore = new TrackmaniaScores();
		$this->matchScore->round = 1;
		$this->playerBestTimes = array();

		$this->redPlayersBestTimes = array();
		$this->bluePlayersBestTimes = array();
	}

	public function beginMap() {
		//var_dump("beginMap");
		$this->resetScores();
	}



	public function endRoundStart() {
		//var_dump("End Round Start");
		$this->matchScore->round += 1;
		$this->displayIndividualScores();
		//$this->displayWidget();

		/*var_dump("OUTSIDE");
		foreach($this->maniaControl->getPlayerManager()- as $spec) {
			var_dump("INSIDE");
			var_dump("Specatting: ".$spec->login);
		}*/


	}

	public function endRoundEnd() {
		//var_dump("End Round End");
		/*$this->closeWidget(self::SETTING_WIDGET_TITLE);
		$this->closeWidget(self::SETTING_WIDGET_INDIVIDUAL_SCORES);
		$this->closeWidget(self::INDIVIDUAL_SCORES);*/
	}

	public function handleGiveUp() {


	}

	public function handleFinishCallback(OnWayPointEventStructure $structure) {

		var_dump("Player {$structure->getLogin()} is in team {$structure->getPlayer()->teamId}");
		$login = trim($structure->getLogin());

		if($structure->getPlayer()->teamId == self::BLUE_TEAM) {
			if(array_key_exists($login, $this->bluePlayersBestTimes)) {
				if($this->bluePlayersBestTimes[$login] > $structure->getRaceTime() && $structure->getRaceTime() != 0) {
					$this->bluePlayersBestTimes[$login] = $structure->getRaceTime();
				}
			}
			else {
				if($structure->getRaceTime() != 0) {
					$this->bluePlayersBestTimes[$login] = $structure->getRaceTime();
				}
			}
		}
		else if($structure->getPlayer()->teamId == self::RED_TEAM) {
			if(array_key_exists($login, $this->redPlayersBestTimes)) {
				if($this->redPlayersBestTimes[$login] > $structure->getRaceTime() && $structure->getRaceTime() != 0) {
					$this->redPlayersBestTimes[$login] = $structure->getRaceTime();
				}
			}
			else {
				if($structure->getRaceTime() != 0) {
					$this->redPlayersBestTimes[$login] = $structure->getRaceTime();
				}
			}
		}


			//changed1
		/*if(array_key_exists(trim($structure->getLogin()), $this->playerBestTimes)) {
			if($this->playerBestTimes[trim($structure->getLogin())] >= $structure->getRaceTime() && $structure->getRaceTime() != 0) {
				$this->playerBestTimes[trim($structure->getLogin())] =  $structure->getRaceTime();
			}
		}
		else {
			if($structure->getRaceTime() != 0) {
				$this->playerBestTimes[trim($structure->getLogin())] =  $structure->getRaceTime();
			}
		}*/
	}


	/*public function setCurrentBestTimes() {
		foreach($this->playerBestTimes as $login => $crBestTime) {
			$this->matchScore->blueTeamPlayers[$login]->currentBestTime = $crBestTime;
		}
	}
*/


	public function removeSpectatorsAndNotConnectedPlayers() {
		$spectators = $this->maniaControl->getPlayerManager()->getSpectators();
		$specLogins = array();
		foreach($spectators as $spec) {
			$specLogins[] = trim($spec->login);
		}


		$allPlayers = $this->maniaControl->getPlayerManager()->getPlayers();
		$allPlayersLogins = array();
		foreach($allPlayers as $pl) {
			$allPlayersLogins[] = trim($pl->login);
		}

		$playersThatArePlaying = array_diff($allPlayersLogins, $specLogins);

		foreach($this->matchScore->blueTeamPlayers as $player) {
			$player->isSpectator = true;
		}
		foreach($this->matchScore->redTeamPlayers as $player) {
			$player->isSpectator = true;
		}

		foreach($playersThatArePlaying as $player) {
			if (array_key_exists($player, $this->matchScore->blueTeamPlayers)) {
				$this->matchScore->blueTeamPlayers[$player]->isSpectator = false;
			}
			if (array_key_exists($player, $this->matchScore->redTeamPlayers)) {
				$this->matchScore->redTeamPlayers[$player]->isSpectator = false;
			}
		}

		foreach($specLogins as $spec) {
			if(array_key_exists($spec, $this->matchScore->blueTeamPlayers)) {
				$this->matchScore->blueTeamPlayers[$spec]->isSpectator = true;
			}
			if(array_key_exists($spec, $this->matchScore->redTeamPlayers)) {
				$this->matchScore->redTeamPlayers[$spec]->isSpectator = true;
			}
		}
	}

	public function updateCurrentBestTimes() {
		/*foreach($this->playerBestTimes as $key => $playerBestTime) {
			$this->matchScore->blueTeamPlayers[$key]->currentBestTime = $playerBestTime;
		}
		*/



		foreach($this->bluePlayersBestTimes as $key => $bluePlayersBestTime) {
			if(array_key_exists($key,$this->matchScore->blueTeamPlayers )) {
				//var_dump("Updated BLUE user {$key}!");
				$this->matchScore->blueTeamPlayers[$key]->currentBestTime = $bluePlayersBestTime;
			}
		}

		foreach($this->redPlayersBestTimes as $key => $redPlayersBestTime) {
			if(array_key_exists($key,$this->matchScore->redTeamPlayers )) {
				//var_dump("Updated RED user {$key}!");
				$this->matchScore->redTeamPlayers[$key]->currentBestTime = $redPlayersBestTime;
			}
		}
	}

	public function displayIndividualScores() {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_INDIVIDUAL_SCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES);
		$lineHeight   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_HEIGHT_INDIVIDUAL_SCORES);
		$lines        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_COUNT_INDIVIDUAL_SCORES);

		$maniaLink = new ManiaLink(self::INDIVIDUAL_SCORES);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($posX, $posY);

		$this->removeSpectatorsAndNotConnectedPlayers();
		$this->updateCurrentBestTimes();

		$bluePlayers = $this->matchScore->blueTeamPlayers;
		usort($bluePlayers, array('\Jaka\TrackmaniaPlayer', 'mapPointsSort'));
		$index = 0;

		/** @var \Jaka\TrackmaniaPlayer $player */
		foreach($bluePlayers as $player) {
			/*var_dump($player->login);
			if($player->isSpectator) {
				var_dump("IS SPEC!");
			}*/
			if($player->isSpectator) {
				//var_dump("SPECTATOR_RETURN {$player->login}");
				continue;
			}
			if($player->currentBestTime == -1) {
				var_dump("PLAYER {$player->login} has bad time {$player->currentBestTime}");
				continue;
			}

			$y = -1. - $index * $lineHeight;

			$teamScoreFrame = new Frame();
			$frame->addChild($teamScoreFrame);
			$teamScoreFrame->setPosition(0, $y+13);

			//Rank
			$rankLabel = new Label();
			$teamScoreFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(2);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($player->matchPoints);
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$teamScoreFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(2);
			$nameLabel->setText($player->nickname);
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$teamScoreFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::LEFT);
			$timeLabel->setX($width * -0.10);
			$timeLabel->setSize($width * 0.6, $lineHeight);
			$timeLabel->setTextSize(2);
			$timeLabel->setText(Formatter::formatTime($player->currentBestTime));
			$timeLabel->setTextEmboss(true);

			//Quad with Spec action
			$quad = new Quad();
			$teamScoreFrame->addChild($quad);
			$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
			$quad->setSize($width * 0.5, $lineHeight);
			$quad->setHorizontalAlign($quad::RIGHT);
			$quad->setAction(self::ACTION_SPEC . '.' . $player->login);

			$index += 1;
		}

		//do for red as well
		$redPlayers = $this->matchScore->redTeamPlayers;
		usort($redPlayers, array('\Jaka\TrackmaniaPlayer', 'mapPointsSort'));
		$index = 0;

		/** @var \Jaka\TrackmaniaPlayer $player */
		foreach($redPlayers as $player) {
			/*var_dump($player->login);
			if($player->isSpectator) {
				var_dump("IS SPEC!");
			}*/
			if($player->isSpectator) {
				//var_dump("SPECTATOR_RETURN {$player->login}");
				continue;
			}
			if($player->currentBestTime == -1) {
				var_dump("PLAYER {$player->login} has bad time {$player->currentBestTime}");
				continue;
			}

			$y = -1. - $index * $lineHeight;

			$teamScoreFrame = new Frame();
			$frame->addChild($teamScoreFrame);
			$teamScoreFrame->setPosition($width * 0.5, $y+13);

			//Rank
			$rankLabel = new Label();
			$teamScoreFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(2);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText($player->matchPoints);
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$teamScoreFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(2);
			$nameLabel->setText($player->nickname);
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$teamScoreFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::LEFT);
			$timeLabel->setX($width * -0.10);
			$timeLabel->setSize($width * 0.6, $lineHeight);
			$timeLabel->setTextSize(2);
			$timeLabel->setText(Formatter::formatTime($player->currentBestTime));
			$timeLabel->setTextEmboss(true);

			//Quad with Spec action
			$quad = new Quad();
			$teamScoreFrame->addChild($quad);
			$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
			$quad->setSize($width * 0.5, $lineHeight);
			$quad->setHorizontalAlign($quad::RIGHT);
			$quad->setAction(self::ACTION_SPEC . '.' . $player->login);

			$index += 1;
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, false);
	}

	public function updateScores(OnScoresStructure $scores) {

		$this->matchScore->matchPointsBlueTeam = $scores->getTeamScores()[0]->getMatchPoints();
		$this->matchScore->matchpointsRedTeam = $scores->getTeamScores()[1]->getMatchPoints();

		foreach($scores->getPlayerScores() as $playerScore) {

			if($playerScore->getPlayer()->teamId == self::BLUE_TEAM) {
				$this->matchScore->blueTeamPlayers[trim($playerScore->getPlayer()->login)] = new TrackmaniaPlayer(trim($playerScore->getPlayer()->login),
				                                                                                                  $playerScore->getPlayer()->nickname,
				                                                                                                  $playerScore->getBestRaceTime(),
				                                                                                                  $playerScore->getRoundPoints(),
				                                                                                                  $playerScore->getMapPoints(),
				                                                                                                  $playerScore->getMatchPoints(),
				                                                                                                  $playerScore->getPlayer()->teamId,
				                                                                                                  false);
			}

			if($playerScore->getPlayer()->teamId == self::RED_TEAM) {
				$this->matchScore->redTeamPlayers[trim($playerScore->getPlayer()->login)] = new TrackmaniaPlayer(trim($playerScore->getPlayer()->login),
				                                                                                                  $playerScore->getPlayer()->nickname,
				                                                                                                  $playerScore->getBestRaceTime(),
				                                                                                                  $playerScore->getRoundPoints(),
				                                                                                                  $playerScore->getMapPoints(),
				                                                                                                  $playerScore->getMatchPoints(),
				                                                                                                  $playerScore->getPlayer()->teamId,
				                                                                                                  false);
			}


		}
		$this->displayIndividualScores();
	}



	public function displayWidget() {
		if($this->active) {
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_TEAMSCORES)) {
				$this->displayTeamScoreWidget();
			}
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_INDIVIDUAL_SCORES)) {
				$this->displayIndividualScoreWidget();
			}
		}
	}


	public function displayIndividualScoreWidget() {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_INDIVIDUAL_SCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES);
		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();

		$maniaLink = new ManiaLink(self::SETTING_WIDGET_INDIVIDUAL_SCORES);

		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);


		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		/*$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, 17);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_WIDGET_INDIVIDUAL_SCORES);
		$titleLabel->setTranslate(true);*/


		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, false);
	}

	public function displayTeamScores() {
		/*$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_TEAMSCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_TEAMSCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES);

		$maniaLink = new ManiaLink(self::MLID_CHECKPOINTS_LIVE_WIDGETTIMES);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($posX, $posY);*/


	}

	public function displayTeamScoreWidget() {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_TEAMSCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_TEAMSCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES);
		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();

		$maniaLink = new ManiaLink(self::SETTING_WIDGET_TITLE);

		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, +12);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_WIDGET_TITLE);
		$titleLabel->setTranslate(true);

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, false);
	}

	public function displayTeamScores() {
		$lines       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_COUNT_TEAMSCORES);
		$lineHeight   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_LINE_HEIGHT_TEAMSCORES);
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_TEAMSCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_TEAMSCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES);

		$maniaLink = new ManiaLink(self::SETTING_WIDGET_TEAMSCORE_LIVE);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($posX, $posY);

		// TODO: Implement showing teamscore

	}

	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
	}


	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//$this->maniaControl = null;
		$this->closeWidget(self::SETTING_WIDGET_TITLE);
		$this->closeWidget(self::SETTING_WIDGET_INDIVIDUAL_SCORES);
		$this->closeWidget(self::INDIVIDUAL_SCORES);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offering Team Scores';
	}




}