<?php

namespace Hayamajin;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\utils\Config;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

class UBans extends PluginBase implements Listener{

	public function onEnable(){

        date_default_timezone_set("Asia/Tokyo"); //タイムゾーン設定

        $config_version = "0.1"; //変更禁止

        $this->s = Server::getInstance();
		$this->s->getPluginManager()->registerEvents($this, $this);

		$db = $this->getDataFolder() . "UserData.db";

		if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0744, true);

		$this->BP = new Config($this->getDataFolder() . "Banned_Players.yml", Config::YAML);
		$this->WP = new Config($this->getDataFolder() . "Warnned_Players.yml", Config::YAML);
		$this->Setting = new Config($this->getDataFolder() . "Setting.yml", Config::YAML, array(

			"コンフィグバージョン(編集禁止)" => $config_version,
			"参加時にプレイヤーの情報をコンソールに表示するか(true or false)" => "true",
            "UBan時にプレイヤーの情報を表示するか(true or false)" => "false"
            ));

		if (!file_exists($db)){

			$this->db = new \SQLite3($db, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

                }else{

                $this->db = new \SQLite3($db, SQLITE3_OPEN_READWRITE);

                }

                $this->DB("CREATE TABLE IF NOT EXISTS player (name TEXT PRIMARY KEY, ip TEXT, host TEXT, cid TEXT, uuid TEXT, ban_reason TEXT, warn_reason TEXT, ban INT, warn INT, ban_sender TEXT, warn_sender TEXT, ban_time TEXT, warn_time TEXT)");

                if ($this->getSetting("コンフィグバージョン(編集禁止)") < $config_version){
                    $this->getLogger()->warning("コンフィグファイルが古いです。");
                    $this->getLogger()->warning("古いコンフィグ(Setting.yml)を消去して、新しいコンフィグを生成してください");
                }

                }

	public function onDisable(){

		$this->getLogger()->info("§7UBansを終了しています...");
        $this->db->close();
	}


    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $ip = $player->getAddress();
        $host = gethostbyaddr($ip);
        $cid = $player->loginData["clientId"];
        $uuid = $player->getUniqueId()->ToString();

        if ($this->isUBan($name, $ip, $host, $cid, $uuid)){

            $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
            $reason = $data["ban_reason"];

            if (empty($data["ban"])){
                $reason = "UBanされたプレイヤーのサブアカウント";
            }
            $sender_name = "UBans (Plugin)";

            $this->addUBanByText($name, $ip, $host, $cid, $uuid, $reason, $sender_name);
            $this->BP->set($name, $reason);
            $player->kick("§cあなたは接続禁止状態です \n§e理由 §f: §6$reason ", false);
        }


        if ($this->isWarn($name)){
            $this->setDanger($player);

        }

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
        if (empty($data)){
        $ban = 0;
        $warn = 0;
        $this->DB("INSERT OR REPLACE INTO player VALUES(\"$name\", \"$ip\",  \"$host\",  \"$cid\", \"$uuid\", \"\", \"\", \"$ban\", \"$warn\", \"\", \"\", \"\", \"\")");
        }   

        $this->DB("UPDATE player SET name = \"$name\", ip = \"$ip\", host = \"$host\", cid = \"$cid\", uuid = \"$uuid\" WHERE name = \"$name\"");


