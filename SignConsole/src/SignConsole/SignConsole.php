<?php

namespace SignConsole;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;

class SignConsole extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onBlockTouch(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		$b = $e->getBlock();
		$id = $b->getId();
		if($id === 63 || $id === 68){
			$sign = $p->getLevel()->getTile(new Vector3($b->x, $b->y, $b->z));
			$strs = $sign->getText();
			$str = $strs[0].$strs[1].$strs[2].$strs[3];
			if(strpos($strings, "##")){
				preg_match("/^##(.+[^\s])/", $str, $line);
				$this->getServer()->dispatchCommand($p, $line[1]);
			}
		}
	}

}
