<?php
namespace dev;

#Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;

#Commands
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

#Scheduler
use pocketmine\scheduler\PluginTask;

#Utils
use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Config;

#etc
use dev\Main;

class Main extends PluginBase implements Listener{
  const NAME = 'DebugScreen',
        VERSION = 'v1.0';

  private $dev = [];
  private static $instance = null;

  public static function getInstance(){
    return self::$instance;
  }

  public function onEnable(){
    self::$instance = $this;
    $task = new updateTask($this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getLogger()->info(Color::GREEN.self::NAME." ".self::VERSION." が読み込まれました。");
  }

  public function onCommand(CommandSender $s, Command $command, $label, array $args){
    if (strtolower($label) === "devs") {
      $n = strtolower($s->getName());
      if (isset($this->dev[$n])) {
        if ($this->dev[strtolower($n)] === true) {
          $this->dev[strtolower($n)] = false;
        }else {
          $this->dev[strtolower($n)] = true;
        }
      }else {
        $this->dev[strtolower($n)] = true;
      }
    }
    return true;
  }

  public function onUpdate(){
    $pls = $this->getServer()->getOnlinePlayers();
    if (count($pls) !== 0) {
      foreach ($pls as $pl) {
        $n = strtolower($pl->getName());
        if (isset($this->dev[$n]) && $this->dev[$n] === true) {
          $pl->sendPopUp("for Minecraft:PE {$this->getServer()->getVersion()}\nUptime: {$this->getServer()->getUpTime()}\nTPS: {$this->getServer()->getTicksPerSecond()}\nXYZ: ".round($pl->x)." / ".round($pl->y)." / ".round($pl->z)."\nIP: {$pl->getAddress()}\nPort: {$pl->getPort()}\nProtocol: {$pl->getProtocol()}\nLoaderID: {$pl->getLoaderID()}                                                                                                                  \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
        }
      }
    }
  }

  public function onDisable(){
    $this->getLogger()->info(Color::RED.self::NAME." が無効化されました。");
  }
}

class updateTask extends PluginTask{
  public function __construct(PluginBase $owner) {
    parent::__construct($owner);

  }

  public function onRun($tick){
    Main::getInstance()->onUpdate();
  }
}
