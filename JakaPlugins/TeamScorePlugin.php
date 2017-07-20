<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace JakaPlugins;

use ManiaControl\Callbacks\CallbackListener;
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
class TeamScorePlugin implements Plugin, CallbackListener {
	/*
	 * Constants
	 */
	// TODO: Maintain plugin metadata constants
	const ID      = 120;
	const VERSION = 1.0;
	const NAME                                  = 'Team Score Plugin';
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

	/** @var \JakaPlugins\TrackmaniaScores $matchScore */
	private $matchScore = null;
	private $playerBestTimes = array();

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

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX_TEAMSCORES, 139);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY_TEAMSCORES, -9);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES, 42);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES, 31);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT_TEAMSCORES, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT_TEAMSCORES, 18);



		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES, 0);
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
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONEVENTSTARTLINE, $this, 'handleOnEventStartLine');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleEndMap');


		$this->updateManialink = true;



		return true;
	}

	public function isThisTeamCupScript() {
		$script = $this->maniaControl->getClient()->getScriptName();
		$this->script = $script["CurrentValue"];

		if($this->script == $this->gamemode) {
			return true;
		}
		else {
			return false;
		}
	}

	public function resetScores() {
		$this->matchScore = new TrackmaniaScores();
		$this->playerBestTimes = array();
	}

	public function beginMap() {
		//var_dump("beginMap");
		$this->resetScores();
	}

	public function handleEndMap() {
		$this->closeAllWidgets();
	}

	public function handleOnEventStartLine() {
		$this->closeAllWidgets();
	}


	public function endRoundStart() {
		$this->displayAllWidgets();
	}

	public function displayAllWidgets() {
		if($this->isThisTeamCupScript()) {
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_INDIVIDUAL_SCORES)) {
				$this->displayIndividualScores();
			}
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_TEAMSCORES)) {
				$this->displayTeamScores();
				$this->displayTeamScoreWidget();
			}
		}
	}

	public function closeAllWidgets() {
		$this->closeWidget(self::SETTING_WIDGET_TITLE);
		$this->closeWidget(self::SETTING_WIDGET_INDIVIDUAL_SCORES);
		$this->closeWidget(self::INDIVIDUAL_SCORES);
		$this->closeWidget(self::SETTING_WIDGET_TEAMSCORE_LIVE);
	}

	public function endRoundEnd() {
		$this->matchScore->round += 1;
	}

	public function checkIfPlayersConflictsTeam(OnWayPointEventStructure $structure) {
		$login = trim($structure->getLogin());

		if(array_key_exists($login, $this->matchScore->blueTeamPlayers)) {
			if($structure->getPlayer()->teamId != $this->matchScore->blueTeamPlayers[$login]->teamId) {
				$this->matchScore->redTeamPlayers[$login] = $this->matchScore->blueTeamPlayers[$login];
				$this->matchScore->redTeamPlayers[$login]->teamId = self::RED_TEAM;
				unset($this->matchScore->blueTeamPlayers[$login]);
			}

		}
		else if(array_key_exists($login, $this->matchScore->redTeamPlayers)) {
			if($structure->getPlayer()->teamId != $this->matchScore->redTeamPlayers[$login]->teamId) {
				$this->matchScore->blueTeamPlayers[$login] = $this->matchScore->redTeamPlayers[$login];
				$this->matchScore->blueTeamPlayers[$login]->teamId = self::BLUE_TEAM;
				unset($this->matchScore->redTeamPlayers[$login]);
			}
		}
	}

	public function handleFinishCallback(OnWayPointEventStructure $structure) {
		//var_dump("Player {$structure->getLogin()} is in team {$structure->getPlayer()->teamId}");
		$login = trim($structure->getLogin());

		if(array_key_exists($login, $this->playerBestTimes)) {
			if($this->playerBestTimes[$login] >= $structure->getRaceTime() && $structure->getRaceTime() != 0) {
				$this->playerBestTimes[$login] =  $structure->getRaceTime();
			}
		}
		else {
			if($structure->getRaceTime() != 0) {
				$this->playerBestTimes[$login] =  $structure->getRaceTime();
			}
		}

		$this->checkIfPlayersConflictsTeam($structure);
	}


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
		foreach($this->playerBestTimes as $key => $curBest) {
			if(array_key_exists($key, $this->matchScore->blueTeamPlayers )) {
				//var_dump("Updated BLUE user {$key}!");
				$this->matchScore->blueTeamPlayers[$key]->currentBestTime = $curBest;
			}
			else if(array_key_exists($key,$this->matchScore->redTeamPlayers )) {
				//var_dump("Updated RED user {$key}!");
				$this->matchScore->redTeamPlayers[$key]->currentBestTime = $curBest;
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
		usort($bluePlayers, array('\JakaPlugins\TrackmaniaPlayer', 'mapPointsSort'));
		$index = 0;

		/** @var \JakaPlugins\TrackmaniaPlayer $player */
		foreach($bluePlayers as $player) {
			if($index == 5) {
				break;
			}
			if($player->isSpectator) {
				continue;
			}
			if($player->currentBestTime == -1) {
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
			$rankLabel->setTextPrefix('$o$FF6');
			$rankLabel->setText($player->mapPoints);
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
		usort($redPlayers, array('\JakaPlugins\TrackmaniaPlayer', 'mapPointsSort'));
		$index = 0;

		/** @var \JakaPlugins\TrackmaniaPlayer $player */
		foreach($redPlayers as $player) {
			if($index == 5) {
				break;
			}
			if($player->isSpectator) {
				continue;
			}
			if($player->currentBestTime == -1) {
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
			$rankLabel->setTextPrefix('$o$FF6');
			$rankLabel->setText($player->mapPoints);
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
		$round = $this->matchScore->round;
		//var_dump("IT IS ROUND: {$round}");

		$this->matchScore->mapPointsBlueTeam[$round] = $scores->getTeamScores()[self::BLUE_TEAM]->getMapPoints();
		$this->matchScore->mapPointsRedTeam[$round] = $scores->getTeamScores()[self::RED_TEAM]->getMapPoints();

		if(!array_key_exists($round, $this->matchScore->blueTeamPlayerPointsSum)) {
			$this->matchScore->blueTeamPlayerPointsSum[$round] = 0;
			$this->matchScore->redTeamPlayerPointsSum[$round] = 0;

			foreach($scores->getPlayerScores() as $playerScore) {
				if($playerScore->getPlayer()->teamId == self::BLUE_TEAM) {
					$this->matchScore->blueTeamPlayerPointsSum[$round] += $playerScore->getRoundPoints();
				}
				else if($playerScore->getPlayer()->teamId == self::RED_TEAM) {
					$this->matchScore->redTeamPlayerPointsSum[$round] += $playerScore->getRoundPoints();
				}
			}
		}


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

			else if($playerScore->getPlayer()->teamId == self::RED_TEAM) {
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
		$this->displayAllWidgets();
	}

	public function displayIndividualScoreWidget() {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_INDIVIDUAL_SCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES);

		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
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

	public function displayTeamScoreWidget() {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSX_TEAMSCORES);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_POSY_TEAMSCORES);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES);

		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
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

		$index = 1;
		for($i = $this->matchScore->round; $i >= 1; $i -= 1) {
			if($index == 7) {
				break;
			}
			if(!array_key_exists($i, $this->matchScore->blueTeamPlayerPointsSum) &&
			   !array_key_exists($i, $this->matchScore->redTeamPlayerPointsSum) &&
			   !array_key_exists($i, $this->matchScore->mapPointsBlueTeam) &&
			   !array_key_exists($i, $this->matchScore->mapPointsRedTeam)){
				continue;
			}
			$y = -1. - $index * $lineHeight;

			$teamScoreFrame = new Frame();
			$frame->addChild($teamScoreFrame);
			$teamScoreFrame->setPosition(0, $y + 13);

			//Rank
			$rankLabel = new Label();
			$teamScoreFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.44);
			$rankLabel->setSize($width * 0.06, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText("\$CCCr\$FF6{$i}");
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$teamScoreFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.25);
			$nameLabel->setSize($width * 0.6, $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText("\$CCCPoints: \$33F{$this->matchScore->blueTeamPlayerPointsSum[$i]}\$CCC<>\$F30{$this->matchScore->redTeamPlayerPointsSum[$i]}");
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$teamScoreFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.45);
			$timeLabel->setSize($width * 0.25, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText("$33F{$this->matchScore->mapPointsBlueTeam[$i]}\$CCC<>\$F30{$this->matchScore->mapPointsRedTeam[$i]}");
			$timeLabel->setTextEmboss(true);

			$index += 1;
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, false);
	}

	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//$this->maniaControl = null;
		$this->closeAllWidgets();
		$this->resetScores();
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
		return 'Plugin offering Team scores and player individual scores';
	}
}