<?php
// TODO: Maintain plugin namespace using your name or something similar
namespace JakaPlugins;

use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use ManiaControl\Bills\BillManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

// TODO: Maintain plugin class PHPDoc
/**
 * Plugin Description
 *
 * @author  Jaka Vrhovec
 * @version 1.0
 */
class PayReplaySkipPlugin implements Plugin, TimerListener, ManialinkPageAnswerListener, CallbackListener, Callbacks {
	/*
	 * Constants
	 */

	const ID      = 123;
	const VERSION = 1.2;
	const NAME    = 'Pay for Replay/Skip Plugin';
	const AUTHOR  = 'Jaka Vrhovec';

	const SETTING_REPLAY_SKIP = 'Replay.Skip.Plugin';
	const SETTING_POSX        = 'Statistics Position: X';
	const SETTING_POSY        = 'Statistics Position: Y';
	const LINE_HEIGHT         = 4;
	const SETTING_WIDTH       = 18;
	const SETTING_HEIGHT      = 9;

	const ACTION_REPLAY = 'Replay.Action';
	const ACTION_SKIP   = 'Skip.Action';

	const SETTING_ANNOUNCE_SERVER_DONATION = 'Enable Server-Donation Announcements';
	const STAT_PLAYER_DONATIONS            = 'Donated Planets';
	const SETTING_MIN_AMOUNT_SHOWN         = 'Minimum Donation amount to get shown';

	const SKIP_PRICE   = 5;//5000;
	const REPLAY_PRICE = 4;//300;


	/**
	 * Private Properties
	 */
	const LOCK = -2;
	const FREE_ACTION = -1;
	const NO_ACTION   = 0;
	const IS_REPLAY   = 1;
	const IS_SKIP     = 2;
	private $counter = 1;
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $action = -1;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
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

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POSX, -116.8);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POSY, 90);

		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_SCORES, $this, 'handleEndMap');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_REPLAY, $this, 'handleReplayAction');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SKIP, $this, 'handleSkipAction');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handle1Seconds', 1000);

		$this->updateManialink = true;
		$this->replaySkipWidget(false);

		return true;
	}

	public function handleBeginMap() {
		if($this->action == self::LOCK) {
			$this->action = self::FREE_ACTION;
		}

	}

	public function handle1Seconds() {
		$this->replaySkipWidget(false);
	}

	public function replaySkipWidget($login) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POSY);
		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

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
		$quadSkip->setX(self::SETTING_WIDTH * 0.01);
		$quadSkip->setSize(self::SETTING_WIDTH * 0.5, self::SETTING_WIDTH * 0.5);
		$quadSkip->setSubStyle($quadSkip::SUBSTYLE_Right);
		$quadSkip->setAction(self::ACTION_SKIP);

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	public function handleEndMap() {
		if ($this->action == self::IS_REPLAY) {
			$this->maniaControl->getMapManager()->getMapActions()->restartMap();
			$this->counter++;
			$this->action = self::FREE_ACTION;
		} else if ($this->action == self::IS_SKIP) {
			//var_dump("Map was skipped");
			$this->counter = 1;
			$this->action  = self::FREE_ACTION;
		} else {
			//var_dump("No actions done!");
			$this->counter = 1;
		}
		$this->action = self::LOCK;
	}

	public function handleReplayAction(array $callback, Player $player) {
		if ($this->action == self::FREE_ACTION) {
			$this->action = self::IS_REPLAY;
			$this->handleDonation($player, self::REPLAY_PRICE * $this->counter);
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
			if ($this->action == self::IS_REPLAY) {
				$message = 'Pay ' . $amount . ' Planets to $<' . $serverName . '$> for replay?';
			} else {
				$message = 'Pay ' . $amount . ' Planets to $<' . $serverName . '$> for skip?';
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
						if($this->action == self::IS_REPLAY) {
							$message = $player->getEscapedNickname() . ' payed ' . $amount . ' Planets for replay!';
						} else {
							$message = $player->getEscapedNickname() . ' payed ' . $amount . ' Planets for skip!';
						}

						$this->payedAction();
					} else {
						$login   = $player->login;
						$message = 'Payment successful! Thanks.';
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

	public function payedAction() {
		if ($this->action == self::IS_REPLAY) {
			//var_dump("ACTION IS REPLAY");

		} else if ($this->action == self::IS_SKIP) {
			//var_dump("ACTION IS SKIP");
			//$this->action = self::FREE_ACTION;
			$this->maniaControl->getClient()->nextMap();
			$this->maniaControl->getMapManager()->getMapActions()->skipMap();
		}

	}

	public function refusedPayedAction() {
		$this->action = self::FREE_ACTION;
	}

	public function handleSkipAction(array $callback, Player $player) {
		if ($this->action == self::FREE_ACTION) {
			$this->action = self::IS_SKIP;
			$this->handleDonation($player, self::SKIP_PRICE * $this->counter);
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		$this->closeWidget(self::SETTING_REPLAY_SKIP);
	}

	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
	}
}