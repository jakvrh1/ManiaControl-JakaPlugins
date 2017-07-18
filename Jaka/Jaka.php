<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace Jaka;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\Callbacks;
use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Bgs1InRace;

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

	const SETTING_WIDGET_POSX_INDIVIDUAL_SCORES = 'InScores Position: X';
	const SETTING_WIDGET_POSY_INDIVIDUAL_SCORES = 'InScores Position: Y';
	const SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES = 'InScores Width';
	const SETTING_WIDGET_LINE_COUNT_INDIVIDUAL_SCORES= 'InScores Displayed Lines Count';
	const SETTING_WIDGET_LINE_HEIGHT_INDIVIDUAL_SCORES = 'InScores Line Height';
	const SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES = 'InScores Height';

	const SETTING_WIDGET_TEAMSCORE_LIVE = 'TeamScoreLive';


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
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY_TEAMSCORES, 61);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH_TEAMSCORES, 42);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT_TEAMSCORES, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT_TEAMSCORES, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT_TEAMSCORES, 18);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSX_INDIVIDUAL_SCORES, -25);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_POSY_INDIVIDUAL_SCORES, 61);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_WIDTH_INDIVIDUAL_SCORES, 150);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_HEIGHT_INDIVIDUAL_SCORES, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_HEIGHT_INDIVIDUAL_SCORES, 4);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_WIDGET_LINE_COUNT_INDIVIDUAL_SCORES, 18);

		// Callbacks
		/*$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'scoresCallback');*/
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'updateTeamScores');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDSTART, $this, 'endRoundStart');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_ENDROUNDEND, $this, 'endRoundEnd');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'beginMap');

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


		return true;
	}

	public function beginMap() {
		var_dump("beginMap");
		$this->matchScore = new TrackmaniaScores();
		$this->matchScore->round = 0;
	}

	public function endRoundStart() {
		var_dump("End Round Start");
		$this->matchScore->round += 1;
		//$this->displayWidget();*/

	}

	public function endRoundEnd() {
		var_dump("End Round End");
		/*$this->closeWidget(self::SETTING_WIDGET_TITLE);
		$this->closeWidget(self::SETTING_WIDGET_INDIVIDUAL_SCORES);*/

	}

	public function updateTeamScores(OnScoresStructure $scores) {
		var_dump("updateTeamScores");
		$this->matchScore->matchPointsBlueTeam = $scores->getTeamScores()[0]->getMatchPoints();
		$this->matchScore->matchpointsRedTeam = $scores->getTeamScores()[1]->getMatchPoints();

		foreach($scores->getPlayerScores() as $playerScore) {
			$this->matchScore->blueTeamPlayers[$playerScore->getPlayer()->login] = new TrackmaniaPlayer($playerScore->getPlayer()->login,
			                                                                                            $playerScore->getPlayer()->nickname,
			                                                                                            $playerScore->getBestRaceTime(),
			                                                                                            $playerScore->getRoundPoints(),
			                                                                                            $playerScore->getMapPoints(),
			                                                                                            $playerScore->getMatchPoints());
		}

		//Getting proper data
		/*var_dump("Round -> ".$this->matchScore->round);
		var_dump("BlueTeam MatchScore -> ".$this->matchScore->matchPointsBlueTeam);
		var_dump("RedTeam MatchScore -> ".$this->matchScore->matchpointsRedTeam);

		foreach($this->matchScore->blueTeamPlayers as $index => $player) {
			var_dump("\nindex: ".$index);
			var_dump("login -> ".$player->login);
			var_dump("nickname -> ".$player->nickname);
			var_dump("best race time -> ".$player->bestTime);
			var_dump("round points -> ".$player->roundPoints);
			var_dump("map points -> ".$player->mapPoints);
			var_dump("match points -> ".$player->matchPoints);
			var_dump("\n");
		}
		var_dump("\n\n");*/

		/*var_dump("Sections - ".$scores->getSection());

		foreach($scores->getTeamScores() as $teamScore) {
			var_dump("Name - ".$teamScore->getName());
			var_dump("MatchPoints - ".$teamScore->getMatchPoints());
			var_dump("RoundsPoints - ".$teamScore->getRoundPoints());
			var_dump("MapPoints - ".$teamScore->getMapPoints());
		}*/

		/*foreach($scores->getPlayerScores() as $playerScore) {
			var_dump("Login -> ".$playerScore->getPlayer()->login);
			var_dump("Name -> ".$playerScore->getPlayer()->nickname);

			var_dump("RoundPoints -> ".$playerScore->getRoundPoints());
			var_dump("MapPoints -> ".$playerScore->getMapPoints());
			var_dump("MatchPoints -> ".$playerScore->getMatchPoints());
			var_dump("BestRace -> ".$playerScore->getBestRaceTime());
			//var_dump("Name -> ".$playerScore->getPlayer()->getUsageInformation());
			var_dump("\n\n");
		}*/
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

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, 17);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_WIDGET_INDIVIDUAL_SCORES);
		$titleLabel->setTranslate(true);

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, false);
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
		$titleLabel->setPosition(0, 17);
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