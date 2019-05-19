<?php

namespace bb;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use bb\ResetMap;
use pocketmine\level\sound\PopSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\item\Item;
use pocketmine\event\entity\EntityLevelChangeEvent;

class BB extends PluginBase implements Listener {

        public $prefix = TE::GRAY . "[ " . TE::GREEN. TE::BOLD . "Build" . TE::LIGHT_PURPLE."Battle". TE::RESET . TE::GRAY . " ]";
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
        public $op = array();
	
	public function onEnable()
	{
		$this->getLogger()->notice(TE::AQUA . "BuildBattle by myval2");
                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
		@mkdir($this->getDataFolder());
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
		foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
                $temas = array("Parrot - Bird","Pez - Fish","Auto - Car","Dragon","Internet","tree - Frog","Mario Bros","Cat - Dog","Mago - Mage","Tractor","Kitchen - Boat","Cascada - WaterFall","Tigre - Tiger","Avión - AirPlane","Superhero","Forest - Soccer","MCPE Mobs-Monsters");
		if($config->get("temas")==null)
		{
			$config->set("temas",$temas);
		}
		$config->save();
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                $slots->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
	}
        
        public function onDisable() {
            $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
            $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
            if($config->get("arenas")!=null)
            {
                    $this->arenas = $config->get("arenas");
            }
            foreach($this->arenas as $arena)
            {
                    for ($i = 1; $i <= 16; $i++) {
                        $slots->set("slot".$i.$arena, 0);
                    }
                    $slots->save();
                    $config->set($arena . "inicio", 0);
                    $config->save();
                    $points = new Config($this->getDataFolder() . "/puntos".$arena.".yml", Config::YAML);
                    foreach($points->getAll() as $key => $w){
                        $points->set($key, 0);
                    }
                    $points->save();
                    $this->reload($arena);
            }
        }
        
        public function reload($lev)
        {
                if ($this->getServer()->isLevelLoaded($lev))
                {
                        $this->getServer()->unloadLevel($this->getServer()->getLevelByName($lev));
                }
                $zip = new \ZipArchive;
                $zip->open($this->getDataFolder() . 'arenas/' . $lev . '.zip');
                $zip->extractTo($this->getServer()->getDataPath() . 'worlds');
                $zip->close();
                unset($zip);
                return true;
        }
	
        public function enCambioMundo(EntityLevelChangeEvent $event)
        {
            $pl = $event->getEntity();
            if($pl instanceof Player)
            {
                $lev = $event->getOrigin();
                $level = $lev->getFolderName();
                if($lev instanceof Level && in_array($level,$this->arenas))
		{
                $pl->removeAllEffects();
                $pl->getInventory()->clearAll();
                $pl->setGamemode(0);
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                $limit = new Config($this->getDataFolder() . "/limit.yml", Config::YAML);
                $limit->set($pl->getName(), 0);
                $limit->save();
                for ($i = 1; $i <= 16; $i++) {
                    if($slots->get("slot".$i.$level)==$pl->getName())
                    {
                        $slots->set("slot".$i.$level, 0);
                    }
                }
                $slots->save();
                }
            }
        }
        
        public function enDrop(PlayerDropItemEvent $ev) {
            $player = $ev->getPlayer();
            if(in_array($player->getLevel()->getFolderName(),$this->arenas))
            {
                $ev->setCancelled();
            }
        }
        
        public function eninv(EntityInventoryChangeEvent $ev) {
            $level = $ev->getEntity()->getLevel()->getFolderName();
            if(in_array($level,$this->arenas))
            {
                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                if($config->get($level . "PlayTime") >= 470)
                {
                    $ev->setCancelled();
                }
                elseif($config->get($level."PlayTime")<170)
                {
                    $ev->setCancelled();
                }
            }
        }

        public function onLog(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
                $player->setGamemode(0);
                if(in_array($player->getLevel()->getFolderName(),$this->arenas))
		{
                    $player->getInventory()->clearAll();
                    $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                    $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                    $player->teleport($spawn,0,0);
                }
	}
        
        public function onQuit(PlayerQuitEvent $event)
        {
            $pl = $event->getPlayer();
            $level = $pl->getLevel()->getFolderName();
            if(in_array($level,$this->arenas))
            {
                $pl->removeAllEffects();
                $pl->getInventory()->clearAll();
                $pl->setGamemode(0);
                $pl->setNameTag($pl->getName());
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                for ($i = 1; $i <= 16; $i++) {
                    if($slots->get("slot".$i.$level)==$pl->getName())
                    {
                        $slots->set("slot".$i.$level, 0);
                    }
                }
                $slots->save();
            }
        }
        
