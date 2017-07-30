<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace JakaPlugins;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Structures\TrackMania\OnStartLineEventStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\Callbacks;
use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use ManiaControl\Utils\Formatter;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use FML\Script\Features\Paging;
use ManiaControl\Manialinks\LabelLine;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;

// TODO: Maintain plugin class PHPDoc
/**
 * Plugin Description
 *
 * @author  Jaka Vrhovec, hpD-orso
 * @version 1.0
 */
class StatisticsPlugin implements Plugin, CallbackListener, TimerListener, ManialinkPageAnswerListener {
	/*
	 * Constants
	 */
	// TODO: Maintain plugin metadata constants
	const ID      = 121;
	const VERSION = 1.0;
	const NAME    = 'Statistics Plugin';
	const AUTHOR  = 'Jaka Vrhovec, hpD-orso';
	/**
	 * Private Properties
	 */

	// Statistics
	const SETTING_STATISTICS_TITLE = 'Statistics Plugin';
	const SETTING_STATISTICS = 'Statistics are displayed';
	const SETTING_STATISTICS_POSX = 'Statistics Position: X';
	const SETTING_STATISTICS_POSY = 'Statistics Position: Y';
	const SETTING_STATISTICS_WIDTH = 'Statistics Width';
	const SETTING_STATISTICS_LINESCOUNT = 'Statistics lines';

	const SETTING_BUTTON = "Statistics.button";
	const SETTING_BUTTON_TITLE_START = "Start";
	const SETTING_BUTTON_TITLE_STOP = "Stop";
	const SETTING_BUTTON_POSX = 'Button Position: X';
	const SETTING_BUTTON_POSY = 'Button Position: Y';
	const SETTING_BUTTON_HEIGHT = 6;
	const SETTING_BUTTON_WIDTH = 10;

	const SETTING_LINE_HEIGHT = 4;

	const ACTION_STATISTICS = "Statistics.action";
	const ACTION_BUTTON = "Button.action";
	const ACTION_RESTART = "Statistics.Restart";

