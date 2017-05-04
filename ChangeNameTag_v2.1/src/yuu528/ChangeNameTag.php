<?php

namespace yuu528;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

class ChangeNameTag extends PluginBase implements Listener
{
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0755, true); 
		}
		$this->tag = new Config($this->getDataFolder() . "tags.yml", Config::YAML);
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args)
	{
		if ($sender instanceof ConsoleCommandSender){
			if(!isset($args[1])) return false;
			$player = $this->getServer()->getPlayer($args[0]);
			if(!$player instanceof Player) return false;
			$this->ChangeTagC($player, $args[1], $sender);
			return true;
		}
		if(!isset($args[0])) return false;
		if(isset($args[1])){
			$player = $this->getServer()->getPlayer($args[0]);
			if(!$player instanceof Player){
				$this->ChangeTagMe($sender, $args[0]);
				return true;
			}else{
				$this->ChangeTag($player, $args[1], $sender);
				return true;
			}
		}else{
			$this->ChangeTagMe($sender, $args[0]);
			return true;
		}
		return;
	}

	public function ChangeTag($player, $tag, $sender){
		if(!isset($player) or !isset($tag) or !isset($sender)) return;
		if(!$sender->isOp()){
			$player->sendMessage("§a[ChangeNameTag] OP以外が他人のタグを変更することはできません");
		}
			$pname = $player->getName();
			$sname = $sender->getName();

			$player->setNameTag($tag);
			$this->tag->set($pname, $tag);
			$this->tag->save();
			$player->sendMessage("§a[ChangeNameTag] {$sname}さんがあなたのネームタグを{$tag}に変更しました");
			$sender->sendMessage("§a[ChangeNameTag] {$pname}さんのネームタグを{$tag}に変更しました");
			return true;
	}

	public function ChangeTagMe($player, $tag){
		$player->setNameTag($tag);
		$this->tag->set($player->getName(), $tag);
		$this->tag->save();
		if(!$player->isOp()){
			$this->getServer()->broadcastMessage("§a[ChangeNameTag] {$player->getName()}さんがネームタグを{$tag}に変更しました。\n§aこのメッセージはOP以外が変更した際に表示されます");
			return true;
		}
		$player->sendMessage("§a[ChangeNameTag] あなたのネームタグを{$tag}に変更しました");
		return true;
	}

	public function ChangeTagC($player, $tag, $sender){
		$player->setNameTag($tag);
		$this->tag->set($player->getName(), $tag);
		$this->tag->save();
		$player->sendMessage("§a[ChangeNameTag] {$sender->getName()}があなたのネームタグを{$tag}に変更しました");
		return true;
	}

	public function onJoin(PlayerJoinEvent $e){
		if(!$this->tag->exists($e->getPlayer()->getName())) return;
		$e->getPlayer()->setNameTag($this->tag->get($e->getPlayer()->getName()));
		$this->getServer()->broadcastMessage("§a[ChangeNameTag] {$e->getPlayer()->getName()}さんのネームタグは{$e->getPlayer()->getNameTag()}に変えられています");
		return;
	}
}