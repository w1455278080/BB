<?php

namespace bb;

class RefreshSigns {
    
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
                $this->prefix = $this->plugin->prefix;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
					$aop = 0;
                                        $namemap = str_replace("§f", "", $text[2]);
					foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$namemap){$aop=$aop+1;}}
					$ingame = TE::AQUA . "§7[ §fJoin §7]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($namemap . "PlayTime")!=470)
					{
						$ingame = TE::DARK_PURPLE . "§7[ §cRunning §7]";
					}
					elseif($aop>=16)
					{
						$ingame = TE::GOLD . "§7[ §9Full §7]";
					}
                                        $t->setText($ingame,TE::YELLOW  . $aop . " / 16",$text[2],$this->prefix);
				}
			}
		}
	}
}
