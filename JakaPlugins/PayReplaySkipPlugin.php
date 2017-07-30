<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace JakaPlugins;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons128x128_Blink;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_2;
use FML\Controls\Quads\Quad_ManiaPlanetLogos;
use FML\Controls\Quads\Quad_ManiaPlanetMainMenu;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Structures\TrackMania\OnStartLineEventStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\Structures\Common\BasePlayerTimeStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnWayPointEventStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\Callbacks;
use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Bgs1InRace;
use ManiaControl\Utils\Formatter;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use FML\Script\Features\Paging;
use ManiaControl\Manialinks\LabelLine;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use ManiaControl\Bills\BillManager;
use ManiaControl\Bills\BillData;
use Maniaplanet\DedicatedServer\Structures\Bill;

// TODO: Maintain plugin class PHPDoc
/**
 * Plugin Description
 *
 * @author  Jaka Vrhovec
 * @version 1.0
 */
class PayReplaySkipPlugin implements Plugin,TimerListener,ManialinkPageAnswerListener,CallbackListener {
	/*
	 * Constants
	 */

	const ID      = 123;
	const VERSION = 1.0;
	const NAME    = 'Replay/Skip plugin';
	const AUTHOR  = 'Jaka Vrhovec';

	const SETTING_REPLAY_SKIP = 'Replay.Skip.Plugin';
	const SETTING_POSX = 'Statistics Position: X';
	const SETTING_POSY = 'Statistics Position: Y';
	const LINE_HEIGHT = 4;
	const SETTING_WIDTH = 18;
	const SETTING_HEIGHT = 9;

	const ACTION_REPLAY = 'Replay.Action';
	const ACTION_SKIP = 'Skip.Action';

	const SETTING_ANNOUNCE_SERVER_DONATION = 'Enable Server-Donation Announcements';
	const STAT_PLAYER_DONATIONS            = 'Donated Planets';
	const SETTING_MIN_AMOUNT_SHOWN        = 'Minimum Donation amount to get shown';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	private $action = null;
	const IS_REPLAY = true;
	const IS_SKIP = false;
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


		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POSX, -139.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POSY, 75);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle2Seconds', 2000);

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_REPLAY, $this, 'handleReplayAction');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SKIP, $this, 'handleSkipAction');


		$this->updateManialink = true;

		return true;
	}


	public function handleReplayAction(array $callback, Player $player) {
		if($this->action == null) {
			$this->action = self::IS_REPLAY;
			$this->handleDonation($player, 5);
		}
	}

	public function handleSkipAction(array $callback, Player $player) {
		if($this->action == null) {
			$this->action = self::IS_SKIP;
			$this->handleDonation($player, 5);
		}
	}

	private function handleDonation(Player $player, $amount, $receiver = '', $receiverName = null) {
		if ($amount > 1000000) {
			// Prevent too huge donation amounts that would cause xmlrpc parsing errors
			$message = "You can only donate 1.000.000 Planets at a time!";
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		//FIXME if you write "/donate 50 hallo" than comes Message: Donate to Hallo
		if (!$receiverName) {
			$serverName = $this->maniaControl->getClient()->getServerName();
			if($this->action == self::IS_REPLAY) {
				$message    = 'Donate ' . $amount . ' Planets to $<' . $serverName . '$> for replay?';
			}
			else {
				$message    = 'Donate ' . $amount . ' Planets to $<' . $serverName . '$> for skip?';
			}
		}

		//Send and Handle the Bill
		$this->maniaControl->getBillManager()->sendBill(function ($data, $status) use (&$player, $amount, $receiver) {
			switch ($status) {
				case BillManager::DONATED_TO_SERVER:
					if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_ANNOUNCE_SERVER_DONATION, true)
					    && $amount >= $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MIN_AMOUNT_SHOWN, true)
					) {
						$login   = null;
						$message = $player->getEscapedNickname() . ' donated ' . $amount . ' Planets! Thanks.';
						$this->payedAction();
					} else {
						$login   = $player->login;
						$message = 'Donation successful! Thanks.';
						$this->payedAction();
					}
					$this->maniaControl->getChat()->sendSuccess($message, $login);
					break;
				case BillManager::PLAYER_REFUSED_DONATION:
					$message = 'Transaction cancelled.';
					$this->maniaControl->getChat()->sendError($message, $player);
					$this->refusedPayedAction();
					break;
				case BillManager::ERROR_WHILE_TRANSACTION:
					$message = $data;
					$this->maniaControl->getChat()->sendError($message, $player);
					break;
			}
		}, $player, $amount, $message);

	}

	public function refusedPayedAction() {
		$this->action = null;
	}

	public function payedAction() {
		if($this->action == self::IS_REPLAY) {
			var_dump("ACTION IS REPLAY");
			$this->action = null;
			$this->maniaControl->getClient()->restartMap();

		}
		else if($this->action == self::IS_SKIP) {
			var_dump("ACTION IS SKIP");
			$this->action = null;
			$this->maniaControl->getClient()->nextMap();
		}

	}

	public function handle2Seconds() {
		$this->replaySkipWidget(false);
	}

	public function replaySkipWidget($login) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POSY);
		$labelStyle         = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle          = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle       = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::SETTING_REPLAY_SKIP);

		// mainframe
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setVerticalAlign($backgroundQuad::TOP);
		$backgroundQuad->setSize(self::SETTING_WIDTH, self::SETTING_HEIGHT);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$y = -1. - 0.9 * self::LINE_HEIGHT;

		$buttonFrame = new Frame();
		$frame->addChild($buttonFrame);
		$buttonFrame->setPosition(0, $y);

		$quadReplay = new Quad_UIConstruction_Buttons();
		$buttonFrame->addChild($quadReplay);
		$quadReplay->setHorizontalAlign($quadReplay::LEFT);
		$quadReplay->setX(self::SETTING_WIDTH * -0.45);
		$quadReplay->setSize(self::SETTING_WIDTH * 0.5, self::SETTING_WIDTH * 0.5);
		$quadReplay->setSubStyle($quadReplay::SUBSTYLE_Reload);
		$quadReplay->setAction(self::ACTION_REPLAY);

		$quadSkip = new Quad_UIConstruction_Buttons();
		$buttonFrame->addChild($quadSkip);
		$quadSkip->setHorizontalAlign($quadSkip::LEFT);
		$quadSkip->setX(self::SETTING_WIDTH *0.01);
		$quadSkip->setSize(self::SETTING_WIDTH * 0.5, self::SETTING_WIDTH * 0.5);
		$quadSkip->setSubStyle($quadSkip::SUBSTYLE_Right);
		$quadSkip->setAction(self::ACTION_SKIP);

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}


	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->maniaControl = null;
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
		return 'Replay or skip map by paying planets';
	}
}