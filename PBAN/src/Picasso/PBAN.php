<?php

namespace Picasso;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\item;
class PBAN extends PluginBase implements Listener{
public function onEnable(){
if(!file_exists($this->getDataFolder())){
    mkdir($this->getDataFolder(), 0744, true);
}
$this->playerlist = new Config($this->getDataFolder() . "playerlist.json",  Config::JSON,
array(
));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		
$this->host = "private.nzr9n.tyo1.database-hosting.conoha.io";//ホスト名
$this->user = "nzr9n_lupin4";//ユーザー名
$this->pass = "Kozirou25";//パスワード
$this->dbname = "nzr9n_bans";//データーベース名かな？
$this->db = new \mysqli($this->host,$this->user,$this->pass,$this->dbname);
if($this->db->connect_error){
$this->getLogger()->info("§eMySQLサーバーに接続できませんでした");
$this->getServer()->shutdown();
}else{
$this->getLogger()->info("§bMySQLサーバーに接続しました");
$this->db->ping();
$sql = "CREATE TABLE IF NOT EXISTS BanData (name VARCHAR(16) NOT NULL,host VARCHAR(50) NOT NULL,cid VARCHAR(50) NOT NULL,reason VARCHAR(30) NOT NULL,sender VARCHAR(20) NOT NULL,PRIMARY KEY (name))";
$this->db->query($sql);
}
    }
    
    public function onDisable(){
        $this->db->close();
        }
    
    
public function onJoin(PlayerJoinEvent $event){
$player = $event->getPlayer();
$user = $player->getName();
$cid = $player->loginData["clientId"];
$ip = $player->getAddress();
$value = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
if($value){
$host = gethostbyaddr($ip);
}else{
$host = "プライベートIP(".$ip.")";
}
$this->playerlist->set(strtolower($user),array("IP"=>$ip,"CID"=>$cid,"ホスト"=>$host));
$this->playerlist->save();
}
public function playerJoin(PlayerPreLoginEvent $event){
	$player = $event->getPlayer();
	$user = strtolower($player->getName());
$ip = $player->getAddress();
$cid = $player->loginData["clientId"];
$host = gethostbyaddr($ip);
$sql = "SELECT `name`,`host`,`cid`,`reason`,`sender` FROM BanData WHERE name='".$user."' OR host='".$host."' OR cid='".$cid."'";
$result =  $this->db->query($sql);
while($row = $result->fetch_assoc()){
$namedata = $row['name'];
$hostdata = $row['host'];
$ciddata = $row['cid'];
$reasondata = $row['reason'];
}
if(isset($namedata)){
if($namedata == $name){
$event->setKickMessage("BANされています(§e名前§f) §d".$name."\n§a理由:".$reasondata."");
$event->setCancelled();
}elseif($hostdata == $host){
$event->setKickMessage("§aBANされています§f(§eホスト[IP]§f)§d ".$host."\n§a理由:".$reasondata."");
$event->setCancelled();
}elseif($ciddata == $cid){
$event->setKickMessage("§aBANされています§f(§eクライアントID§f)§d ".$cid."\n§a理由:".$reasondata."");
$event->setCancelled();
}
}
}

public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
	switch (strtolower($command->getName())) {
		case "pban":
				if(count($args) < 2){
				$sender->sendMessage("使用方法:/pban <名前> <理由>");
				return false;
				}
				$player = $this->getServer()->getPlayer($args[0]);
				if($player instanceOf Player){
				$player = $player->getPlayer();
				$user = $player->getName();
				$cid = $player->loginData["clientId"];
				$ip = $player->getAddress();
				$reason = "";
				$i = 1;
				foreach($args as $args1){
				if($i > 1){
				$reason = $reason." ".$args1;
				}
				$i++;
				}
				$value = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
				if($value){
				$host = gethostbyaddr($ip);
				}else{
				$host = "プライベートIP(".$ip.")";
				}
				
				$sql = "INSERT INTO `".$this->dbname."`.`BanData` (`name`,`host`,`cid`,`reason`,`sender` )VALUES ('".$user."','".$host."','".$cid."','".$reason."','".$sender->getName()."')";
                $this->db->query($sql);
				$this->getServer()->broadcastMessage("§e[PBANXSYSTEM]".$sender->getName()."が".$user."をPBANXしました 理由:".$reason);
				$player->kick("§cあなたはXBANされました\n理由:".$reason."\nBANした人の名前:".$sender->getName(),false);
				}else{
				if($this->playerlist->exists(strtolower($args[0]))){
				$reason = "";
				$i = 1;
				foreach($args as $args1){
				if($i > 1){
				$reason = $reason." ".$args1;
				}
				$i++;
				}
				$sql = "INSERT INTO `".$this->dbname."`.`BanData` (`name`,`host`,`cid`,`reason`,`sender` )VALUES ('".strtolower($args[0])."','".$this->playerlist->getAll()[strtolower($args[0])]["ホスト"]."','".$this->playerlist->getAll()[strtolower($args[0])]["CID"]."','".$reason."','".$sender->getName()."')";
                $this->db->query($sql);
				$this->getServer()->broadcastMessage("§e[PBANXSYSTEM]".$sender->getName()."が".$args[0]."をPBANXしました 理由:".$reason);
				}else{
				$sender->sendMessage($args[0]."はオフラインです");
				}
			}
			break;
		case "unpban":
if(!isset($args[0])){
$sender->sendMessage(">> パラメータの入力");
}else{
$name = strtolower($args[0]);
$sql5 = "SELECT `name`,`host`,`cid`,`reason`,`sender` FROM BanData WHERE name='".$name."'";
$result =  $this->db->query($sql5);
while($row = $result->fetch_assoc()){
$namedata = $row['name'];
}
if(!(isset($namedata))){
$sender->sendMessage(">> ".$name."さんはBanされてませんでした");
}else{
$sql = "DELETE FROM `".$this->dbname."`.`BanData` WHERE `BanData`.`name` = '".$name."'";
$this->db->query($sql);
$sender->sendMessage(">> ".$name."さんのBanを解除しました");
}
}
break;
	}
	return false;
}

public function onDeath(PlayerDeathEvent $event) {
$player = $event->getPlayer();
$itema = Item::get(260, 0, 1);
$itemb = Item::get(322, 0, 1);
if($player->getInventory()->contains($itema)){//特定のアイテムが指定した数以上あるか
$player->getInventory()->removeItem($itema);//アイテムを消去
}else{
return false;
}
if($player->getInventory()->contains($itemb)){//特定のアイテムが指定した数以上あるか
$player->getInventory()->removeItem($itemb);//アイテムを消去
}else{
return false;
}
}
}
