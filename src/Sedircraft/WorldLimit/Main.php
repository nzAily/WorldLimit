<?php

declare(strict_types=1);

namespace Sedircraft\WorldLimit;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    private int $minCoord;
    private int $maxCoord;
    private array $limitedWorlds;
    private array $bypassPlayers = [];

    public function onEnable(): void {
        $this->saveDefaultConfig(); 
        $this->reloadConfiguration();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "WorldLimit plugin enabled!");
    }

    public function onDisable(): void {
        $this->getLogger()->info(TextFormat::RED . "WorldLimit plugin disabled!");
    }

    private function reloadConfiguration(): void {
        $this->minCoord = $this->getConfig()->get("MinCoord", -1000);
        $this->maxCoord = $this->getConfig()->get("MaxCoord", 1000);
        $this->limitedWorlds = $this->getConfig()->get("LimitedWorlds", []);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if (in_array($player->getName(), $this->bypassPlayers, true)) {
            return;
        }

        $position = $event->getTo();

        if ($position === null) {
            return; 
        }

        $worldName = $player->getWorld()->getFolderName();

        if (!in_array($worldName, $this->limitedWorlds, true)) {
            return;
        }

        $x = $position->getX();
        $z = $position->getZ();

        if ($x < $this->minCoord || $x > $this->maxCoord || $z < $this->minCoord || $z > $this->maxCoord) {
            $event->cancel();
            $player->sendMessage(TextFormat::RED . "§r[§l§4!§r] §cYou have reached the limit of the world!");
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) === "limitby") {
            if (!$sender->hasPermission("worldlimit.bypass")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
                return true;
            }

            if (count($args) !== 1) {
                $sender->sendMessage(TextFormat::YELLOW . "Usage: /limitby <player>");
                return true;
            }

            $playerName = $args[0];

            if (in_array($playerName, $this->bypassPlayers, true)) {
                unset($this->bypassPlayers[array_search($playerName, $this->bypassPlayers, true)]);
                $sender->sendMessage(TextFormat::GREEN . "$playerName bypass is not active!");
            } else {
                $this->bypassPlayers[] = $playerName;
                $sender->sendMessage(TextFormat::GREEN . "$playerName bypass active!");
            }

            return true;
        }

        return false;
    }
}
