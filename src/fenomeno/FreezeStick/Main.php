<?php

namespace fenomeno\FreezeStick;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\AnvilBreakSound;

class Main extends PluginBase implements Listener {
    use CooldownTrait;

    private Item $freezeItem;

    protected function onLoad(): void
    {
        $this->saveDefaultConfig();
        $item = StringToItemParser::getInstance()->parse($this->getConfig()->get('item_name'));
        if(is_null($item)){
            $this->getLogger()->warning("§cL'item " . $this->getConfig()->get('item_name') . " n'existe pas, celui-ci a été remplacé par la blaze rod");
            $this->freezeItem = VanillaItems::BLAZE_ROD();
            $this->freezeItem->setCustomName(TextFormat::RESET . "§fFreeze Stick");
        } else $this->freezeItem = $item;
    }

    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if ($damager instanceof Player) {
            if ($damager->getInventory()->getItemInHand()->equals($this->freezeItem, false, false)){
                $cop = strtolower($damager->getUniqueId()->toString());
                if (!$this->isOnCooldown("freeze-$cop")){
                    if ($entity->hasNoClientPredictions()){
                        if (!$this->getConfig()->get('canHitFreezePlayer')) {
                            $event->cancel();
                        }
                        return;
                    }
                    $entity->setNoClientPredictions();
                    if ($entity->isAlive() && $entity->hasNoClientPredictions()){
                        if ($this->getConfig()->get('playSound')){
                            $damager->broadcastSound(new AnvilBreakSound());
                        }
                        $damager->sendMessage((string)$this->getConfig()->get('succes-freeze'));
                        if ($entity instanceof Player){
                            $this->sendMessage($entity, $this->getConfig()->get('entityFreezedMsg'), $this->getConfig()->get('entityFreezedMsgType'));
                        }
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($entity) {
                            if ($entity instanceof Player){
                                if (!$entity->isConnected()){
                                    return;
                                }
                            }
                            if (!$entity->isAlive()){
                                return;
                            }
                            $entity->setNoClientPredictions(false);
                            if($entity instanceof Player) {
                                $entity->sendMessage($this->getConfig()->get('unfreezeMessage'));
                            }
                        }), 20 * (int)$this->getConfig()->get('freeze-cooldown'));
                    }

                    $this->setCooldown("freeze-$cop", (int)$this->getConfig()->get('cooldown'));
                } else $this->sendMessage($damager, str_replace("{s}", strval(intval($this->getCooldown("freeze-$cop"))), $this->getConfig()->get('cooldown-message')), $this->getConfig()->get('cooldownTypeMessage'));
            }
        }
    }

    private function sendMessage(Player $player, string $message, string $type){
        switch ($type){
            case "popup":
                $player->sendPopup($message);
                break;
            case "title":
                $player->sendMessage($message);
                break;
            case "message":
                $player->sendTitle(is_array(explode("\n", $message)) ? explode("\n", $message)[0] : $message, is_array(explode("\n", $message)) ? explode("\n", $message)[1] : "");
                break;
        }
    }

}