<?php
namespace bingbing;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\level\particle\LavaParticle;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\entity\Entity;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\entity\Zombie;
use pocketmine\nbt\NBT;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityMotionEvent;

class bingRPG extends PluginBase implements Listener{
    public function onEnable(){
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->playerDB = new Config($this->getDataFolder()."playerdata.yml", Config::YAML);
        $this->player = $this->playerDB->getAll();
        
        $this->mdb = new Config($this->getDataFolder()."message.yml",Config::YAML,["m" => "§c[§fRPG§c]§f"]);
        $this->m = $this->mdb->getAll();
        
        $this->cooldb = new Config($this->getDataFolder()."cool.yml",Config::YAML);
        $this->cool = $this->cooldb->getAll();
        
        $this->rankdb = new Config($this->getDataFolder()."rank.yml",Config::YAML);
        $this->rank = $this->rankdb->getAll();
    }
    
    public function join(PlayerJoinEvent$event){
        $name = $event->getPlayer()->getName();
        if (!isset($this->player[$name])){
            $this->player[$name] = [];
            $this->player[$name]["type"] = $this->type();
            $this->player[$name]["job"] = "백수";
            $this->player[$name]["level"] = 1;
            $this->player[$name]["stat"] =0;
            $this->player[$name]["exp"] = 0;
            $this->player[$name]["speed"] =0;
            $this->player[$name]["health"] = 0;
            $this->player[$name]["quest"] = 0;
            
            $this->player[$name]["typelevel"] = 1;
            $event->getPlayer()->sendMessage($this->m["m"]."당신은 ".$this->player[$name]["type"]."의 신에게 선택 받았습니다. 신의 가호가 함께합니다.");
            
            $this->cool[$name] = [];
            $this->cool[$name]["g"] = 0;
            $this->cool[$name]["lg"] = 0;
            $this->cool[$name]["gg"] = 0;
            $this->cool[$name]["lgg"] = 0;
            
            
        }
        $this->player[$name]["quest"] = 0;
        
        $event->getPlayer()->setMaxHealth(20 + $this->player[$name]["health"]*0.25);
        $event->getPlayer()->setHealth(20 + $this->player[$name]["health"]*0.25);
        if (!isset($this->rank[0])){
           
        }
        
    }
    public function respawn(PlayerRespawnEvent$event){
        $name = $event->getPlayer()->getName();
        
        if ($this->player[$name]["job"] !== "백수"){
            $event->getPlayer()->setMaxHealth(20 + $this->player[$name]["health"]*0.25);
            $event->getPlayer()->setHealth(20 + $this->player[$name]["health"]*0.25);
        }
    }
    
