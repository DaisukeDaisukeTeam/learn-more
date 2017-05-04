<?php


namespace TapID;


use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;




class TapID extends PluginBase implements Listener{

	public function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
if(!file_exists($this->getDataFolder()))@mkdir($this->getDataFolder()); 
  	$this->config = new Config($this->getDataFolder() . "Item.yml", Config::YAML, array());

if($this->config->exists("item")){
}else{
  
$this->config->set("item", "347");
$this->config->save();
}

	}

	


public function onBlockTap(PlayerInteractEvent $event){
$block = $event->getBlock();
$id = $block->getId();
$meta = $block->getDamage();
$player = $event->getPlayer();
$hand = $player->getInventory()->getItemInHand()->getId();
$con = $this->config->get("item");

if($hand == $con){
$player->sendMessage("ID $id ダメージ値 $meta");


}

}
}
