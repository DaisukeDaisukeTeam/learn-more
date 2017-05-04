<?php

namespace onebone\itemcloud;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class MainClass extends PluginBase implements Listener{
	/**
	 * @var MainClass
	 */
	private static $instance;

	/**
	 * @var ItemCloud[]
	 */
	private $clouds;

	/**
	 * @return MainClass
	 */
	public static function getInstance(){
		return self::$instance;
	}

	/**
	 * @param Player|string $player
	 *
	 * @return ItemCloud|bool
	 */
	public function getCloudForPlayer($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->clouds[$player])){
			return $this->clouds[$player];
		}
		return false;
	}

	/**************************   Non-API part   ***********************************/

	public function onEnable(){
		if(!self::$instance instanceof MainClass){
			self::$instance = $this;
		}
		@mkdir($this->getDataFolder());
		if(!is_file($this->getDataFolder()."ItemCloud.dat")){
			file_put_contents($this->getDataFolder()."ItemCloud.dat", serialize([]));
		}
		$data = unserialize(file_get_contents($this->getDataFolder()."ItemCloud.dat"));

		$this->saveDefaultConfig();
		if(is_numeric($interval = $this->getConfig()->get("auto-save-interval"))){
			$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new SaveTask($this), $interval * 1200, $interval * 1200);
		}

		$this->clouds = [];
		foreach($data as $datam){
			$this->clouds[$datam[1]] = new ItemCloud($datam[0], $datam[1]);
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $params){
		switch($command->getName()){
			case "ic":
				if(!$sender instanceof Player){
					$sender->sendMessage("ゲーム内で入力して下さい");
					return true;
				}
				$sub = array_shift($params);
				switch($sub){
					case "register":
						if(!$sender->hasPermission("itemcloud.command.register")){
							$sender->sendMessage(TextFormat::RED."このコマンドの実行に必要な権限がありません");
							return true;
						}
						if(isset($this->clouds[strtolower($sender->getName())])){
							$sender->sendMessage("[ItemCloud] あなたは既にItemCloudに登録しています");
							break;
						}
						$this->clouds[strtolower($sender->getName())] = new ItemCloud([], $sender->getName());
						$sender->sendMessage("[ItemCloud] 無事に登録完了しました。");
						break;
					case "upload":
						if(!$sender->hasPermission("itemcloud.command.upload")){
							$sender->sendMessage(TextFormat::RED."このコマンドの実行に必要な権限がありません");
							return true;
						}
						if(!isset($this->clouds[strtolower($sender->getName())])){
							$sender->sendMessage("[ItemCloud] まず§b/ic register§fで登録して下さい");
							break;
						}
						$item = array_shift($params);
						$amount = array_shift($params);
						if(trim($item) === "" or !is_numeric($amount)){
							usage:
							$sender->sendMessage("使用法: /ic upload <アイテムID[:ダメージ値]> <数>");
							break;
						}
						$amount = (int) $amount;
						$item = Item::fromString($item);
						$item->setCount($amount);

						$count = 0;
						foreach($sender->getInventory()->getContents() as $i){
							if($i->getID() == $item->getID() and $i->getDamage() == $item->getDamage()){
								$count += $i->getCount();
							}
						}
						if($amount <= $count){
							$this->clouds[strtolower($sender->getName())]->addItem($item->getID(), $item->getDamage(), $amount, true);
							$sender->sendMessage("[ItemCloud] 無事にあなたのアカウントにアイテムがアップロードされました");
						}else{
							$sender->sendMessage("[ItemCloud] アップロードするアイテムを持っていません");
						}
						break;
					case "download":
						if(!$sender->hasPermission("itemcloud.command.download")){
							$sender->sendMessage(TextFormat::RED."このコマンドの実行に必要な権限がありません");
							return true;
						}
						$name = strtolower($sender->getName());
						if(!isset($this->clouds[$name])){
							$sender->sendMessage("[ItemCloud] まず§b/ic register§fで登録して下さい");
							break;
						}
						$item = array_shift($params);
						$amount = array_shift($params);
						if(trim($item) === "" or !is_numeric($amount)){
							usage2:
							$sender->sendMessage("使用法: /ic download <アイテムID[:ダメージ値]> <数>");
							break;
						}
						$amount = (int)$amount;
						$item = Item::fromString($item);
						$item->setCount($amount);

						if(!$this->clouds[$name]->itemExists($item->getID(), $item->getDamage(), $amount)){
							$sender->sendMessage("[ItemCloud] アップロードするアイテムを持っていません");
							break;
						}

						if($sender->getInventory()->canAddItem($item)){
							$this->clouds[$name]->removeItem($item->getID(), $item->getDamage(), $amount);
							$sender->getInventory()->addItem($item);
							$sender->sendMessage("[ItemCloud] 無事にアイテムをダウンロードしました");
						}else{
							$sender->sendMessage("[ItemCloud] アイテムをダウンロードするためのインベントリがありません");
						}
						break;
					case "list":
						if(!$sender->hasPermission("itemcloud.command.list")){
							$sender->sendMessage(TextFormat::RED."このコマンドの実行に必要な権限がありません");
							return true;
						}
						$name = strtolower($sender->getName());
						if(!isset($this->clouds[$name])){
							$sender->sendMessage("[ItemCloud] まず§b/ic register§fで登録して下さい");
							break;
						}
						$output = "[ItemCloud] 保存されているアイテム : \n";
						foreach($this->clouds[$name]->getItems() as $item => $count){
							$output .= "$item : $count 個\n";
						}
						$sender->sendMessage($output);
						break;
					case "count":
						if(!$sender->hasPermission("itemcloud.command.count")){
							$sender->sendMessage(TextFormat::RED."このコマンドの実行に必要な権限がありません");
							return true;
						}
						$name = strtolower($sender->getName());
						if(!isset($this->clouds[$name])){
							$sender->sendMessage("[ItemCloud] まず§b/ic register§fで登録して下さい");
							return true;
						}
						$item = array_shift($params);
						if(trim($item) === ""){
							$sender->sendMessage("使用法: /ic count <アイテムID>");
							return true;
						}

						$item = Item::fromString($item);

						if(($count = $this->clouds[$name]->getCount($item->getID(), $item->getDamage())) === false){
							$sender->sendMessage("[ItemCloud] ".$item->getName()." というアイテムは保存されていません");
							break;
						}else{
							$sender->sendMessage("[ItemCloud] ".$item->getName()."というアイテムは".$count."個あります");
						}
						break;
					default:
						$sender->sendMessage("[ItemCloud] 使用法: ".$command->getUsage());
				}
				return true;
		}
		return false;
	}

	public function save(){
		$save = [];
		foreach($this->clouds as $cloud){
			$save[] = $cloud->getAll();
		}
		file_put_contents($this->getDataFolder()."ItemCloud.dat", serialize($save));
	}

	public function onDisable(){
		$this->save();
		$this->clouds = [];
	}
}