	// Properties
	public $playedMaps = array();
	public $playerIsSavign = array();
	public $players = array();
	public $giveUpLock = array();

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}
	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Team Score settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS_TITLE, self::SETTING_STATISTICS_TITLE);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS_POSX, -139.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS_POSY, 75);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS_WIDTH, 42.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_STATISTICS_LINESCOUNT, 15);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_BUTTON_POSX, 155.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_BUTTON_POSY, 77);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONFINISHLINE, $this, 'handleFinishCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONGIVEUP, $this, 'handleOnGiveUp');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleEndMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_ONEVENTSTARTLINE, $this, 'handleOnEventStartLine');
		
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_STATISTICS, $this, 'handleStatisticsButtonPressed');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_BUTTON, $this, 'handleButtonPressed');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_RESTART, $this, 'handleButtonRestart');
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Seconds', 1000);

		$this->updateManialink = true;

		return true;
	}

	public function handleButtonRestart(array $callback, Player $player) {
		$login = $player->login;
		if(array_key_exists($login, $this->playerIsSavign)) {
			$this->players[$login] = new StatisticsPlayer($player->nickname, $login);
		}
	}

	public function handleOnEventStartLine(OnStartLineEventStructure $structure) {
		if(array_key_exists($structure->getLogin(), $this->giveUpLock)) {
			unset($this->giveUpLock[$structure->getLogin()]);
		}
	}

	public function handleEndMap() {
		$this->playerIsSavign = array();
		$mapId = $this->maniaControl->getMapManager()->getCurrentMap()->uid;
		$this->playedMaps[$mapId] = array();

		foreach ($this->players as $player) {
			$this->playedMaps[$mapId][$player->login] = $player;
		}
	}

	public function savePlayerToCurrentMap($player) {
		$mapId = $this->maniaControl->getMapManager()->getCurrentMap()->uid;
		$this->playedMaps[$mapId][$player->login] = $player;
	}

	/**
	 * Handle the ManiaLink answer of the showRecordsList action
	 *
	 * @internal
	 * @param array                        $callback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handleButtonPressed(array $callback, Player $player) {
		$login = $player->login;

		if(array_key_exists($login, $this->playerIsSavign)) {
			unset($this->playerIsSavign[$login]);
			if(array_key_exists($login, $this->players)) {
				$this->savePlayerToCurrentMap($this->players[$login]);
			}

		} else {
			$this->playerIsSavign[$login] = true;
			$this->players[$login] = new StatisticsPlayer($player->nickname, $login);
		}
		$this->displayButtonWidget($login);
	}

	/**
	 * Handle the ManiaLink answer of the showRecordsList action
	 *
	 * @internal
	 * @param array                        $callback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function handleStatisticsButtonPressed(array $callback, Player $player) {
		$this->showList($player);
	}

	public function handle1Seconds() {
		$this->displayStatisticsWidget(false);

		foreach($this->maniaControl->getPlayerManager()->getPlayers() as $player) {
			if($player->isSpectator) {
				$this->maniaControl->getManialinkManager()->hideManialink(self::SETTING_BUTTON, $player->login);
				if(array_key_exists($player->login, $this->playerIsSavign)) {
					unset($this->playerIsSavign[$player->login]);
				}
			} else {
				$this->displayButtonWidget($player->login);
			}
		}
	}

	public function handleFinishCallback(OnWayPointEventStructure $structure) {
		$login = $structure->getLogin();
		$this->giveUpLock[$login] = true;

		if(array_key_exists($login, $this->playerIsSavign)) {
			$this->players[$login]->rounds++;
			$this->players[$login]->allTimes[] = $structure->getRaceTime();

			if($structure->getRaceTime() < $this->players[$login]->bestTime || $this->players[$login]->bestTime == -1) {
				$this->players[$login]->bestTime = $structure->getRaceTime();
			}
		}
	}

	public function handleOnGiveUp(BasePlayerTimeStructure $structure) {
		$login = $structure->getLogin();

		if(array_key_exists($login, $this->giveUpLock)) {
			return;
		}

		if(array_key_exists($login, $this->playerIsSavign)) {
			$this->players[$login]->rounds++;
			$this->players[$login]->giveUps++;
		}
	}


	public function displayStatisticsWidget($login) {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_STATISTICS)) {
			$this->statisticsWidget($login);
		}
		else {
			$this->closeWidget(self::SETTING_STATISTICS);
		}
	}

	public function displayButtonWidget($login) {
		$this->buttonWidget($login);
	}

	public function showList($playerArg) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();

		// get PlayerList
		$mapId = $this->maniaControl->getMapManager()->getCurrentMap()->uid;
		$players = null;

		if(array_key_exists($mapId, $this->playedMaps)) {
			$players = $this->playedMaps[$mapId];
		}

		if($players == null) {
			return;
		}
		usort($players, array('JakaPlugins\StatisticsPlayer', 'bestTimeSort'));

		// create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 5);

		$labelLine = new LabelLine($headFrame);
		$labelLine->addLabelEntryText('Nickname', $posX + 5);
		$labelLine->addLabelEntryText('Rounds', $posX + 30);
		$labelLine->addLabelEntryText('Give ups', $posX + 60);
		$labelLine->addLabelEntryText('Best time', $posX + 90);
		$labelLine->addLabelEntryText('Average time', $posX + 120);
		$labelLine->render();

		$index = 0;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		foreach($players as $player) {
			if ($index % 15 === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPageControl($pageFrame);
			}

			$recordFrame = new Frame();
			$pageFrame->addChild($recordFrame);

			if ($index % 2 === 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$recordFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-0.001);
			}

			if ($player->login === $playerArg->login) {
				$currentQuad = new Quad_Icons64x64_1();
				$recordFrame->addChild($currentQuad);
				$currentQuad->setX($posX + 3.5);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			if (strlen($player->nickname) < 2) {
				$player->nickname = $player->login;
			}

			$labelLine = new LabelLine($recordFrame);
			$labelLine->addLabelEntryText($player->nickname, $posX + 5);
			$labelLine->addLabelEntryText($player->rounds, $posX + 30);
			$labelLine->addLabelEntryText($player->giveUps, $posX + 60);
			if($player->bestTime != -1) {
				$labelLine->addLabelEntryText(Formatter::formatTime($player->bestTime), $posX + 90);
			}
			$labelLine->addLabelEntryText(Formatter::formatTime($this->average($player)), $posX + 120);
			$labelLine->render();

			$recordFrame->setY($posY);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $playerArg);
	}

	public function buttonWidget($login) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_BUTTON_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_BUTTON_POSY);

		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle          = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle       = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::SETTING_BUTTON);

		if(array_key_exists($login, $this->playerIsSavign)) {
			// mainframe
			$frame = new Frame();
			$maniaLink->addChild($frame);
			$frame->setPosition($posX - 15, $posY);

			// Background Quad
			$backgroundQuad = new Quad();
			$frame->addChild($backgroundQuad);
			$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
			$backgroundQuad->setSize(self::SETTING_BUTTON_WIDTH * 4, self::SETTING_BUTTON_HEIGHT * 1.7);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

			$titleLabel = new Label();
			$frame->addChild($titleLabel);
			$titleLabel->setPosition(-10, self::SETTING_LINE_HEIGHT * -0.75);
			$titleLabel->setWidth(self::SETTING_BUTTON_WIDTH);
			$titleLabel->setStyle($labelStyle);
			$titleLabel->setTextSize(1);
			$titleLabel->setText("\$F00".self::SETTING_BUTTON_TITLE_STOP);
			$titleLabel->setAction(self::ACTION_BUTTON);

			$restart = new Label();
			$frame->addChild($restart);
			$restart->setPosition(10, self::SETTING_LINE_HEIGHT * -0.75);
			$restart->setWidth(self::SETTING_BUTTON_WIDTH*1.2);
			$restart->setStyle($labelStyle);
			$restart->setTextSize(1);
			$restart->setText("\$09FRestart");
			$restart->setAction(self::ACTION_RESTART);

			$round = new Label();
			$frame->addChild($round);
			$round->setPosition(-15, self::SETTING_LINE_HEIGHT * -1.7);
			$round->setWidth(self::SETTING_BUTTON_WIDTH);
			$round->setStyle($labelStyle);
			$round->setTextSize(1);
			$round->setText("r".$this->players[$login]->rounds);

			$giveUps = new Label();
			$frame->addChild($giveUps);
			$giveUps->setPosition(-5, self::SETTING_LINE_HEIGHT * -1.7);
			$giveUps->setWidth(self::SETTING_BUTTON_WIDTH);
			$giveUps->setStyle($labelStyle);
			$giveUps->setTextSize(1);
			$giveUps->setText("g".$this->players[$login]->giveUps);

			$averageTime = new Label();
			$frame->addChild($averageTime);
			$averageTime->setPosition(10, self::SETTING_LINE_HEIGHT * -1.7);
			$averageTime->setWidth(self::SETTING_BUTTON_WIDTH*2);
			$averageTime->setStyle($labelStyle);
			$averageTime->setTextSize(1);
			$averageTime->setText(Formatter::formatTime($this->average($this->players[$login])));

			$titleLabel->setTranslate(true);
		} else {
			// mainframe
			$frame = new Frame();
			$maniaLink->addChild($frame);
			$frame->setPosition($posX, $posY);

			// Background Quad
			$backgroundQuad = new Quad();
			$frame->addChild($backgroundQuad);
			$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
			$backgroundQuad->setSize(self::SETTING_BUTTON_WIDTH, self::SETTING_BUTTON_HEIGHT);
			$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
			$backgroundQuad->setAction(self::ACTION_BUTTON);

			$titleLabel = new Label();
			$frame->addChild($titleLabel);
			$titleLabel->setPosition(0, self::SETTING_LINE_HEIGHT * -0.75);
			$titleLabel->setWidth(self::SETTING_BUTTON_WIDTH);
			$titleLabel->setStyle($labelStyle);
			$titleLabel->setTextSize(1);
			$titleLabel->setText("\$0F0".self::SETTING_BUTTON_TITLE_START);

			$titleLabel->setTranslate(true);
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}


	public function average($player) {
		if(count($player->allTimes) == 0) {
			return 0;
		}
		return array_sum($player->allTimes) / count($player->allTimes);
	}

	public function statisticsWidget($login) {
		$lines       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_STATISTICS_LINESCOUNT);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_STATISTICS_WIDTH);
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_STATISTICS_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_STATISTICS_POSY);

		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle          = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle       = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();


		$maniaLink = new ManiaLink(self::SETTING_STATISTICS);

		$height = 7. + $lines * self::SETTING_LINE_HEIGHT;

		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
		$backgroundQuad->setSize($width, $height * 1.07);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setAction(self::ACTION_STATISTICS);

		$titleLabel = new Label();
		$frame->addChild($titleLabel);
		$titleLabel->setPosition(0, self::SETTING_LINE_HEIGHT * -0.9);
		$titleLabel->setWidth($width);
		$titleLabel->setStyle($labelStyle);
		$titleLabel->setTextSize(2);
		$titleLabel->setText(self::SETTING_STATISTICS_TITLE);
		$titleLabel->setTranslate(true);

		$mapId = $this->maniaControl->getMapManager()->getCurrentMap()->uid;
		$players = null;

		if(array_key_exists($mapId, $this->playedMaps)) {
			$players = $this->playedMaps[$mapId];
		}

		if($players == null) {
			$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
			return;
		}
		usort($players, array('JakaPlugins\StatisticsPlayer', 'bestTimeSort'));

		$index = 2;
		foreach($players as $player) {
			if($index >= $lines + 2) {
				break;
			}
			if($this->average($player) == 0) {
				continue;
			}

			$y = -1. - $index * self::SETTING_LINE_HEIGHT;

			$playerFrame = new Frame();
			$frame->addChild($playerFrame);
			$playerFrame->setPosition(0, $y);

			// Displays how many rounds he did
			$rankLabel = new Label();
			$playerFrame->addChild($rankLabel);
			$rankLabel->setHorizontalAlign($rankLabel::LEFT);
			$rankLabel->setX($width * -0.47);
			$rankLabel->setSize($width * 0.1, self::SETTING_LINE_HEIGHT);
			$rankLabel->setTextSize(1);
			$rankLabel->setTextPrefix('$o$fff');
			if($player->giveUps > 0) {
				$rankLabel->setText($player->rounds."-".$player->giveUps);
			}else {
				$rankLabel->setText($player->rounds);
			}

			$rankLabel->setTextEmboss(true);

			// Displays player nickname
			$nameLabel = new Label();
			$playerFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.35 );
			$nameLabel->setSize($width * 0.56 , self::SETTING_LINE_HEIGHT);
			$nameLabel->setTextSize(1);
			$nameLabel->setText(Formatter::stripLinks($player->nickname));
			$nameLabel->setTextEmboss(true);

			$timeLabel = new Label();
			$playerFrame->addChild($timeLabel);
			$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
			$timeLabel->setX($width * 0.48);
			$timeLabel->setSize($width * 0.25, self::SETTING_LINE_HEIGHT);
			$timeLabel->setTextSize(1);
			$timeLabel->setText(Formatter::formatTime($this->average($player)));

			$timeLabel->setTextEmboss(true);
			$index++;
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);

	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//$this->maniaControl = null;
		$this->closeWidget(self::SETTING_BUTTON);
		$this->closeWidget(self::SETTING_STATISTICS);

	}

	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
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
		return 'Plugin offering best and average times over some period of time';
	}
}