        public function Puntuar(PlayerItemHeldEvent $event) {
            $player = $event->getPlayer();
            $level = $player->getLevel()->getFolderName();
            if(in_array($level,$this->arenas))
            {
                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                $time = $config->get($level . "PlayTime");
                if($time<=170)
                {
                    if($config->get("actual".$level)!=$player->getName())
                    {
                        if($event->getItem()->getDamage()==14)
                        {
                            $player->sendTip(TE::BOLD.TE::DARK_RED."SUPER POOP");
                        }
                        elseif($event->getItem()->getDamage()==6)
                        {
                            $player->sendTip(TE::BOLD.TE::RED."POOP");
                        }
                        elseif($event->getItem()->getDamage()==5)
                        {
                            $player->sendTip(TE::BOLD.TE::GREEN."OK");
                        }
                        elseif($event->getItem()->getDamage()==13)
                        {
                            $player->sendTip(TE::BOLD.TE::DARK_GREEN."GOOD");
                        }
                        elseif($event->getItem()->getDamage()==11)
                        {
                            $player->sendTip(TE::BOLD.TE::DARK_PURPLE."EPIC");
                        }
                        elseif($event->getItem()->getDamage()==4)
                        {
                            $player->sendTip(TE::BOLD.TE::GOLD."LEGENDARY");
                        }
                    }
                    else
                    {
                        $player->sendTip(TE::BOLD.TE::RED."You cant vote your own Plot");
                    }
                }
            }
        }
        
        public function getPoints($damage){
            if($damage == 14){
                return 1;
            }
            if($damage == 6){
                return 2;
            }
            if($damage == 5){
                return 3;
            }
            if($damage == 13){
                return 4;
            }
            if($damage == 11){
                return 5;
            }
            if($damage == 4){
                return 6;
            }
            return 1;
        }
        
        public function getConfirm($damage){
            if($damage == 14){
                return TE::DARK_RED."SUPER POOP";
            }
            if($damage == 6){
                return TE::RED."POOP";
            }
            if($damage == 5){
                return TE::GREEN."OK";
            }
            if($damage == 13){
                return TE::DARK_GREEN."GOOD";
            }
            if($damage == 11){
                return TE::DARK_PURPLE."EPIC";
            }
            if($damage == 4){
                return TE::GOLD."LEGENDARY";
            }
            return TE::DARK_RED."SUPER POOP";
        }
        
