<?php

namespace benchmark;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\tile\Tile;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Arrow;
use pocketmine\entity\Snowball;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\inventory\PlayerInventory;
use pocketmine\scheduler\CallbackTask;
use pocketmine\nbt\tag\Shorttag;
use pocketmine\nbt\tag\Stringtag;
use pocketmine\nbt\tag\EnumTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\ZombieHorse;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\nbt\NBT;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\entity\Villager;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;


use pocketmine\network\protocol\InteractPacket;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\event\PlayerTextPreSendEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\AddEntityPacket;

use pocketmine\entity\Human;

use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\Player;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Wolf;
use pocketmine\utils\Config;
use pocketmine\network\Network;
use pocketmine\utils\UUID;
use pocketmine\event\player\PlayerItemHeldEvent ;
use pocketmine\entity\Effect;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\entity\Item as ItemEntity;
use pocketmine\event\level\LevelSaveEvent;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\scheduler\PluginTask;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentEntry;
use pocketmine\item\enchantment\EnchantmentLevelTable;
use pocketmine\item\enchantment\EnchantmentList;
use pocketmine\utils\Random;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\Particle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\InstantEnchantParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\SpellParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\network\protocol\AddPlayerPacket;

use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\ButtonClickSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\DoorBumpSound;
use pocketmine\level\sound\DoorCrashSound;
use pocketmine\level\sound\DoorSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\GenericSound;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\level\sound\GhastSound;
use pocketmine\level\sound\GraySplashSound;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\sound\NoteblockSound;
use pocketmine\level\sound\PopSound;
use pocketmine\level\sound\SpellSound;
use pocketmine\level\sound\SplashSound;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\level\sound\ZombieHealSound;
use pocketmine\level\sound\ZombieInfectSound;
use pocketmine\level\Position;
use pocketmine\utils\LevelException;


use pocketmine\utils\Binary;



class benchmark extends PluginBase implements Listener{
		
	public function onEnable()
	{
        if (!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder(), 0744, null);
        $this->yomikomi = new Config($this->getDataFolder() . "yomikomi.json", Config::JSON, array());
		$this->s = new Config($this->getDataFolder() . "score.json", Config::JSON, array());
        }
        $this->yomikomi = new Config($this->getDataFolder() . "score.json", Config::JSON, array());
		$this->s = new Config($this->getDataFolder() . "score.json", Config::JSON, array());
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args)
	{

	
		if($command->getName() == "ben"){
						if (!$sender instanceof Player) return $sender->sendMessage(TextFormat::RED."このコマンドはゲーム内で使用してください");
			$subCommand = strtolower(array_shift($args));	
			switch ($subCommand){	
			
				case "start":
$time_start = microtime(true);		
$this->getServer()->broadcastMessage("§bブロック設置のベンチマークを開始します : メモリーの性能");			
				$player = $sender->getPlayer();
				$name = $player->getName();
$count = 1500000;//回数	
$level = $player->getLevel();//Levelオブジェクトの取得
for($i = 0;$i < $count; ++$i){		
$block = Block::get(1,0);//Blockオブジェクトの生成
$vector = new Vector3(1000, 127, 1000);
$level->setBlock($vector, $block);
$block = Block::get(1,0);//Blockオブジェクトの生成
$vector = new Vector3(1000, 126, 1000);
$level->setBlock($vector, $block);
$block = Block::get(1,0);//Blockオブジェクトの生成
$vector = new Vector3(1000, 125, 1000);
$level->setBlock($vector, $block);
}

$time = microtime(true) - $time_start;
if($time < 20){
$this->getServer()->broadcastMessage("§bブロックの設置や破壊は高速です");
}elseif($time < 24){
$this->getServer()->broadcastMessage("§bブロックの設置や破壊は快適です");
}elseif($time < 28){
$this->getServer()->broadcastMessage("§bブロックの設置や破壊はある程度です");
}elseif($time < 33){
$this->getServer()->broadcastMessage("§b測定不能【 Timeout 】");
}


$time_start1 = microtime(true);
$this->getServer()->broadcastMessage("§bディスク性能 : KB単位の書き込みと読み込み");
$count = 200000;//回数	
$level = $player->getLevel();//Levelオブジェクトの取得
for($i = 0;$i < $count; ++$i){					

  $this->yomikomi->set("KAKIKOMI1", "以下のファイルは編集しても意味はありません");
 $this->yomikomi->set("KAKIKOMI2", "読み込みと書き込みの速度を計算しています。"); 
 $this->yomikomi->save();
 $m = $this->yomikomi->get("KAKIKOMI1");
$m1 = $this->yomikomi->get("KAKIKOMI2"); 
}


$time = microtime(true) - $time_start1;
if($time < 15){
$this->getServer()->broadcastMessage("§b書き込みや読み込みは高速です");
}elseif($time < 23){
$this->getServer()->broadcastMessage("§b書き込みや読み込みは快適です");
}elseif($time < 26){
$this->getServer()->broadcastMessage("§b書き込みや読み込みはある程度です");
}elseif($time < 30){
$this->getServer()->broadcastMessage("§b測定不能【 Timeout 】");
}

$time = microtime(true) - $time_start;
$time2 = $time * 1000;
$time1 = intval($time2);
$time3 = $time1 / 1000;
$this->getServer()->broadcastMessage("§a処理に $time3 秒かかりました");
$score = $time1 * 1;
$score1 = 60000 - $score;
if($score1 < 0){
	$this->getServer()->broadcastMessage("§aスコアの測定不能でした");	
}
	$this->getServer()->broadcastMessage("§aベンチマークスコアは $score1 でした");	
	if($score1 > 25000){
$this->getServer()->broadcastMessage("§a評価 S : かなり快適な処理が見込めます");	
	}elseif($score1 > 22000){
$this->getServer()->broadcastMessage("§a評価 A+ : S以下A以上の快適な処理が見込めます");
	}elseif($score1 > 19000){
$this->getServer()->broadcastMessage("§a評価 A : 快適な処理が見込めます");
	}elseif($score1 > 16000){
$this->getServer()->broadcastMessage("§a評価 B : ある程度の負荷なら問題なく動作します");
	}elseif($score1 > 9000){
$this->getServer()->broadcastMessage("§a評価 B : 少人数向けのサーバー構築にお勧めです");	
	}else{
$this->getServer()->broadcastMessage("§a評価 D : 友達と一緒に遊ぶサーバー向け");		
	}
	$this->getServer()->broadcastMessage("下は目安です【VPS】 OSやプランによってスコアは変動します　全て1G プラン　一部SSD");
	$this->getServer()->broadcastMessage("KAGOYA : 24000~, serversman : 測定不可~");
$this->getServer()->broadcastMessage("Sakura : 23000~, お名前.com : 25000~");
$this->getServer()->broadcastMessage("CloudCORE : 25000~, WebARENA : 24000~");
	$this->getServer()->broadcastMessage("konoha : 測定不可~ 28000, SPPD : 測定不可~");
 $this->s->set("score", "$score1");
 $this->s->set("Time", "$time"); 
 $this->s->save();
										break;				
			
			}
		}
	}
}