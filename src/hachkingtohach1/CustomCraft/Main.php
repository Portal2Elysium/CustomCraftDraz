<?php

namespace hachkingtohach1\CustomCraft;

use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use DaPigGuy\PiggyCustomEnchants\{CustomEnchantManager, enchants\CustomEnchant, PiggyCustomEnchants, utils\Utils};

class Main extends PluginBase{

    public function onLoad() : void{
        $this->saveDefaultConfig();
    }

    public function onEnable() : void{
        $this->registerItemsCraft();
    }

    /**
     *  This is getEnchantment int $id
     **/
    public function getEnchantment(int $id){
        $enchantment = Enchantment::getEnchantment($id);

        return $enchantment;
    }

    /**
     * This is getCEnchantment string $name Item $item, int $level
     **/
    public function getCEnchantment(string $name, Item $item, int $level) : ?CustomEnchant{
        $enchant = CustomEnchantManager::getEnchantmentByName($name);
        if($enchant === null){
            $this->getLogger()->warning('CE is ' . $name . ' with level ' . $level . ' name is null!');

            return null;
        }
        if($level > $enchant->getMaxLevel()){
            $this->getLogger()->warning('CE is ' . $name . ' with level' . $level . ' max level is ' . $enchant->getMaxLevel());

            return null;
        }
        if(!Utils::checkEnchantIncompatibilities($item, $enchant)){
            $this->getLogger()->warning('CE is ' . $name . ' with level ' . $level . 'This enchant is not compatible with another enchant.');

            return null;
        }

        return $enchant;
    }

    public function getItem(array $data) : Item{
        if(!isset($data["item"])){
            var_dump($data);
            throw new \RuntimeException("Tried to parse config data with missing \"item\" key!");
        }
        $item = Item::fromString($data["item"]);
        if(!$item instanceof Item){
            throw new \RuntimeException("Found invalid item string \"" . $data["item"] . "\" when reading config.");
        }
        $item->setCount((int) $data["qty"]);

        if(isset($data["enchantment"])){
            foreach($data["enchantment"] as $id => $level){
                $item->addEnchantment(new EnchantmentInstance($this->getEnchantment($id), $level));
            }
        }

        if(isset($data["cenchantment"])){
            foreach($data["cenchantment"] as $ceId => $ceLevel){
                if(!class_exists(PiggyCustomEnchants::class)){
                    throw new \RuntimeException("Found recipe that requires PiggyCustomEnchants but PiggyCustomEnchants is not loaded!");
                }
                $cei = $this->getCEnchantment($ceId, $item, $ceLevel);
                if($cei instanceof CustomEnchant){
                    $item->addEnchantment(new EnchantmentInstance($cei, $ceLevel));
                }
            }
        }

        return $item;
    }

    // Lenght => array() & short => []
    public function registerItemsCraft(){
        foreach($this->getConfig()->getAll() as $index => $craft){
            $items = [];
            if(!isset($craft["results"])){
                throw new \RuntimeException("Found invalid recipe \"$index\".  Missing results index!");
            }
            foreach($craft["results"] as $result){
                $item = $this->getItem($result);
                if(isset($result["name"])){
                    $item->setCustomName($result["name"]);
                }
                $items[] = $item;
            }


            $recipes = new ShapedRecipe(
                ["abc", "def", "ghi"],
                [
                    "a" => Item::fromString($craft["shape"][0][0]),
                    "b" => Item::fromString($craft["shape"][0][1]),
                    "c" => Item::fromString($craft["shape"][0][2]),
                    "d" => Item::fromString($craft["shape"][1][0]),
                    "e" => Item::fromString($craft["shape"][1][1]),
                    "f" => Item::fromString($craft["shape"][1][2]),
                    "g" => Item::fromString($craft["shape"][2][0]),
                    "h" => Item::fromString($craft["shape"][2][1]),
                    "i" => Item::fromString($craft["shape"][2][2])
                ],
                $items
            );
            $this->getServer()->getCraftingManager()->registerRecipe($recipes);
        }
    }
}