        public function onMov(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
                    $limit = new Config($this->getDataFolder() . "/limit.yml", Config::YAML);
                    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                    if($config->get($level . "PlayTime") < 470 && $config->get($level . "PlayTime") > 170)
                    {
                        if($limit->get($player->getName()) != null)
                        {
                            $pos = $limit->get($player->getName());
                            if($player->x>$pos[0]+13.5 || $player->x<$pos[0]-13.5 || $player->y>$pos[1]+20 || $player->y<$pos[1]-1 || $player->z>$pos[2]+13.5 || $player->z<$pos[2]-13.5)
                            {
                                $event->setCancelled();
                            }
                        }
                    }
		}
	}
	
	public function onBlockBr(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
                $block = $event->getBlock();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
                    $limit = new Config($this->getDataFolder() . "/limit.yml", Config::YAML);
                    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                    if($config->get($level . "PlayTime") != null)
                    {
                            if($config->get($level . "PlayTime") >= 470)
                            {
                                    $event->setCancelled();
                            }
                    }
                    if($limit->get($player->getName()) != null)
                    {
                        $pos = $limit->get($player->getName());
                        if($block->getX()>$pos[0]+13.5 || $block->getX()<$pos[0]-13.5 || $block->getY()>$pos[1]+20 || $block->getY()<$pos[1]-1 || $block->getZ()>$pos[2]+13.5 || $block->getZ()<$pos[2]-13.5)
                        {
                            $event->setCancelled();
                        }
                    }
		}
	}
        
        public function onBlockPla(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
                $block = $event->getBlock();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
                    $limit = new Config($this->getDataFolder() . "/limit.yml", Config::YAML);
                    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                    if($config->get($level . "PlayTime") != null)
                    {
                            if($config->get($level . "PlayTime") >= 470)
                            {
                                    $event->setCancelled();
                            }
                    }
                    if($limit->get($player->getName()) != null)
                    {
                        $pos = $limit->get($player->getName());
                        if($block->getX()>$pos[0]+13.5 || $block->getX()<$pos[0]-13.5 || $block->getY()>$pos[1]+20 || $block->getY()<$pos[1]-1 || $block->getZ()>$pos[2]+13.5 || $block->getZ()<$pos[2]-13.5)
                        {
                            $event->setCancelled();
                        }
                    }
		}
	}
	
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) {
        switch($cmd->getName()){
			case "bb":
                            if($player->isOp())
                            {
                                if(!empty($args[0]))
				{
                                    if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[0]))
                                    {
                                            $this->getServer()->loadLevel($args[0]);
                                            $this->getServer()->getLevelByName($args[0])->loadChunk($this->getServer()->getLevelByName($args[0])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[0])->getSafeSpawn()->getFloorZ());
                                            array_push($this->arenas,$args[0]);
                                            $this->currentLevel = $args[0];
                                            $this->mode = 1;
                                            $player->sendMessage($this->prefix . "Registra los plot!");
                                            $player->setGamemode(1);
                                            array_push($this->op, $player->getName());
                                            $player->teleport($this->getServer()->getLevelByName($args[0])->getSafeSpawn(),0,0);
                                            $name = $args[0];
                                            $this->zipper($player, $name);
                                    }
                                    else
                                    {
                                            $player->sendMessage($this->prefix . "ERROR missing world.");
                                    }
                                }
                            }
			return true;
	}
        }
        
        public function PlayerInteractEvent(PlayerInteractEvent $ev){
            $item = $ev->getItem();
            if($item->getId() === Item::SPAWN_EGG){
                $ev->setCancelled();
            }
        }
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if(($this->mode==26)&&(in_array($player->getName(), $this->op)))
			{
				$tile->setText(TE::AQUA . "§7[ §fJoin §7]",TE::GREEN  . "§e0 / 16","§f" . $this->currentLevel,$this->prefix);
				$this->refreshArenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage($this->prefix . "Arena Registered!");
                                array_shift($this->op);
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0]==TE::AQUA . "§7[ §fJoin §7]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                                                $namemap = str_replace("§f", "", $text[2]);
                                                $level = $this->getServer()->getLevelByName($namemap);
                                                for ($i = 1; $i <= 16; $i++) {
                                                    if($slots->get("slot".$i.$namemap)==null)
                                                    {
                                                            $thespawn = $config->get($namemap . "Spawn17");
                                                            $slots->set("slot".$i.$namemap, $player->getName());
                                                            goto with;
                                                    }
                                                }
                                                $player->sendMessage($this->prefix."No Slots");
                                                goto sinslots;
                                                with:
                                                $slots->save();
                                                $player->sendMessage($this->prefix . "You Entered BuildBattle");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($player->getNameTag() .TE::AQUA. " joined the game");
                                                }
                                                $spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->getInventory()->clearAll();
                                                $player->removeAllEffects();
                                                $player->setMaxHealth(20);
                                                $player->setHealth(20);
                                                $player->setFood(20);
                                                $player->setGamemode(1);
                                                sinslots:
					}
					else
					{
						$player->sendMessage($this->prefix . "You cant join!");
					}
				}
			}
		}
		elseif(in_array($player->getName(), $this->op)&& $this->mode>=1 && $this->mode<=16)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . "Spawn ".$this->mode." Registered!");
			$this->mode++;
			$config->save();
		}
		elseif(in_array($player->getName(), $this->op)&&$this->mode==17)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . "Spawn Lobby Registered!");
			$config->set("arenas",$this->arenas);
                        $config->set($this->currentLevel . "Start", 0);
			$player->sendMessage($this->prefix . "Touch a spawn to registered Arena!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
			$config->save();
			$this->mode=26;
		}
	}
	
	public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 470);
			$config->set($arena . "StartTime", 30);
		}
		$config->save();
	}
        
        public function zipper($player, $name)
        {
        $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $datos) {
					if (!$datos->isDir()) {
						$relativePath = $name . '/' . substr($datos, strlen($path) + 1);
						$zip->addFile($datos, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
}