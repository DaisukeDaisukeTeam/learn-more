<?php

namespace JoinQuitPopup;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class main extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		if(!file_exists($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder().'config.yml', Config::YAML, [
			'JoinMessage' => '§e%n が世界にやってきました',
			'QuitMessage' => '§e%n が世界を去りました'
		]);
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(!isset($args[0])) return false;
		for($i = 1; $i < count($args); ++$i) $args[0] = "{$args[0]} {$args[$i]}";
		switch($label){
			case 'jmset':
				$this->config->set('JoinMessage', $args[0]);
				$this->config->save();
				$sender->sendMessage("ログインのメッセージを {$args[0]} §fに変更しました");
				break;
			case 'qmset':
				$this->config->set('QuitMessage', $args[0]);
				$this->config->save();
				$sender->sendMessage("ログアウトのメッセージを {$args[0]} §fに変更しました");
				break;
		}
		return true;
	}

	public function PlayerJoin(PlayerJoinEvent $event){
		$event->setJoinMessage(null);
		$message = str_replace('%n', $event->getPlayer()->getName(), $this->config->get('JoinMessage'));
		$this->getServer()->broadcastPopup($message);
	}

	public function PlayerQuit(PlayerQuitEvent $event){
		$event->setQuitMessage(null);
		$message = str_replace('%n', $event->getPlayer()->getName(), $this->config->get('QuitMessage'));
		$this->getServer()->broadcastPopup($message);
	}

}