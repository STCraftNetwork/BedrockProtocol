<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

class StructureEditorData{
    public const TYPE_DATA = 0;
    public const TYPE_SAVE = 1;
    public const TYPE_LOAD = 2;
    public const TYPE_CORNER = 3;
    public const TYPE_INVALID = 4;
    public const TYPE_EXPORT = 5;

    public string $structureName;
    public string $structureDataField;
    public bool $includePlayers;
    public bool $showBoundingBox;
    public int $structureBlockType;
    public StructureSettings $structureSettings;
    public int $structureRedstoneSaveMode;

    public function __construct(
        string $structureName = "",
        string $structureDataField = "",
        bool $includePlayers = false,
        bool $showBoundingBox = true,
        int $structureBlockType = self::TYPE_DATA,
        StructureSettings $structureSettings = null,
        int $structureRedstoneSaveMode = 0
    ) {
        $this->structureName = $structureName;
        $this->structureDataField = $structureDataField;
        $this->includePlayers = $includePlayers;
        $this->showBoundingBox = $showBoundingBox;
        $this->structureBlockType = $structureBlockType;
        $this->structureSettings = $structureSettings ?? new StructureSettings();
        $this->structureRedstoneSaveMode = $structureRedstoneSaveMode;
    }

    public function isValidStructureBlockType(): bool {
        return in_array($this->structureBlockType, [
            self::TYPE_DATA,
            self::TYPE_SAVE,
            self::TYPE_LOAD,
            self::TYPE_CORNER,
            self::TYPE_INVALID,
            self::TYPE_EXPORT
        ], true);
    }

    public function getBlockTypeAsString(): string {
        switch ($this->structureBlockType) {
            case self::TYPE_DATA:
                return "Data Block";
            case self::TYPE_SAVE:
                return "Save Block";
            case self::TYPE_LOAD:
                return "Load Block";
            case self::TYPE_CORNER:
                return "Corner Block";
            case self::TYPE_INVALID:
                return "Invalid Block";
            case self::TYPE_EXPORT:
                return "Export Block";
            default:
                return "Unknown";
        }
    }

    public function setStructureName(string $name): void {
        $this->structureName = $name;
    }

    public function setRedstoneSaveMode(int $mode): bool {
        if ($mode >= 0) {
            $this->structureRedstoneSaveMode = $mode;
            return true;
        }
        return false;
    }
}
