<?php

namespace SOFe\BanHammer;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
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

		Await::f2c(function() use($sender) : void {
			try {
				if(!($sender instanceof Player)) {
					$sender->sendMessage("Run this command in-game.");
					return true;
				}

				$sender->sendMessage("[BanHammer] Click the player to ban");
				while(true) {
					$event = yield $this->std->nextAttack($sender);
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
				$message = yield $sender->nextChat();
				$this->getServer()->getNameBans()->addBan($target->getName(), $message);
				$target->kick("Banned: $message");
				$sender->sendMessage("{$target->getName()} has been banned.");
			} catch(QuitException $e) {
				// nothing needs to be done if sender quits
			}
		});
	}
}
