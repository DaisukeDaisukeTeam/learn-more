<?php

namespace ViewerName;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class main extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		if(!file_exists($this->getDataFolder())) mkdir($this->getDataFolder());
		$this->display = new Config($this->getDataFolder().'display.yml', Config::YAML);
		$this->tag = new Config($this->getDataFolder().'tag.yml', Config::YAML);
	}

	public function PlayerLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		if($this->display->exists($name)){
			$display = $this->display->get($name);
			$player->setDisplayName($display);
		}else{
			$this->display->set($name, $name);
			$this->display->save();
		}
		if($this->tag->exists($name)){
			$tag = $this->tag->get($name);
			$player->setNameTag($tag);
		}else{
			$this->tag->set($name, $name);
			$this->tag->save();
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($label){
			case 'display':
				if(!isset($args[1])) return false;
				$player = $this->getServer()->getPlayer($args[0]);
				if($player instanceof Player){
					for($i = 2; $i < count($args); ++$i) $args[1] = "{$args[1]} {$args[$i]}";
					$player->setDisplayName($args[1]);
					$name = $player->getName();
					$this->display->set($name, $args[1]);
					$this->display->save();
					$player->sendMessage("コメントの名前が {$args[1]} §fに変更されました");
					$sender->sendMessage("{$name} のコメントの名前を {$args[1]} §fに変更しました");
				}else{
					$sender->sendMessage('§c指定されたプレイヤーが見つかりません');
				}
				break;
			case 'tag':
				if(!isset($args[1])) return false;
				$player = $this->getServer()->getPlayer($args[0]);
				if($player instanceof Player){
					for($i = 2; $i < count($args); ++$i) $args[1] = "{$args[1]} {$args[$i]}";
					$player->setNameTag($args[1]);
					$name = $player->getName();
					$this->tag->set($name, $args[1]);
					$this->tag->save();
					$player->sendMessage("タグの名前が {$args[1]} §fに変更されました");
					$sender->sendMessage("{$name} のタグの名前を {$args[1]} §fに変更しました");
				}else{
					$sender->sendMessage('§c指定されたプレイヤーが見つかりません');
				}
				break;
			case 'rd':
				if(!isset($args[0])) return false;
				for($i = 1; $i < count($args); ++$i) $args[0] = "{$args[0]} {$args[$i]}";
				if($name = array_search($args[0], $this->display->getAll())){
					$player = $this->getServer()->getPlayer($name);
					if($player instanceof Player){
						$player->setDisplayName($name);
						$player->sendMessage('コメントの名前がリセットされました');
					}
					$this->display->set($name, $name);
					$this->display->save();
					$sender->sendMessage("{$args[0]} のコメントの名前をリセットしました");
				}else{
					$sender->sendMessage('§c指定されたプレイヤーが見つかりません');
				}
				break;
			case 'rt':
				if(!isset($args[0])) return false;
				for($i = 1; $i < count($args); ++$i) $args[0] = "{$args[0]} {$args[$i]}";
				if($name = array_search($args[0], $this->tag->getAll())){
					$player = $this->getServer()->getPlayer($name);
					if($player instanceof Player){
						$player->setNameTag($name);
						$player->sendMessage('タグの名前がリセットされました');
					}
					$this->tag->set($name, $name);
					$this->tag->save();
					$sender->sendMessage("{$args[0]} のタグの名前をリセットしました");
				}else{
					$sender->sendMessage('§c指定されたプレイヤーが見つかりません');
				}
				break;
		}
		return true;
	}

}