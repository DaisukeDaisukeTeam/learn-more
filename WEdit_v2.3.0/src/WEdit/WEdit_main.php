<?php

namespace WEdit;

/*
	ワールドエディター
*/

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\level\level;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class WEdit_main extends PluginBase implements Listener, CommandExecutor{
	private $sessions, $id, $eid, $enable, $dataFolder;

	public function onLoad(){
		$this->getLogger()->info("WEditが読み込まれました。".TextFormat::GREEN."(v2.0.0 - by Gonbe34)");
	}

	public function onEnable(){
	        $dataFolder = Server::getInstance()->getPluginManager()->getPlugin("WEdit")->getDataFolder();
	        if(!file_exists($dataFolder)){
			@mkdir($dataFolder, 0744, true);
		}
		$settings = new Config($dataFolder."settings.yml", Config::YAML,
			array(
				"block_id" => 155,
				"elase_id" => 267,
			)
		);
		$this->id = $settings->get("block_id");
		$this->eid = $settings->get("elase_id");
		$this->sessions = [];
		$this->cmditem = [
			"//e",
			"//e 1",
			"//e 2"
		];
		$this->dataFolder = $dataFolder;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	#event: ブロック破壊
	public function BlockBreak(BlockBreakEvent $event){//1
		$id = $event->getItem()->getID();
		if($id == $this->id){
			$player = $event->getPlayer();
			$user = $player->getName();
			if(empty($this->sessions[$user][1])){
				$x = $event->getBlock()->x;
				$y = $event->getBlock()->y;
				$z = $event->getBlock()->z;
				$this->sessions[$user][1] = array($x, $y, $z);
				$ms = "[WEdit] POS1が設定されました。: $x, $y, $z";
				if(isset($this->sessions[$user][2])){//片方がセットされていたら
					$num = $this->countBlocks($player);
					if($num != false) $ms .= " (計".$num."ブロック)";
				}
				$player->sendMessage($ms);
				$event->setCancelled(true);
			}
		}
		return true;
	}

	#event: ブロック設置
	public function BlockPlace(BlockPlaceEvent $event){//2
		$id = $event->getItem()->getID();
		if($id == $this->id){
			$player = $event->getPlayer();
			$user = $player->getName();
			if(empty($this->sessions[$user][2])){
				$x = $event->getBlock()->x;
				$y = $event->getBlock()->y;
				$z = $event->getBlock()->z;
				$this->sessions[$user][2] = array($x, $y, $z);
				$ms = "[WEdit] POS2が設定されました。: $x, $y, $z";
				if(isset($this->sessions[$user][1])){//片方がセットされていたら
					$num = $this->countBlocks($player);
					if($num != false) $ms .= " (計".$num."ブロック)";
				}
				$player->sendMessage($ms);
				$event->setCancelled(true);
			}
		}
		return true;
	}

	#event: アイテムでブロックタッチ
	public function BlockTouch(PlayerInteractEvent $event){//1
		$id = $event->getItem()->getID();
		if($id == $this->eid){
			$player = $event->getPlayer();
			$user = $player->getName();
			$cmd = !empty($this->sessions[$user][4]) ? $this->sessions[$user][4] : 0;
			switch($event->getAction()){
				case 1:
				//メニュー切り替え
					$cmd += 1;
					if(empty($this->cmditem[$cmd])){
						$cmd = 0;
					}
					$ms = "[WEdit] コマンド「".$this->cmditem[$cmd]."」を選択";
					$this->sessions[$user][4] = $cmd;
					$player->sendMessage($ms);
					$event->setCancelled(true);
				break;
				case 3:
				//メニュー決定
					switch($cmd){
						case 0:
							$this->erase($player);
						break;
						case 1:
						case 2:
							$this->erase($player,$cmd);
						break;
					}
					$event->setCancelled(true);
				break;
			}

		}
		return true;
	}

	#コマンド受け取り
	public function onCommand(CommandSender $sender, Command $command, $label, array $params){
		$user = $sender->getName();
		//print($user);
		switch($command->getName()){
			case "/":
			case "/help":
				$ms ="=== WEditの使い方 ===\n".
				     "* ID:".$this->id."を持ち、ブロックを破壊することでPOS1を設定\n".
				     "* ID:".$this->id."を設置することでPOS2を設定\n".
				     "* POS1とPOS2を設定し終えたら、以下のコマンドを実行しましょう\n".
				     "* //set <id> :範囲をブロックで埋めます\n".
				     "* //cut :範囲のブロックを消します\n".
				     "* //replace <id1> <id2> :id1をid2のブロックに置き換えます\n".
				     "* //air <id> :範囲内のidのブロックのみ削除します\n".
				     "* //e :設定されたPOS1とPOS2を削除します\n".
				     "";
				$sender->sendMessage($ms);
			break;
			case "/e":
				if(count($params) == 0){
					$this->erase($sender);
				}else{
					$this->erase($sender,$params[0]);
				}
			break;
			case "/set":
				if(count($params) != 1)return false;
				$this->set($sender,$params[0]);
			break;
			case "/replace":
				if(count($params) != 2)return false;
				$this->replace($sender,$params[0],$params[1]);
			break;
			case "/air":
				if(count($params) != 1)return false;
				$this->replace($sender,$params[0],0);
			break;
			case "/cut":
				$this->set($sender);
			break;
			case "/undo":
				$this->undo($sender);
			case "/pos":
				if(count($params) != 4)return false;
				$num = $params[0];
				if(empty($this->sessions[$user][$num])){
					$x = $params[1];
					$y = $params[2];
					$z = $params[3];
					$this->sessions[$user][$num] = array($x, $y, $z);
					$ms = "[WEdit] POS".$num."が設定されました。: $x, $y, $z";
					$p = $num == 1 ? 2 : 1;
					if(isset($this->sessions[$user][$p])){//片方がセットされていたら
						$num = $this->countBlocks($sender);
						if($num != false) $ms .= " (計".$num."ブロック)";
					}
					$sender->sendMessage($ms);
				}else{
					$sender->sendMessage("[WEdit] 不正なパラメータのため、座標指定できません。");
				}
			break;
			case "/copy":
				$this->copy($sender);
			break;
			case "/paste":
				$this->paste($sender);
			break;
			case "/output":
				$this->outputObject($sender);
			break;
			case "/input":
				if(count($params) == 1){
					$this->inputObject($sender, $params[0]);
				}else{
					return false;
				}
			break;
		}
		return true;
	}

	#copy
	public function copy($player){
		$name = $player->getName();
		if(isset($this->sessions[$name][1]) and isset($this->sessions[$name][2])){
			$pos = $this->sessions[$name];
			$sx = min($pos[1][0], $pos[2][0]);
			$sy = min($pos[1][1], $pos[2][1]);
			$sz = min($pos[1][2], $pos[2][2]);
			$ex = max($pos[1][0], $pos[2][0]);
			$ey = max($pos[1][1], $pos[2][1]);
			$ez = max($pos[1][2], $pos[2][2]);
			$num = ($ex - $sx + 1) * ($ey - $sy +1) * ($ez - $sz + 1);

			Server::getInstance()->broadcastMessage("[WEdit] ".$name."がコピーを開始します…(copy : ".$num."ブロック)");

			$level = $player->getLevel();

			$data = array();
			for($x = $sx; $x <= $ex; ++$x){
				for($y = $sy; $y <= $ey; ++$y){
					for($z = $sz; $z <= $ez; ++$z){
						$data[] = array($x, $y, $z, $level->getBlockIdAt($x, $y ,$z), $level->getBlockDataAt($x, $y ,$z));
					}
				}
			}

			$this->sessions[$name][4] = $data;
			Server::getInstance()->broadcastMessage("[WEdit] 変更が終了しました。");
		}else{
			$player->sendMessage("[WEdit] ERROR: POS1とPOS2が指定されていません。\n[WEdit] //helpを打ち、使い方を読んでください。");
		}
	}

	#json形式で、copyしたものを出力
	public function outputObject($player, $filename=false){
		$name = $player->getName();
		if(isset($this->sessions[$name][4])){
			$player->sendMessage("[WEdit] オブジェクトの出力中…");
			$content = json_encode($data);
			if(!$filename){
				$filename = "wedit_output_".date("Y-m-d H:i:s");
			}
			file_put_contents($this->dataFolder.$filename.".json", $content);
			//未完成
			$player->sendMessage("[WEdit] オブジェクトが「".$filename.".json」として出力されました。");
		}else{
			$player->sendMessage("[WEdit] copyを使ってから実行してください");
		}
	}

	#json形式から入力
	public function inputObject($player, $filename){
		$fname = $this->dataFolder.$filename.".json";
		if(file_exists($fname)){
			$player->sendMessage("[WEdit] オブジェクトの入力中…");
			$name = $player->getName();
			$content = file_get_contents($fname);
			$ar = json_decode($content, true);
			if(isset($ar[1])){
				$this->sessions[$name][4] = $ar;
				$player->sendMessage("[WEdit] オブジェクトが入力されました。");
			}else{
				$player->sendMessage("[WEdit] ファイルが壊れています");
			}
		}else{
			$player->sendMessage("[WEdit] 存在しないファイル");
		}
	}

	#setのコマンド処理
	#param0 : player obj
	#param1 : item id
	public function set($player, $id = 0){
		$name = $player->getName();
		if(isset($this->sessions[$name][1]) and isset($this->sessions[$name][2])){
			$pos = $this->sessions[$name];
			$sx = min($pos[1][0], $pos[2][0]);
			$sy = min($pos[1][1], $pos[2][1]);
			$sz = min($pos[1][2], $pos[2][2]);
			$ex = max($pos[1][0], $pos[2][0]);
			$ey = max($pos[1][1], $pos[2][1]);
			$ez = max($pos[1][2], $pos[2][2]);
			$num = ($ex - $sx + 1) * ($ey - $sy +1) * ($ez - $sz + 1);

			if($id == 0){
				Server::getInstance()->broadcastMessage("[WEdit] ".$name."が変更を開始します…(cut : ".$num."ブロック)");
			}else{
				Server::getInstance()->broadcastMessage("[WEdit] ".$name."が変更を開始します…(set $id : ".$num."ブロック)");
			}

			$level = $player->getLevel();
			$did = explode(":", $id);
			if(count($did) != 2){
				$block = Block::get($did[0]);
			}else{
				$block = Block::get($did[0], $did[1]);
			}

			$data = array();
			for($x = $sx; $x <= $ex; ++$x){
				for($y = $sy; $y <= $ey; ++$y){
					for($z = $sz; $z <= $ez; ++$z){
						$data[] = array($x, $y, $z, $level->getBlockIdAt($x, $y ,$z), $level->getBlockDataAt($x, $y ,$z));
						$posi = new Vector3($x, $y, $z);
						$level->setBlock($posi, $block);
					}
				}
			}

			$this->sessions[$name][3] = $data;
			Server::getInstance()->broadcastMessage("[WEdit] 変更が終了しました。");
		}else{
			$player->sendMessage("[WEdit] ERROR: POS1とPOS2が指定されていません。\n[WEdit] //helpを打ち、使い方を読んでください。");
		}
	}

	#replaceのコマンド処理
	#param0 : player obj
	#param1 : item id 置き換えるほう
	#param2 : item id セットされてるほう
	public function replace($player, $id1, $id2){
		$name = $player->getName();
		if(isset($this->sessions[$name][1]) and isset($this->sessions[$name][2])){
			$pos = $this->sessions[$name];
			$sx = min($pos[1][0], $pos[2][0]);
			$sy = min($pos[1][1], $pos[2][1]);
			$sz = min($pos[1][2], $pos[2][2]);
			$ex = max($pos[1][0], $pos[2][0]);
			$ey = max($pos[1][1], $pos[2][1]);
			$ez = max($pos[1][2], $pos[2][2]);
			$num = ($ex - $sx + 1) * ($ey - $sy +1) * ($ez - $sz + 1);

			Server::getInstance()->broadcastMessage("[WEdit] ".$name."が変更を開始します…($id1 => $id2) : ".$num."ブロック)");

			$count = 0;
			$level = $player->getLevel();
			$did = explode(":", $id2);
			if(count($did) != 2){
				$block = Block::get($did[0]);
			}else{
				$block = Block::get($did[0], $did[1]);
			}
			$data = array();
			for($x = $sx; $x <= $ex; ++$x){
				for($y = $sy; $y <= $ey; ++$y){
					for($z = $sz; $z <= $ez; ++$z){
						if($level->getBlockIdAt($x, $y ,$z) == $id1){
							$data[] = array($x, $y, $z, $id1, 0);
							$posi = new Vector3($x, $y, $z);
							$level->setBlock($posi, $block);
							$count ++;
						}
					}
				}
			}

			$this->sessions[$name][3] = $data;
			Server::getInstance()->broadcastMessage("[WEdit] 変更が終了しました。");
		}else{
			$player->sendMessage("[WEdit] ERROR: POS1とPOS2が指定されていません。\n[WEdit] //helpを打ち、使い方を読んでください。");
		}
	}

	#undoのコマンド処理
	#param0 : player obj
	public function undo($player){
		$name = $player->getName();
		if(isset($this->sessions[$name][3])){
			$data = $this->sessions[$name][3];
			$num = count($data);
			Server::getInstance()->broadcastMessage("[WEdit] ".$name."が変更を開始します…(undo : ".$num."ブロック)");

			$level = $player->getLevel();
			foreach($data as $b){
				$block = Block::get($b[3], $b[4]);
				$posi = new Vector3($b[0], $b[1], $b[2]);
				$level->setBlock($posi, $block);
			}
			unset($this->sessions[$name][3]);
			Server::getInstance()->broadcastMessage("[WEdit] 変更が終了しました。");
		}else{
			$player->sendMessage("[WEdit] ERROR: やり直し出来ません。");
		}
	}



	#ブロックの数を数える
	#param0 : player obj
	public function countBlocks($player){
		if($player == null){
			$name = CONSOLE;
		}else{
			$name = $player->getName();
		}
		if(isset($this->sessions[$name][1]) and isset($this->sessions[$name][2])){
			$pos = $this->sessions[$name];
			$sx = min($pos[1][0], $pos[2][0]);
			$sy = min($pos[1][1], $pos[2][1]);
			$sz = min($pos[1][2], $pos[2][2]);
			$ex = max($pos[1][0], $pos[2][0]);
			$ey = max($pos[1][1], $pos[2][1]);
			$ez = max($pos[1][2], $pos[2][2]);
			$num = ($ex - $sx + 1) * ($ey - $sy +1) * ($ez - $sz + 1);
			if($num < 0) $num * -1;
			return $num;
		}else{
			return false;
		}
	}

	#座標データ削除
	#param0 : player obj
	#param1 : type
	public function erase($player, $t = 0){
		$name = $player->getName();
		if(isset($this->sessions[$name])){
			if($t == 0){
				unset($this->sessions[$name]);
				$ms = "[WEdit] 座標データは削除されました。";
			}else{
				if(isset($this->sessions[$name][$t])){
					unset($this->sessions[$name][$t]);
					$ms = "[WEdit] POS".$t."は削除されました。";
				}else{
					$ms = "[WEdit] POS".$t."は設定されていません。";
				}
			}
		}else{
			$ms = "[WEdit] POS1もPOS2も設定されていません。";
		}
		$player->sendMessage($ms);
		return true;
	}



	/* WEditAPI */

	#プレイヤーのセッションを取得
	public function getSession($user){
		if(isset($this->sessions[$user])){
			return [$this->sessions[$user][1], $this->sessions[$user][2]];
		}else{
			return false;
		}
	}

	#プレイヤーが直前に置き換えたブロックを取得
	public function getReplacedBlock($user){
		if(!empty($this->sessions[$user][3])){
			return $this->sessions[$user][3];
		}else{
			return false;
		}
	}
}