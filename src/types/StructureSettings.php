<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\math\Vector3;

class StructureSettings {
    public string $paletteName;
    public bool $ignoreEntities;
    public bool $ignoreBlocks;
    public bool $allowNonTickingChunks;
    public BlockPosition $dimensions;
    public BlockPosition $offset;
    public int $lastTouchedByPlayerID;
    public int $rotation;
    public int $mirror;
    public int $animationMode;
    public float $animationSeconds;
    public float $integrityValue;
    public int $integritySeed;
    public Vector3 $pivot;

    public function __construct(
        string $paletteName = "",
        bool $ignoreEntities = false,
        bool $ignoreBlocks = false,
        bool $allowNonTickingChunks = false,
        BlockPosition $dimensions = null,
        BlockPosition $offset = null,
        int $lastTouchedByPlayerID = 0,
        int $rotation = 0,
        int $mirror = 0,
        int $animationMode = 0,
        float $animationSeconds = 0.0,
        float $integrityValue = 1.0,
        int $integritySeed = 0,
        Vector3 $pivot = null
    ) {
        $this->paletteName = $paletteName;
        $this->ignoreEntities = $ignoreEntities;
        $this->ignoreBlocks = $ignoreBlocks;
        $this->allowNonTickingChunks = $allowNonTickingChunks;
        $this->dimensions = $dimensions ?? new BlockPosition(0, 0, 0);
        $this->offset = $offset ?? new BlockPosition(0, 0, 0);
        $this->lastTouchedByPlayerID = $lastTouchedByPlayerID;
        $this->rotation = $rotation;
        $this->mirror = $mirror;
        $this->animationMode = $animationMode;
        $this->animationSeconds = $animationSeconds;
        $this->integrityValue = $integrityValue;
        $this->integritySeed = $integritySeed;
        $this->pivot = $pivot ?? new Vector3(0, 0, 0);
    }

    public function isValidRotation(): bool {
        return in_array($this->rotation, [0, 90, 180, 270], true);
    }

    public function isValidMirror(): bool {
        return in_array($this->mirror, [0, 1], true);
    }

    public function setIntegrity(float $value): void {
        $this->integrityValue = max(0.0, min(1.0, $value));
    }

    public function setPaletteName(string $name): void {
        $this->paletteName = $name;
    }

    public function setAnimation(float $seconds, int $mode): void {
        $this->animationSeconds = $seconds;
        $this->animationMode = $mode;
    }
}
