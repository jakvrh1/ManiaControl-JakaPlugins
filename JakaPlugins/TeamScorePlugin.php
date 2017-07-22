<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace JakaPlugins;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
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
 * @author  Jaka Vrhovec
 * @version 1.1
 */
class TeamScorePlugin implements Plugin, CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	// TODO: Maintain plugin metadata constants
	const ID      = 120;
	const VERSION = 1.1;
	const NAME                          = 'Team Score Plugin';
	const AUTHOR                        = 'Jaka Vrhovec';

	/**
	 * Private Properties
	 */

	// Team Score properties
	const SETTING_TEAMSCORE_TITLE = 'Team Score';
	const SETTING_TEAMSCORE = 'Team Score display';
	const SETTING_TEAMSCORE_MAXPLAYERS = "Team Score max players";
	const SETTING_TEAMSCORE_MAXTEAMSCORE = "Team Score max round";
	const SETTING_TEAMSCORE_POSX = 'Team Score Position: X';
	const SETTING_TEAMSCORE_POSY = 'Team Score Position: Y';
	const SETTING_TEAMSCORE_WIDTH = 'Team Score Width';
	const SETTING_TEAMSCORE_HEIGHT = 'Team Score Height';
	//const SETTING_TEAMSCORE_LINE_COUNT = 'Team Score Displayed Lines Count';
	//const SETTING_TEAMSCORE_LINE_HEIGHT = 'Team Score Line Height';
	const LINE_HEIGHT = 4;

	const ACTION_SPEC = 'Spec.Action';

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	// Gamemodes supported by the plugin
	const GAMEMODE = "Team.Script.txt";

	/** @var \JakaPlugins\TrackmaniaScores $matchScore */
	private $matchScore = null;
	private $playerBestTimes = array();

	// Teams
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

		// Team Score settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_MAXPLAYERS, 10);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_MAXTEAMSCORE, 5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_POSX, -139.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_POSY, 75);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_WIDTH, 40.);
		//$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TEAMSCORE_LINE_HEIGHT, 4.);


		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'updateScores');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDSTART, $this, 'endRoundStart');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'beginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONEVENTSTARTLINE, $this, 'handleOnEventStartLine');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_STARTROUNDSTART, $this, 'handleStartRoundStart');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_PODIUMEND, $this, 'handlePodiumEnd');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_PODIUMSTART, $this, 'handlePodiumStart');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONGIVEUP, $this, 'handleOnGiveUp');
		//$this->maniaControl->getCommandManager()->registerCommandListener('skipround', $this, 'chat_skipRound', false, 'Skips round without updating score and round.');

		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle2Seconds', 2000);
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleSpec');

		$this->updateManialink = true;

		return true;
	}

	/*public function chat_skipRound(array $chat, Player $player) {
		$admins = $this->maniaControl->getAuthenticationManager()->getAdmins(AuthenticationManager::AUTH_LEVEL_ADMIN);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$ff0[$<' . $player->nickname . '$>] $ff0$iHello $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$ff0[$<' . $player->nickname . '$>] $ff0$iHello All!';
		}
		$this->sendChat($msg, $player);
	}*/

	public function handle2Seconds() {
		$this->ifSpectatorShowAllScoreWidget();
	}

	public function handleOnGiveUp(BasePlayerTimeStructure $structure) {
		$this->displayAllScore($structure->getLogin());
	}

	public function isThisTeamCupScript() {
		$script = $this->maniaControl->getClient()->getScriptName();
		$currentGameMode = $script["CurrentValue"];

		if($currentGameMode == $this::GAMEMODE) {
			return true;
		}
		else {
			return false;
		}
	}

	public function handleSpec(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if(count($actionArray) < 2){
			return;
		}
		$action      = $actionArray[0] . '.' . $actionArray[1];

		if (count($actionArray) > 2) {

			switch ($action) {
				case self::ACTION_SPEC:
					$adminLogin = $callback[1][1];
					$targetLogin = $actionArray[2];
					$player = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
					if ($player->isSpectator) {
						$this->maniaControl->getClient()->forceSpectatorTarget($adminLogin, $targetLogin, -1);
					}
			}
		}
	}

	public function handlePodiumEnd() {
		$this->closeWidget(self::SETTING_TEAMSCORE_TITLE);
	}

	public function handlePodiumStart() {
		$this->displayAllScore(false);
	}

	public function displayAllScore($login) {
		if($this->isThisTeamCupScript()) {
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE)) {
				$this->displayTeamScoresAndIndividualScores($login);
			}
		}
	}

	public function resetScores() {
		$this->matchScore = new TrackmaniaScores();
		$this->playerBestTimes = array();
	}

	public function handleStartRoundStart() {
		$this->matchScore->round += 1;
	}

	public function beginMap() {
		$this->resetScores();
	}

	public function ifSpectatorShowAllScoreWidget() {
		foreach ($this->maniaControl->getPlayerManager()->getPlayers() as $player) {
			if($player->isSpectator) {
				$this->displayAllScore($player->login);
			}
		}
	}

	public function handleOnEventStartLine() {
		$this->closeWidget(self::SETTING_TEAMSCORE_TITLE);
	}


	public function endRoundStart() {
		$this->displayAllScore(false);
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
		$this->displayAllScore($structure->getLogin());
	}

	public function playersThatArePlayingLogins() {
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

		return array_diff($allPlayersLogins, $specLogins);
	}

	public function playersThatArePlaying() {
		$playersThatArePlayingLogins = $this->playersThatArePlayingLogins();
		$playersThatArePlaying = array();
		$allPlayers = $this->maniaControl->getPlayerManager()->getPlayers();

		foreach ($allPlayers as $player) {
			if(in_array($player->login, $playersThatArePlayingLogins)) {
				$playersThatArePlaying[] = $player;
			}
		}

		return $playersThatArePlaying;
	}


	public function removeSpectatorsAndNotConnectedPlayers() {
		$spectators = $this->maniaControl->getPlayerManager()->getSpectators();
		$playersThatArePlaying = $this->playersThatArePlayingLogins();

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

		foreach($spectators as $spectator) {
			$login = trim($spectator->login);

			if(array_key_exists($login, $this->matchScore->blueTeamPlayers)) {
				$this->matchScore->blueTeamPlayers[$login]->isSpectator = true;
			}
			if(array_key_exists($login, $this->matchScore->redTeamPlayers)) {
				$this->matchScore->redTeamPlayers[$login]->isSpectator = true;
			}
		}
	}

	public function updateCurrentBestTimes() {
		foreach($this->playerBestTimes as $key => $curBest) {
			if(array_key_exists($key, $this->matchScore->blueTeamPlayers )) {
				$this->matchScore->blueTeamPlayers[$key]->currentBestTime = $curBest;
			}
			else if(array_key_exists($key,$this->matchScore->redTeamPlayers )) {
				$this->matchScore->redTeamPlayers[$key]->currentBestTime = $curBest;
			}
		}
	}

	public function updateScores(OnScoresStructure $scores) {
		$round = $this->matchScore->round;

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
		$this->displayAllScore(false);
	}

	public function displayTeamScoresAndIndividualScores($login) {
		//$lines       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_LINE_COUNT);
		$lineHeight   = self::LINE_HEIGHT;
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_WIDTH);
		$maxPlayers   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_MAXPLAYERS);
		$maxTeamScore = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TEAMSCORE_MAXTEAMSCORE);

		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle          = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle       = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::SETTING_TEAMSCORE_TITLE);

		$currentPlaying = $this->matchScore->blueTeamPlayers + $this->matchScore->redTeamPlayers;

		if(count($currentPlaying) == 0) {
			$neededLines = count($this->playersThatArePlaying());
			if($maxPlayers > $neededLines) {
				$maxPlayers = $neededLines;
			}
		}else {
			$neededLines = count($currentPlaying);
			if($maxPlayers > count($neededLines)) {
				$maxPlayers = $neededLines;
			}
		}

		if($maxTeamScore > $this->matchScore->round) {
			$maxTeamScore = $this->matchScore->round;
		}

		$height = 8. + ($maxPlayers + $maxTeamScore + 1) * $lineHeight;
		/*$maxPlayers += 3;
		$maxTeamScore += 3;*/


		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		//$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		/*$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);*/
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
		$backgroundQuad->setSize($width + 1.05, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);


		/*$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, 20);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_TEAMSCORE_TITLE);
		$titleLabel->setTranslate(true);*/

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, $lineHeight * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_TEAMSCORE_TITLE);
		$titleLabel->setTranslate(true);

		$this->removeSpectatorsAndNotConnectedPlayers();
		$this->updateCurrentBestTimes();

		$playersFromBothTeams = $this->matchScore->blueTeamPlayers + $this->matchScore->redTeamPlayers;
		usort($playersFromBothTeams, array('\JakaPlugins\TrackmaniaPlayer', 'mapPointsSort'));
		$index = 2;
		$displayedPlayers = 0;
		$displayedTeamScore = 0;


		if(count($playersFromBothTeams) == 0) {
			$playersFromBothTeams = $this->playersThatArePlaying();
			foreach ($playersFromBothTeams as $player) {
				if($displayedPlayers >= $maxPlayers) {
					break;
				}
				$y = -1. - $index * $lineHeight;

				$teamScoreFrame = new Frame();
				$frame->addChild($teamScoreFrame);
				$teamScoreFrame->setPosition(0, $y);
				//$teamScoreFrame->setY($y+ $lineHeight / 2);

				$nameLabel = new Label();
				$teamScoreFrame->addChild($nameLabel);
				$nameLabel->setHorizontalAlign($nameLabel::LEFT);
				$nameLabel->setX($width * -0.3 );
				$nameLabel->setSize($width * 0.6 , $lineHeight);
				$nameLabel->setTextSize(1);
				$nameLabel->setText($player->nickname);
				$nameLabel->setTextEmboss(true);

				//Quad with Spec action
				$quad = new Quad();
				$teamScoreFrame->addChild($quad);
				$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
				$quad->setSize($width * 0.96, $lineHeight);
				$quad->setAction(self::ACTION_SPEC . '.' . $player->login);
				$displayedPlayers += 1;
			}
		}
		else {
			foreach($playersFromBothTeams as $player) {
				if($player->isSpectator) {
					continue;
				}
				if($displayedPlayers >= $maxPlayers) {
					break;
				}
				$y = -1. - $index * $lineHeight;

				$teamScoreFrame = new Frame();
				$frame->addChild($teamScoreFrame);
				$teamScoreFrame->setPosition(0, $y);
				//$teamScoreFrame->setY($y+ $lineHeight / 2);

				$rankLabel = new Label();
				$teamScoreFrame->addChild($rankLabel);
				$rankLabel->setHorizontalAlign($rankLabel::LEFT);
				$rankLabel->setX($width * -0.47);
				$rankLabel->setSize($width * 0.1, $lineHeight);
				$rankLabel->setTextSize(1);
				$rankLabel->setTextPrefix('$o');
				$rankLabel->setText($player->mapPoints);

				$rankLabel->setTextEmboss(true);

				$nameLabel = new Label();
				$teamScoreFrame->addChild($nameLabel);
				$nameLabel->setHorizontalAlign($nameLabel::LEFT);
				$nameLabel->setX($width * -0.3 );
				$nameLabel->setSize($width * 0.6 , $lineHeight);
				$nameLabel->setTextSize(1);
				$nameLabel->setText($player->nickname);
				$nameLabel->setTextEmboss(true);

				$timeLabel = new Label();
				$teamScoreFrame->addChild($timeLabel);
				$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
				$timeLabel->setX($width * 0.47);
				$timeLabel->setSize($width * 0.27, $lineHeight);
				$timeLabel->setTextSize(1);
				if($player->currentBestTime != -1) {
					$timeLabel->setText(Formatter::formatTime($player->currentBestTime));
				}

				//Quad with Spec action
				$quad = new Quad();
				$teamScoreFrame->addChild($quad);
				$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
				$quad->setSize($width * 0.96, $lineHeight);
				$quad->setAction(self::ACTION_SPEC . '.' . $player->login);

				$displayedPlayers += 1;
				$index += 1;
			}
		}

		$y = -1. - $index * $lineHeight;
		$blankFrame = new Frame();
		$frame->addChild($blankFrame);
		$blankFrame->setPosition(0, $y);

		$index += 1;

		for($i = $this->matchScore->round; $i >= 1; $i -= 1) {
			if(!array_key_exists($i, $this->matchScore->blueTeamPlayerPointsSum) &&
			   !array_key_exists($i, $this->matchScore->redTeamPlayerPointsSum) &&
			   !array_key_exists($i, $this->matchScore->mapPointsBlueTeam) &&
			   !array_key_exists($i, $this->matchScore->mapPointsRedTeam)){
				continue;
			}
			if($displayedTeamScore >= $maxTeamScore) {
				break;
			}
			$y = -1. - $index * $lineHeight;

			$teamScoreFrame = new Frame();
			$frame->addChild($teamScoreFrame);
			$teamScoreFrame->setPosition(0, $y);

			//Rank
			$rankLabel = new Label();
			$teamScoreFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.1, $lineHeight);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o');
			$rankLabel->setText("r{$i}");
			$rankLabel->setTextEmboss(true);

			//Name
			$nameLabel = new Label();
			$teamScoreFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.3 );
			$nameLabel->setSize($width * 0.6 , $lineHeight);
			$nameLabel->setTextSize(1);
			$nameLabel->setText("Points: \$33F{$this->matchScore->blueTeamPlayerPointsSum[$i]}\$FFF<>\$F30{$this->matchScore->redTeamPlayerPointsSum[$i]}");
			$nameLabel->setTextEmboss(true);

			//Time
			$timeLabel = new Label();
			$teamScoreFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.47);
			$timeLabel->setSize($width * 0.27, $lineHeight);
			$timeLabel->setTextSize(1);
			$timeLabel->setText("\$33F{$this->matchScore->mapPointsBlueTeam[$i]}\$fff - \$F30{$this->matchScore->mapPointsRedTeam[$i]}");
			$timeLabel->setTextEmboss(true);

			/*$quad = new Quad();
			$teamScoreFrame->addChild($quad);
			$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
			$quad->setSize($width * 0.5, $lineHeight);
			$quad->setHorizontalAlign($quad::RIGHT);*/

			$displayedTeamScore += 1;
			$index += 1;
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//$this->maniaControl = null;
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