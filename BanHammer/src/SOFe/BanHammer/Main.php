<?php

namespace SOFe\BanHammer;

use Generator;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\plugin\PluginBase;
use SOFe\AwaitStd\Await;
use SOFe\AwaitStd\AwaitStd;
use SOFe\AwaitStd\QuitException;

final class Main extends PluginBase {
	/** @var AwaitStd */
	private $std;

	public function onEnable() : void {
		$this->std = AwaitStd::init($this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		\assert($command->getName() === "bh");

		Await::f2c(function() use($sender) : Generator {
			try {
				if(!($sender instanceof Player)) {
					$sender->sendMessage("Run this command in-game.");
					return true;
				}

				$sender->sendMessage("[BanHammer] Click the player to ban. Type \"cancel\" in chat to cancel.");
				while(true) {
					// This yield line will suspend until either nextAttack or nextChat happens
					[$which, $event] = yield Await::race([
						"attack" => $this->std->nextAttack($sender),
						"chat" => $this->std->nextChat($sender),
					]);
					if($which === "chat") {
						// nextChat happened first
						if(strtolower($event->getMessage()) === "cancel") {
							$event->setCancelled();
							$sender->sendMessage("BanHammer has been cancelled");
							return; // we don't need to wait for anything anymore
						} else {
							continue; // if player chats, we don't handle it, so we keep on racing attack/chat again
						}
					}

					\assert($which === "attack");
					\assert($event instanceof EntityDamageByEntityEvent);
					$target = $event->getEntity();
					if(!($target instanceof Player)) {
						$sender->sendMessage("You can only ban players. Try clicking agian.");
						continue;
					} else {
						break;
					}
				}
				$sender->sendMessage("[BanHammer] Selected {$target->getName()}.");
				$sender->sendMessage("[BanHammer] Type the ban message.");
				$message = yield $this->std->consumeNextChat($sender);
				$this->getServer()->getNameBans()->addBan($target->getName(), $message, null, $sender->getName());
				$target->kick("Banned: $message");
				$sender->sendMessage("{$target->getName()} has been banned.");
			} catch(QuitException $e) {
				// nothing needs to be done if sender quits
			}
		});

		return true; // we don't need that usage message anyway
	}
}