        if ($this->getSetting("参加時にプレイヤーの情報を表示するか(true or false)") == "true"){
        $ip = $data["ip"];
        $host = $data["host"];
        $cid = $data["cid"];
        $uuid = $data["uuid"];

        $this->getLogger()->info("§a名前         §f: §b$name");
        $this->getLogger()->info("§aIPアドレス    §f: §b$ip");
        $this->getLogger()->info("§aホスト        §f: §b$host");
        $this->getLogger()->info("§aクライアントID §f: §b$cid");
        $this->getLogger()->info("§aユニークID    §f: §b$uuid");

        }
    }

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        $prefix = "§e[UBans]§f";

        if ($command->getName() === "uban"){
            $sender_name = $sender->getName();
            if (empty($args[0])){
                $sender->sendMessage("$prefix §b使い方 : /uban <プレイヤーネーム> <理由>");
                return;
            }
                $name = $args[0];
                $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

                $ip = $data["ip"];
                $host = $data["host"];
                $cid = $data["cid"];
                $uuid = $data["uuid"];

                if (!$sender instanceof Player){
                    $sender_name = "管理者";
                }

                if (empty($data)){
                    $sender->sendMessage("$prefix §b{$name}さんはサーバーに来ていません");
                    return;
                }

                
                if (isset($args[1])){
                    $reason = $args[1];
                } else {
                    $reason = "未記入";
                }

                if ($data["ban"] === 1){
                    $sender->sendMessage("$prefix §b{$name}は既にUBanされています");
                    return;
                }

                $this->AddUBan($name, $reason, $sender_name);

                $player = $this->s->getPlayer($name);

                if ($player Instanceof Player){
                    $player->kick("§cサーバーとの接続が切断されました \n§6理由 §f:§6$reason ", false);
                }
                $this->BCM("§a{$sender_name}§fが§c{$name}§fを§eUBan§fしました\n".
                           "$prefix §e理由 §f:§6 $reason");
                $sender->sendMessage("$prefix §a{$name}§fを§eUBan§fしました");

                if ($this->getSetting("UBan時にプレイヤーの情報を表示するか(true or false)") == "true"){

                    $this->BCM("§aIPアドレス    §f: §b$ip");
                    $this->BCM("§aホスト        §f: §b$host");
                    $this->BCM("§aクライアントID §f: §b$cid");
                    $this->BCM("§aユニークID     §f: §b$uuid");

                }
            }

        if ($command->getName() === "uban_txt"){
            if (!$sender instanceof Player){
                $sender_name = "管理者";
            }

            $sender_name = $sender->getName();
            if (empty($args[0]) or
                empty($args[1]) or
                empty($args[2]) or
                empty($args[3]) or 
                empty($args[4]) or
                empty($args[5])){
                $sender->sendMessage("$prefix §b使い方 : /uban_txt <名前> <IPアドレス> <ホスト> <クライアントID> <ユニークID> <理由>");
                $sender->sendMessage("$prefix §bUBanしたくない情報は「null」と入力してください");
                return;
            } 

            $this->addUBanByText($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $sender_name);
            $sender->sendMessage("$prefix §c{$args[0]}§aをテキストでUBanしました");
            $sender->sendMessage(" §6IPアドレス §f: §b$args[1]");
            $sender->sendMessage(" §6ホスト §f: §b $args[2]");
            $sender->sendMessage(" §6クライアントID §f: §b $args[3]");
            $sender->sendMessage(" §6ユニークID §f : §b$args[4]");
            $sender->sendMessage(" §6理由 §f: §b $args[5]");




        }


    if ($command->getName() === "warn"){
        $sender_name = $sender->getName();
        if (empty($args[0])){
                $sender->sendMessage("$prefix §b使い方 : /warn <プレイヤーネーム> <理由>");
                return;
            }

            $name = $args[0];
            $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

                if (!$sender instanceof Player){
                    $sender_name = "管理者";
                }
                if (empty($data)){
                    $sender->sendMessage("{$prefix} §b{$name}さんはサーバーに来ていません");
                    return;
                }
                if (isset($args[1])){
                    $reason = $args[1];
                } else {
                    $reason = "未記入";
                }
                if ($data["warn"] === 1){
                    $sender->sendMessage("$prefix §b{$name}は既にWarnされています");
                    return;
                }

                $this->AddWarn($name, $reason, $sender_name);

                $player = $this->s->getPlayer($name);

                if ($player instanceof Player){
                    $this->setDanger($player);
                    $player->sendMessage("$prefix §cあなたは管理者から警告を受けました\n$prefix §c理由 §f:§6$reason");
                }
                $this->BCM("§a{$sender_name}§fが§c{$name}§fを§eWarn§fしました\n".
                           "$prefix §e理由 §f:§6 $reason");
                $sender->sendMessage("$prefix §c{$name}§fを§eWarn§fしました");
            }

    if ($command->getName() === "unuban"){
        if (empty($args[0])){
                $sender->sendMessage("$prefix §b使い方 : /unuban <プレイヤーネーム>");
                return;
            }
        $name = $args[0];
        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if (empty($data)){
            $sender->sendMessage("{$name}さんはサーバーに来ていません");
            return;
        }
        if ($data["ban"] === 0){
                    $sender->sendMessage("$prefix §b{$name}はUBanされていません");
                    return;
        }
        $this->unUBan($name);
        $sender->sendMessage("$prefix §a{$name}§fの§eUBan§fを§b解除§fしました");
    }


    if ($command->getName() === "unwarn"){
        if (empty($args[0])){
                $sender->sendMessage("$prefix §b使い方 : /unwarn <プレイヤーネーム>");
                return;
            }
        $name = $args[0];
        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if (empty($data)){
            $sender->sendMessage("{$name}さんはサーバーに来ていません");
            return;
        }
        if ($data["warn"] === 0){
                    $sender->sendMessage("$prefix §b{$name}はWarnされていません");
                    return;
        }
        $this->unWarn($name);
        $sender->sendMessage("$prefix §a{$name}§fの§eWarn§fを§b解除§fしました");

    }
    if ($command->getName() === "ubans"){
        if (empty($args[0])){
        $sender->sendMessage("§b/ubans about   §f: §6UBansがどのようなプラグインなのかを確認出来ます\n".
                            "§b/ubans bans    §f: §6UBanされているプレイヤーのリストを表示します\n".
                            "§b/ubans warns    §f: §6Warnされているプレイヤーのリストを表示します\n".
                            "§b/ubans bi  §f: §6UBanされているプレイヤーの細かな情報を表示します\n".
                            "§b/ubans wi §f: §6Warnされているプレイヤーの細かな情報を表示します");
        
                            if (!$sender instanceof Player){
                                $sender->sendMessage("§b/ubans reload §f: §6コンフィグファイルをリロードします");
                            }
            } else {
                switch ($args[0]){
                    case "about":
                    case "a":
                    $sender->sendMessage("§eUBans§fとは、§9Hayamajin§fがまったりのんびり開発している\n".
                                         "§b荒らし対策プラグイン§fです。\n".
                                         "今現在では、\n".
                                         "・§eUBan(名前、IPアドレス、ホスト、クライアントID(ユニークID)の\n".
                                         "  §e同時Banが出来る機能)\n".
                                         "・§eWarn(危険プレイヤーを把握出来るように＆\n".
                                         "§e  危険プレイヤーのブロックの設置/破壊を制限\n".
                                         "という機能が実装されています。");
                    break;
                    case "bans":
                    case "b":
                    case "ubans":
                    case "ban":
                    $sender->sendMessage("§b--- §eこのサーバーでUBanされたプレイヤー一覧§b---");
                    foreach ($this->BP->getAll() as $a => $bp){
                        
                        $this->uban[$a] = $a;
                        
                    }
                    
                    $players = implode(", ", $this->uban);
                    
                    $sender->sendMessage("§a$players\n".
                                         "§b/ubans bi 名前 で詳しい情報を取得出来ます\n".
                                         "§b--- §eこのサーバーでUBanされたプレイヤー一覧§b---");
                    break;

                    case "warns":
                    case "w":
                    case "warn":

                    $sender->sendMessage("§b--- §eこのサーバーでWarnされたプレイヤー一覧§b---");
                    foreach ($this->WP->getAll() as $a => $wp){
                        
                        $this->warn[$a] = $a;
                        
                    }
                    $players = implode(", ", $this->warn);
                    
                    $sender->sendMessage("§a$players\n".
                                         "§b/ubans wi 名前 で詳しい情報を取得出来ます\n".
                                         "§b--- §eこのサーバーでWarnされたプレイヤー一覧§b---");

                    break;



                    case "baninfo":
                    case "bi":
                    if (empty($args[1])){
                        $sender->sendMessage("$prefix §b使い方 : /ubans baninfo <プレイヤーネーム>");
                        return;
                    }
                        $name = $args[1];
                        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
                        if ($data["ban"] === 0 or empty($data)){
                            $sender->sendMessage("$prefix §b{$name}さんはUBanされていません");
                            return;
                        }

                        $reason = $data["ban_reason"];
                        $time = $data["ban_time"];
                        $ban = $data["ban"];
                        $sender_name = $data["ban_sender"];

                        $sender->sendMessage("§b------ §6$name §b-----\n".
                                             "§a[タイプ] §eUBan\n".
                                             "§a[理由]  §e$reason\n".
                                             "§a[時間]  §e$time\n".
                                             "§a[実行者] §e$sender_name\n".
                                             "§b------ §6$name §b-----");
                        break;

                        case "warninfo":
                        case "wi":

                        if (empty($args[1])){
                        $sender->sendMessage("$prefix §b使い方 : /ubans warninfo <プレイヤーネーム>");
                        return;
                        }
                        $name = $args[1];
                        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
                        if ($data["warn"] === 0 or empty($data)){
                            $sender->sendMessage("$prefix §b{$name}さんはWarnされていません");
                            return;
                        }

                        $reason = $data["warn_reason"];
                        $time = $data["warn_time"];
                        $sender_name = $data["warn_sender"];

                        $sender->sendMessage("§b------ §6$name §b-----\n".
                                             "§a[タイプ] §eWarn\n".
                                             "§a[理由]  §e$reason\n".
                                             "§a[時間]  §e$time\n".
                                             "§a[実行者] §e$sender_name\n".
                                             "§b------ §6$name §b-----");
                        break;

                        case "reload":
                        if ($sender instanceof Player){
                            $sender->sendMessage("$prefix §cコンソール限定のコマンドです");
                         } else {

                        $this->BP->reload();
                        $this->WP->reload();
                        $this->Setting->reload();
                        $sender->sendMessage("$prefix コンフィグファイルをリロードしました");

                        }
                        break;

                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if ($data["warn"] === 1){
            $player->sendTip("§c⚠あなたはWarnされています⚠");
            $event->setCancelled();
        }
    }

    public function onPleace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if ($data["warn"] === 1){
            $player->sendTip("§c⚠あなたはWarnされています⚠");
            $event->setCancelled();
        }
    }



    function getSetting($setting){

        if (!$this->Setting->exists($setting)) return false;

    	$setting = $this->Setting->get($setting);

    	return $setting;
    }


    public function addUBan($name, $reason, $sender_name){
        
        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if (empty($data)) 
            return false;

            $time = $this->getTime();
            $this->DB("UPDATE player SET ban = \"1\", ban_sender = \"$sender_name\", ban_reason = \"$reason\", ban_time = \"$time\" WHERE name = \"$name\"");
            $this->BP->set($name, $reason);
            $this->BP->save();

            $this->BP->reload();

            return true;

    }
    public function unUBan($name){

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
        if (empty($data))
        return false;
        $this->DB("UPDATE player SET ban = \"0\", ban_sender = \"\", ban_reason = \"\", ban_time = \"\" WHERE name = \"$name\"");

        $this->BP->remove($name);
        $this->BP->save();

        $this->BP->reload();

        return true;

    }

    public function AddWarn($name, $reason, $sender_name){

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);

        if (empty($data)) 
            return false;
    
            $time = $this->getTime();
            $this->DB("UPDATE player SET warn = \"1\", warn_sender = \"$sender_name\", warn_reason = \"$reason\", warn_time = \"$time\" WHERE name = \"$name\"");
            $this->WP->set($name, $reason);
            $this->WP->save();

            $this->WP->reload();

            return true;
    }

    public function unWarn($name){

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
        if (empty($data))
            return false;

        $this->DB("UPDATE player SET warn = \"0\", warn_sender = \"\", warn_reason = \"\", warn_time = \"\" WHERE name = \"$name\"");

        $this->WP->remove($name);
        $this->WP->save();

        $this->WP->reload();

        return true;
    }

    public function setDanger(Player $player){
        $nt = $player->getNameTag();
        $dn = $player->getDisplayName();

        $player->setNameTag("§c⚠§f $nt");
        $player->setDisplayName("§c⚠§f $dn");

        return true;
    }

    public function isUBan($name, $ip, $host, $cid, $uuid){


        $result = $this->DB("SELECT * FROM player WHERE
            ban = \"1\" AND name = \"$name\" OR
            ban = \"1\" AND ip = \"$ip\" OR
            ban = \"1\" AND host = \"$host\" OR
            ban = \"1\" AND cid = \"$cid\" OR
            ban = \"1\" AND uuid = \"$uuid\"
            ", true);

        if(!empty($result)){
            return true;
        }
            return false;
        }

    public function isWarn($name){

        $data = $this->DB("SELECT * FROM player WHERE name = \"$name\"", true);
        $warn = $data["warn"];
        if ($warn === 1){
            return true;
        }
            return false;
    }


    public function Delete($name){
        $data = $this->DB("SELECT FROM player WHERE name = \"$name\"", true);
        if(empty($data)) 
        return false;

        $this->DB("DELETE FROM player WHERE name = \"$name\"", true);
        return true;
    }

    public function isRegister($account){

        $account = $this->DB("DELETE FROM player WHERE name = \"$account\"", true);

        if(empty($account))
        return true;

        return false;

    }

    public function addUBanByText($name, $ip, $host, $cid, $uuid, $reason, $sender_name){
        $ban = 1;
        $time = $this->getTime();
        $this->DB("INSERT OR REPLACE INTO player VALUES(\"$name\", \"$ip\",  \"$host\",  \"$cid\", \"$uuid\", \"$reason\", \"\", \"$ban\", \"\", \"$sender_name\", \"\", \"$time\", \"\")");
        $this->BP->set($name, $reason);
        //$this->DB("CREATE TABLE IF NOT EXISTS player (name TEXT PRIMARY KEY, ip TEXT, host TEXT, cid TEXT, uuid TEXT, ban_reason TEXT, warn_reason TEXT, ban INT, warn INT, ban_sender TEXT, warn_sender TEXT, ban_time TEXT, warn_time TEXT)");

    }


    function DevFunction($name, $ip, $host, $cid, $uuid){
        $this->DB("INSERT OR REPLACE INTO player VALUES(\"$name\", \"$ip\",  \"$host\",  \"$cid\", \"$uuid\", \"\", \"\", \"\", \"\", \"\", \"\", \"\", \"\")");

    }

    function getTime(){
        $time = date("Y年m月d日H時i分s秒");

        return $time;
    }
    function BCM($msg){
        $prefix = "§e[UBans]§f";
        $this->s->BroadcastMessage("$prefix $msg");

        return true;
    }

    function DB($sql, $return = false)
            {
                if($return){
                    return $this->db->query($sql)->fetchArray();
                }else{
                    $this->db->query($sql);
                    return true;
                }
            }


    /*function addSetting($main, $sub){

        $this->Setting->set($main, $sub);
        $this->Setting->save();
    }*/

}

