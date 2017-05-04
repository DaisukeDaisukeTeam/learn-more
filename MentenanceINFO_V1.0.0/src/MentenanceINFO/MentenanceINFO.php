<?php

namespace MentenanceINFO;

use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;

class MentenanceINFO extends PluginBase implements Listener{

    public function onEnable(){

    $this->getLogger()->info("§aMentenanceINFO§eを読み込みました。");
    $this->getLogger()->info("§c二次配布は禁止です。§b製作者 ikatyo");

            $this->getServer()->getPluginManager()->registerEvents($this,$this);
    }
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
    if($command->getName() == "startinfo"){//infoコマンドの処理
    $this->getServer()->broadcastMessage("§a===============INFO===============");
    $this->getServer()->broadcastMessage("§aサーバーメンテナンスを開始します。");
    $this->getServer()->broadcastMessage("§aご理解とご協力をお願い致します。　");
    $this->getServer()->broadcastMessage("§a==================================");
    }

    }
}
