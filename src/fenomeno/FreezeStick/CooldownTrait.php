<?php
namespace fenomeno\FreezeStick;

trait CooldownTrait {

    private array $cooldowns = [];

    public function setCooldown(string $name, float $seconds): void {
        $this->cooldowns[$name] = time() + $seconds;
    }

    public function getCooldown(string $name): float {
        $time = $this->cooldowns[$name] ?? microtime(true);
        $seconds = intval(round($time - microtime(true)));
        if ($seconds <= 0){
            $this->removeCooldown($name);
        }

        return $seconds;
    }

    public function isOnCooldown(string $name): bool {
        return $this->getCooldown($name) > 0;
    }

    public function removeCooldown(string $name): void {
        unset($this->cooldowns[$name]);
    }

}