    public function death(EntityDeathEvent$event){
        if ($event instanceof EntityDamageByEntityEvent){
            $en = $event->getEntity();
            $ki = $en->getLastDamageCause()->getEntity();
            if ($ki instanceof Player){
                if ($en instanceof Player){
                    $name = $ki->getName();
                    
                    
                    $ki->sendMessage($this->m["m"] ." ".$en." 님을  죽이고 20 경험치를 얻으셨습니다");
                    
                    $this->player[$name]["exp"] = $this->player[$name]["exp"] + 20;
                    $ki->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                    if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                        $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                        $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                        $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                        $this->player[$name]["exp"] =0;
                        $ki->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                        
                    }
                }
                if ($en instanceof Zombie){
                    $name = $ki->getName();
                    
                    
                    $ki->sendMessage($this->m["m"] ." BOSS를 죽이고  2000 경험치를 얻으셨습니다");
                    
                    $this->player[$name]["exp"] = $this->player[$name]["exp"] + 2000;
                    $ki->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                    if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                        $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                        $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                        $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                        $this->player[$name]["exp"] =0;
                        $ki->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                        
                    }
                    if ($en->getMaxHealth() > 600){
                        $amount = mt_rand(600 ,2000);
                        $en->setMaxHealth($amount);
                        $en->setHealth($amount);
                        
                    }
                } 
            }
        }
    }
    public function break(BlockBreakEvent$event){
        $name = $event->getPlayer()->getName();
        
        
        if ($event->getBlock()->getId() == "38" and $event->getPlayer()->getLevel()->getName() == "wild"){
            $event->getPlayer()->sendMessage($this->m["m"] ." "."꽃을 꺽고  5 경험치를 얻으셨습니다");
            
            $this->player[$name]["exp"] = $this->player[$name]["exp"]+ 5;
            $event->getPlayer()->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
            if ($this->player[$name]["exp"] >=$this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                $this->player[$name]["exp"] =0;
                $event->getPlayer()->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                
            }
        }
    }
    public function place(BlockPlaceEvent$event){
        if ($event->getBlock()->getId() == "38"){
            $event->setCancelled();
        }
        
        
        
    }
   
   
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        
        $name = $sender->getName();
        
        if ($command->getName() == "전직"){
            switch ($args[0]){
                case "궁수":
                    $this->player[$name]["job"] = "궁수";
                    $sender->sendMessage($this->m["m"] ." 궁수로 전직하셨습니다. /내정보 로 확인가능합니다");
                    break;
                case "기사":
                    
                    $sender->sendMessage($this->m["m"] ." 기사로 전직하셨습니다. /내정보 로 확인가능합니다");
                    $this->player[$name]["job"] = "기사";
                    break;
                case "마법사":
                    $this->player[$name]["job"] = "마법사";
                    $sender->sendMessage($this->m["m"] ." 마법사로 전직하셨습니다. /내정보 로 확인가능합니다");
                    break;
                case "도적":
                    $this->player[$name]["job"] = "도적";
                    $sender->sendMessage($this->m["m"] ." 도적으로 전직하셨습니다. /내정보 로 확인가능합니다");
                    break;
                    
            }
        }
        if ($command->getName() == "내정보"){
            
            $sender->sendMessage($this->m["m"] ." 직업: ".$this->player[$name]["job"]);
            
            $sender->sendMessage($this->m["m"] ." 레벨: ".$this->player[$name]["level"]);
            $sender->sendMessage($this->m["m"] ." 경험치: ".$this->player[$name]["exp"]/ $this->player[$name]["level"]*125);
            $sender->sendMessage($this->m["m"] ." 속성: ".$this->player[$name]["type"]);
            $sender->sendMessage($this->m["m"] ." 속성레벨: ".$this->player[$name]["typelevel"]);
            $sender->sendMessage($this->m["m"] ." 체력스텟: ". $this->player[$name]["health"]);
            $sender->sendMessage($this->m["m"] ." 이동속도 스텟: ". $this->player[$name]["speed"] );
            $sender->sendMessage($this->m["m"] ." 남은 스텟: ".$this->player[$name]["stat"]);
            
        }
        /*if ($command->getName() == "랭킹"){
            switch ($args[0]){
                case "보기":
                    $sender->sendMessage($this->m["m"] ." ===랭킹 순위표 ===");
                    
                    for ($a = 1; $a =< 5; $a++){
                        $sender->sendMessage($this->m["m"] ." [".$this->rank[$a]."]등 ");
                        
                        
                    }
                    break;
                case "내랭킹":
                    break;
            }
        }*/
        if ($command->getName() == "스탯"){
            if ($args[0] == "찍기"){
                switch ($args[1]){
                    case "체력":
                        
                        if ($this->player[$name]["stat"] !== "0"){
                            if (!isset($args[2])){
                                
                                
                                $this->player[$name]["stat"] =$this->player[$name]["stat"]- 1;
                                
                                $this->player[$name]["health"] =$this->player[$name]["health"]+1;
                                
                                $sender->sendMessage($this->m["m"] ." 체력스텟: ".$this->player[$name]["health"]);
                            }
                            else if (is_numeric($args[2]) and $args[2] <= $this->player[$name]["stat"]){
                                $this->player[$name]["stat"] =$this->player[$name]["stat"]- $args[2];
                                
                                $this->player[$name]["health"] =$this->player[$name]["health"]+$args[2];
                                
                                $sender->sendMessage($this->m["m"] ." 체력스텟: ".$this->player[$name]["health"]);
                            }
                        }
                        break;
                        
                    case "이동속도":
                        if ($this->player[$name]["stat"] !== "0"){
                            if (!isset($args[2])){
                                
                                $this->player[$name]["stat"]  =$this->player[$name]["stat"]- 1;
                                $this->player[$name]["speed"]  =$this->player[$name]["speed"]+1;
                                $sender->sendMessage($this->m["m"] ." 이동속도 스텟: ". $this->player[$name]["speed"] );
                                
                            }
                            else if (is_numeric($args[2]) and $args[2] < $this->player[$name]["stat"] ){
                                $this->player[$name]["stat"]  =$this->player[$name]["stat"]- $args[2];
                                $this->player[$name]["speed"]  =$this->player[$name]["speed"]+$args[2];
                                $sender->sendMessage($this->m["m"] ." 이동속도 스텟: ". $this->player[$name]["speed"] );
                                
                            }
                            
                        }
                        break;
                        
                    default:
                        break;
                }
            }
        }
        if ($command->getName() == "퀘스트"){
            switch ($args[0]){
                case "수령":
                    
                    if ($this->player[$name]["quest"] !== "0"){
                        
                        
                        $this->player[$name]["quest"] = $this->quest();
                        $this->save();
                        $sender->sendMessage($this->m["m"] ."현제 당신의 퀘스트는 ".$this->player["quest"]."입니다");
                        
                    }
                    break;
                case "확인":
                    
                    $sender->sendMessage($this->m["m"] ."현제 당신의 퀘스트는 ".$this->player["quest"]."입니다");
                    $this->save();
                    break;
                case "보상받기":
                   switch ($this->player[$name]["quest"]){
                       case "이보게 젊은이 나를 위해 양귀비 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지":
                           if ($sender->getInventory()->contains(Item::get(38,0,50))){
                               $sender->getInventory()->remove(Item::get(38, 0, 50));
                               $this->player[$name]["quest"] = "0";
                               $this->save();
                               
                               
                               $this->player[$name]["exp"] = $this->player[$name]["exp"]+mt_rand($this->player[$name]["level"]*20 ,$this->player[$name]["level"]*30);
                               EconomyAPI::getInstance()->addMoney($sender->getName(), mt_rand($this->player[$name]["level"]*1000 , $this->player[$name]["level"]* 1250));
                               $sender->sendMessage($this->m["m"]. " 고맙네 고마워 ");
                               $sender->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                               if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                                   $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                                   $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                                   $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                                   $this->player[$name]["exp"] =0;
                                   $sender->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                                   
                               }
                           }
                           
                           
                           
                           
                           break;
                       case "이보게 젊은이 나를 위해 해바라기 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지":
                           if ($sender->getInventory()->contains(Item::get(37, 0, 50))){
                               $sender->getInventory()->remove(Item::get(175, 0, 50));
                           $this->player[$name]["quest"] = "0";
                           
                           $this->player[$name]["exp"] = $this->player[$name]["exp"]+mt_rand($this->player[$name]["level"]*20 ,$this->player[$name]["level"]*30);
                           EconomyAPI::getInstance()->addMoney($sender->getName(), mt_rand($this->player[$name]["level"]*1000 , $this->player[$name]["level"]* 1250));
                           $sender->sendMessage($this->m["m"]. " 고맙네 고마워 ");
                           $sender->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                           if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                               $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                               $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                               $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                               $this->player[$name]["exp"] =0;
                               $sender->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                               
                           }
                   }
                           
                           break;
                       case "이보게 젊은이 나를 위해 민들레 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지":
                           if ($sender->getInventory()->contains(Item::get(37, 0, 50))){
                           $sender->getInventory()->remove(Item::get(37, 0, 50));
                           $this->player[$name]["quest"] = "0";
                           
                           $this->player[$name]["exp"] = $this->player[$name]["exp"]+mt_rand($this->player[$name]["level"]*20 ,$this->player[$name]["level"]*30);
                           EconomyAPI::getInstance()->addMoney($sender->getName(), mt_rand($this->player[$name]["level"]*1000 , $this->player[$name]["level"]* 1250));
                           $sender->sendMessage($this->m["m"]. " 고맙네 고마워 ");
                           $sender->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                           if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                               $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                               $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                               $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                               $this->player[$name]["exp"] =0;
                               $sender->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                               
                           }
                           
                           
                           }
                           break;
                           
                   }
            }
        }
        
    }
    public function damage(EntityDamageEvent$event){
        
        
        $e = $event->getEntity();
       
            
            
        $damager = $event->getDamager();
        if ($event instanceof EntityDamageByEntityEvent){
            if ($damager instanceof Player ){
                
                if ($e instanceof Player){
                $name = $e->getName();
                
                
                $stat = 1-$this->player[$name]["typelevel"]*0.00125;
                
                if ($this->player[$name]["type"] == "흙"){
                    $event->setDamage($event->getDamage()*$stat);
                }
                }
                $name = $damager->getName();
                $fire = $this->player[$name]["typelevel"]*0.00125+1;
                $stat = 1-$this->player[$name]["typelevel"]*0.00125;
                
                switch ($this->player[$damager->getName()]["type"]){
                    case "불":
                        $event->setDamage($event->getDamage()*$fire);
                        break;
                    case "물":
                        if($this->random($stat) == "true"){
                            $e->getPlayer()->addEffect(Effect::getEffect(2)->setAmplifier(0)->setDuration(20));
                        }
                        break;
                    case "공기":
                        if($this->random($stat) == "true"){
                            $y =0;
                            while ($y == 50){
                                $e->getPlayer()->teleport(new Vector3($e->getPlayer()->getX(),$e->getPlayer()->getY()+0.1,$e->getPlayer()->getZ()));
                                $y++;
                                $e->getPlayer()->getLevel()->addParticle(new LavaParticle(new Vector3($e->getPlayer()->getX(),$e->getPlayer()->getY()-0.1,$e->getPlayer()->getZ())));
                            }
                        }
                        
                        break;
                }
                
                
                
                if (mt_rand(1, 100) < 50 and $this->player[$name]["job"] == "궁수"){
                    if ($damager->getInventory()->getItemInHand() == Item::get(261,0,1)){
                        $e->getEntity()->addEffect(Effect::getEffect(19)->setDuration(mt_rand(60 , 100))->setAmplifier(0));
                        $p->sendMessage($this->m["m"]." 독이 제대로 스며들었군");
                        if ($damager->getInventory()->contains(Item::get(287,0,1)) and $damager->isSneaking()){
                            $damager->getInventory()->remove(Item::get(287,0,1));
                            $damager->teleport(new Vector3($e->getX() , $e->getY() , $e->getZ() ,$e->getLevel()) );
                            $e->teleport(new Vector3($damager->getX(), $damager->getY() , $damager->getZ(), $damager->getLevel()));
                            
                        }
                    }
                }
                if ($e instanceof Zombie and $event instanceof EntityDamageByEntityEvent){
                    $damager = $event->getDamager();
                    if ($damager instanceof Player){
                        $damager->sendMessage($this->m["m"]." 후후후 좀 따끔한걸");
                        $damager->sendMessage($this->m["m"]." BOSS좀비".$e->getHealth()." / ".$e->getMaxHealth());
                        
                        $name = $damager->getName();
                        
                        $this->player[$name]["exp"] = $this->player[$name]["exp"] + $event->getDamage()*1;
                        $damager->sendMessage($this->m["m"]." 경험치 ".$this->player[$name]["exp"] ." / ".$this->player[$name]["level"]*125);
                        if ($this->player[$name]["exp"] >= $this->player[$name]["level"]*125 and $this->player[$name]["level"] <400){
                            $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
                            $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
                            $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
                            $this->player[$name]["exp"] =0;
                            $damager->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
                            
                        }
                    }
                }
            }
             if ($damager instanceof Zombie){
                $event->setDamage(7);
            }
            if (mt_rand(1, 100) < 5+$this->player[$name]["level"]*0.05 and $this->player[$name]["job"] == "기사"){
                $p->sendMessage($this->m["m"]." 순간적으로 공격을 막았다");
                
                $event->setDamage(0);
                
            }
        }
        
        
    }

    public function spawn(EntitySpawnEvent$event){
        $e = $event->getEntity();
        if ($e instanceof Zombie){
            $e->setMaxHealth(mt_rand(600 , 1000));
            $e->setHealth(1000);
        
        
        }
        
    }
    public function touch(PlayerInteractEvent$event){
        
        $p = $event->getPlayer();
        $name = $p->getName();
        $t = $this->player[$name]["typelevel"]*0.00125;
        
        $tl = $this->player[$name]["typelevel"];
        
        $speed = $this->player[$name]["speed"];
        $health = $this->player[$name]["health"];
        $stat = $this->player[$name]["stat"];
        
        
        if ($event->getItem()->getId() == "369" and $this->cool[$name]["g"] == "0" and !$event->getPlayer()->isSneaking() and $this->player[$name]["job"] !== "백수"){
            $this->cool[$name]["g"] = time();
            foreach ($p->getLevel()->getEntities() as $entity){
                $x = $entity->getX();
                $y = $entity->getY();
                $z = $entity->getZ();
                
                
                if ($this->player[$name]["job"] == "마법사"){
                    
                    if ($entity instanceof Player){
                        
                        if ($x <$p->getX()+5 and $p->getX()-5 < $x  and $z <$p->getZ()+5 and $p->getZ() -5 < $z and $entity->getName() !== $name){
                            
                            for ($i = 0; $i <$this->player[$name]["level"]*0.01 +1  ; $i++) {
                                
                                
                                $packet = new AddEntityPacket();
                                $packet->eid = Entity::$entityCount ++;
                                $packet->x = $x;
                                $packet->y = $y;
                                $packet->z = $z;
                                $packet->type = 93;
                                $packet->speedX = 0;
                                $packet->speedY = 0;
                                $packet->speedZ = 0;
                                $packet->metadata = array ();
                                $this->getServer ()->broadcastPacket ($p->getLevel()->getEntities(), $packet );
                                $explore = new Explosion(new Position($x,$y,$z,$p->getLevel()), 3);
                            }
                        }
                        
                        
                    }
                    $p->sendMessage($this->m["m"]."라이트니이이이이잉");
                }
                
                
                if ($this->player[$name]["job"] == "도적"){
                    
                    $p->sendMessage($this->m["m"]." 은신");
                    $p->addEffect(Effect::getEffect(14)->setAmplifier(50)->setDuration(5*20+$this->player[$name]["level"]*0.025));
                    
                }
                
                
                
                
                if ($this->player[$name]["job"] == "기사"){
                    
                    $p->sendMessage($this->m["m"]." 회복");
                    $p->addEffect(Effect::getEffect(10)->setAmplifier(floor($this->player[$name]["level"]*0.01))->setDuration(5*20+$this->player[$name]["level"]*0.025));
                    
                    
                    
                }
                
                
                
            }
            
            
            
            
            
            
        }
        else if( !$event->getPlayer()->isSneaking() and $event->getItem()->getId() == "369"  and $this->player[$name]["job"] !== "백수"){
            $this->cool[$name]["lg"] = time();
            if (  $this->cool[$name]["lg"] - $this->cool[$name]["g"] >= "60"){
                foreach ($p->getLevel()->getEntities() as $entity){
                    $x = $entity->getX();
                    $y = $entity->getY();
                    $z = $entity->getZ();
                    
                    if ($this->player[$name]["job"] == "마법사"){
                        
                        if ($entity instanceof Player){
                            
                            if ($x <$p->getX()+5 and $p->getX()-5 < $x  and $z <$p->getZ()+5 and $p->getZ() -5 < $z and $entity->getName() !== $name){
                                
                                for ($i = 0; $i <$this->player[$name]["level"]*0.01 +1  ; $i++) {
                                    
                                    
                                    $packet = new AddEntityPacket();
                                    $packet->eid = Entity::$entityCount ++;
                                    $packet->x = $x;
                                    $packet->y = $y;
                                    $packet->z = $z;
                                    $packet->type = 93;
                                    $packet->speedX = 0;
                                    $packet->speedY = 0;
                                    $packet->speedZ = 0;
                                    $packet->metadata = array ();
                                    $this->getServer ()->broadcastPacket ($p->getLevel()->getEntities(), $packet );
                                    $explore = new Explosion(new Position($x,$y,$z,$p->getLevel()), 3);
                                    
                                }
                            }
                            
                            
                        }
                        $this->cool[$name]["g"] = time();
                        
                    }
                    
                }
                
                
                if ($this->player[$name]["job"] == "도적"){
                    
                    $p->sendMessage($this->m["m"]." 은신");
                    $p->addEffect(Effect::getEffect(14)->setAmplifier(50)->setDuration(5*20+$this->player[$name]["level"]*0.025));
                    $this->cool[$name]["g"] = time();
                    
                }
                
                
                
                
                if ($this->player[$name]["job"] == "기사"){
                    
                    $p->sendMessage($this->m["m"]." 회복");
                    $p->addEffect(Effect::getEffect(10)->setAmplifier(floor($this->player[$name]["level"]*0.01))->setDuration(5*20+$this->player[$name]["level"]*0.5));
                    $this->cool[$name]["g"] = time();
                    
                }
                
                
            }
            else if ($this->player[$name]["job"] !== "백수") {
                $t =$this->cool[$name]["lg"] - $this->cool[$name]["g"];
                $c = 60 - $t;
                $p->sendMessage($this->m["m"]." 쿨타임이 ".$c ."초 남았습니다");
            }
        }
        
        
        
        
        
        if ( $event->getPlayer()->isSneaking() and $event->getItem()->getId() == "369" and $this->cool[$name]["gg"] == "0" and $this->player[$name]["job"] !== "백수"){
            $this->cool[$name]["gg"] = time();
            foreach ($p->getLevel()->getEntities() as $entity){
                $x = $entity->getX();
                $y = $entity->getY();
                $z = $entity->getZ();
                
                if ($this->player[$name]["job"] == "마법사"){
                    
                    $p->sendMessage($this->m["m"]." 시공간을 지나 텔포");
                    $p->teleport(new Vector3(mt_rand($p->getX() - 5- $this->player[$name]["level"]* 0.05 ,
                        $p->getX() + 5+ $this->player[$name]["level"]* 0.05 ),$p->getY(),
                        mt_rand($p->getZ() - 5- $this->player[$name]["level"]* 0.05 ,
                            $p->getZ() + 5+ $this->player[$name]["level"]* 0.05 )
                        ,$p->getLevel()));
                    
                }
                
                
                if ($this->player[$name]["job"] == "도적"){
                    $p->setMotion ( $p->getMotion()->multiply(floor($this->player[$name]["level"]*0.2 +3 )));
                    $p->sendMessage($this->m["m"]." 도적은 빠른 움직임이 기본이지");
                    
                    
                    
                }
                
                
                
                
                if ($this->player[$name]["job"] == "기사"){
                    
                    
                    $event->getPlayer()->setScale(1.5);
                    $event->getPlayer()->addEffect(Effect::getEffect(5)->setAmplifier(0)->setDuration($level*4 + 5));
                    $event->getPlayer()->setHealth(100000);
                    $p->sendMessage($this->m["m"]." 기사의 갑옷해방!");
                    
                }
                
                
                
                
            }
        }
        
        else if ($event->getPlayer()->isSneaking() and $event->getItem()->getId() == "369" and $this->player[$name]["job"] !== "백수" ){
            $this->cool[$name]["lgg"] = time();
            if (  $this->cool[$name]["lgg"] - $this->cool[$name]["gg"] > "60"){
                foreach ($p->getLevel()->getEntities() as $entity){
                    $x = $entity->getX();
                    $y = $entity->getY();
                    $z = $entity->getZ();
                    
                    if ($this->player[$name]["job"] == "마법사"){
                        
                        $p->sendMessage($this->m["m"]." 시공간을 지나 텔포");
                        $p->teleport(new Vector3(mt_rand($p->getX() - 5- $this->player[$name]["level"]* 0.05 ,
                            $p->getX() + 5+ $this->player[$name]["level"]* 0.05 ),$p->getY(),
                            mt_rand($p->getZ() - 5- $this->player[$name]["level"]* 0.05 ,
                                $p->getZ() + 5+ $this->player[$name]["level"]* 0.05 )
                            ,$p->getLevel()));
                        $this->cool[$name]["gg"] = time();
                    }
                    
                    
                    if ($this->player[$name]["job"] == "도적"){
                        $p->setMotion ( $p->getMotion()->multiply(floor($this->player[$name]["level"]*0.2 +3 )));
                        $p->sendMessage($this->m["m"]." 도적은 빠른 움직임이 기본이지");
                        $this->cool[$name]["gg"] = time();
                    }
                    
                    
                    
                    
                    if ($this->player[$name]["job"] == "기사"){
                        
                        $event->getPlayer()->setScale(1.5);
                        $event->getPlayer()->setHealth(100000);
                        $p->sendMessage($this->m["m"]." 기사의 갑옷해방!");
                        $this->cool[$name]["gg"] = time();
                        
                    }
                    
                    
                    
                    
                }
            }
            else if ( $this->player[$name]["job"] !== "백수"){
                $t =$this->cool[$name]["lgg"] - $this->cool[$name]["gg"];
                $c = 60 - $t;
                $p->sendMessage($this->m["m"]." 쿨타임이 ".$c ."초 남았습니다");
            }
            
        }
        if ($event->getItem()->getId() == "369" and $this->player[$p->getName()]["job"] == "궁수" ){
            
            $p->addEffect(Effect::getEffect(2)->setAmplifier(0)->setDuration(20*1));
            
            
        }
        
        
        
        if ($event->getItem()->getId() == "318" ){
            if ($this->player[$name]["speed"] < 100 and  1 <$this->player[$name]["speed"] ){
                $event->getPlayer()->addEffect(Effect::getEffect(1)->setAmplifier(0)->setDuration(5*20+$this->player[$name]["speed"]*10));
                $event->getPlayer()->getInventory()->removeItem(Item::get(318, 0, 1));
                $event->getPlayer()->sendMessage($this->m["m"] ." 신소쿠"."우다다다다ㅏ다다닫");
                
            }
            
            else if ($this->player[$name]["speed"] < 200 and  100 <$this->player[$name]["speed"]){
                $event->getPlayer()->getInventory()->removeItem(Item::get(318, 0, 1));
                
                $event->getPlayer()->sendMessage($this->m["m"] ." 신소쿠"."우다다다다ㅏ다다닫");
                $event->getPlayer()->addEffect(Effect::getEffect(1)->setAmplifier(1)->setDuration(5*20+$this->player[$name]["speed"]*10));
            }
            else if (  200 <$this->player[$name]["speed"]){
                $event->getPlayer()->getInventory()->removeItem(Item::get(318, 0, 1));
                
                $event->getPlayer()->sendMessage($this->m["m"] ." 신소쿠"."우다다다다ㅏ다다닫");
                $event->getPlayer()->addEffect(Effect::getEffect(1)->setAmplifier(2)->setDuration(5*20+$this->player[$name]["speed"]*10));
            }
        }
        if ($event->getItem()->getId() == "339"and $this->player[$name]["job"] !== "백수"){
            $this->player[$name]["exp"] = 0;
            $this->player[$name]["level"] = $this->player[$name]["level"]+ 1;
            $this->player[$name]["stat"] =$this->player[$name]["stat"]+2;
            $this->player[$name]["typelevel"] = $this->player[$name]["typelevel"]+ 1;
            $event->getPlayer()->getInventory()->removeItem(Item::get(339,0,1));
            $event->getPlayer()->sendMessage($this->m["m"] ." 레벨업"." 현제 레벨은 ".$this->player[$name]["level"]."입니다");
        }
    } // 메소드 끝
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function job($name){
        return $this->player[$name]["job"];
    }
    public function random($t){
        if (mt_rand(1,100) < $t*100) {
            return "true";
        }
    }
    public function type(){
        switch (mt_rand(1,4)){
            case 1:
                return "물";
                break;
            case 2:
                return "불";
                break;
            case 3:
                return "흙";
                break;
            case 4:
                return "공기";
                break;
        }
    }
    public function quest(){
        switch(mt_rand(1, 3)){
            case 1:
                return "이보게 젊은이 나를 위해 양귀비 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지";
                break;
            case 2:
                return "이보게 젊은이 나를 위해 해바라기 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지";
                break;
            case 3:
                return "이보게 젊은이 나를 위해 민들레 꽃 50송이만 꺽어주시겠나?? 보상은 두둑히 주지";
                break;
        }
        
        
    }
    public function onDisable(){
        $this->save();
    }
    public function save(){
        
        
        $this->playerDB->setAll($this->player);
        $this->playerDB->save();
        
        
        
        
    }
}
