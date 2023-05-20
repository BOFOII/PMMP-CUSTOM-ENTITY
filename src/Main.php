<?php

declare(strict_types=1);

namespace BOFOIII\CustomEntity;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use ReflectionClass;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;

class Main extends PluginBase
{

    private int $id = 0;

    // https://wiki.vg/Bedrock_Protocol#Animate_Entity
    protected function onEnable(): void
    {

        EntityFactory::getInstance()->register(GrandHamer::class, function (World $world, CompoundTag $nbt): GrandHamer {
            return  new GrandHamer(EntityDataHelper::parseLocation($nbt, $world), null);
        }, ["raigen:grand_hammer"]);

        $instance = StaticPacketCache::getInstance();
        $staticPacketCache = new ReflectionClass($instance);
        $property = $staticPacketCache->getProperty("availableActorIdentifiers");
        $property->setAccessible(true);
        /** @var AvailableActorIdentifiersPacket $packet */
        $packet = $property->getValue($instance);
        /** @var CompoundTag $root */
        $root = $packet->identifiers->getRoot();
        $idList = $root->getListTag("idlist") ?? new ListTag();
        $idList->push(CompoundTag::create()
            ->setString("id", "raigen:grand_hammer")
            ->setString("bid", ""));
        $packet->identifiers = new CacheableNbt($root);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!$sender instanceof Player) {
            return false;
        }

        switch ($command->getName()) {
            case "grand_hammer":
                $entity = new GrandHamer($sender->getLocation(), null);
                $entity->spawnToAll();
                $this->id = $entity->getId();
                return true;
            case "stomping":
                $pk = AnimateEntityPacket::create(
                    "animation.grand_hammer.stomping",
                    "default",
                    "query.any_animation_finished",
                    1,
                    "controller.animation.grand_hammer.attack",
                    0,
                    [$this->id]
                );
                $sender->getNetworkSession()->sendDataPacket($pk);
                return true;
            default:
                
                return false;
        }
    }
}

class GrandHamer extends Living
{

    public static function getNetworkTypeId(): string
    {
        return "raigen:grand_hammer";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(6, 13);
    }

    public function getName(): string
    {
        return "GrandHamer";
    }
}
