<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\utils\Limits;
use function count;
use const PHP_INT_MAX;

class LevelChunkPacket extends DataPacket implements ClientboundPacket {
    public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

    private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
    private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;
    private const MAX_BLOB_HASHES = 64;

    private ChunkPosition $chunkPosition;
    /** @phpstan-var DimensionIds::* */
    private int $dimensionId;
    private int $subChunkCount;
    private bool $clientSubChunkRequestsEnabled;
    /** @var int[]|null */
    private ?array $usedBlobHashes = null;
    private string $extraPayload;

    /**
     * Create a new LevelChunkPacket instance.
     *
     * @param ChunkPosition $chunkPosition The position of the chunk.
     * @param int $dimensionId The dimension ID.
     * @param int $subChunkCount The number of subchunks.
     * @param bool $clientSubChunkRequestsEnabled Whether client subchunk requests are enabled.
     * @param int[]|null $usedBlobHashes The used blob hashes.
     * @param string $extraPayload Additional payload data.
     * @return self The created LevelChunkPacket instance.
     */
    public static function create(
        ChunkPosition $chunkPosition,
        int $dimensionId,
        int $subChunkCount,
        bool $clientSubChunkRequestsEnabled,
        ?array $usedBlobHashes,
        string $extraPayload
    ): self {
        $instance = new self();
        $instance->chunkPosition = $chunkPosition;
        $instance->dimensionId = $dimensionId;
        $instance->subChunkCount = $subChunkCount;
        $instance->clientSubChunkRequestsEnabled = $clientSubChunkRequestsEnabled;
        $instance->usedBlobHashes = $usedBlobHashes;
        $instance->extraPayload = $extraPayload;
        return $instance;
    }

    public function getChunkPosition(): ChunkPosition {
        return $this->chunkPosition;
    }

    public function getDimensionId(): int {
        return $this->dimensionId;
    }

    public function getSubChunkCount(): int {
        return $this->subChunkCount;
    }

    public function isClientSubChunkRequestEnabled(): bool {
        return $this->clientSubChunkRequestsEnabled;
    }

    public function isCacheEnabled(): bool {
        return $this->usedBlobHashes !== null;
    }

    /** 
     * @return int[]|null 
     */
    public function getUsedBlobHashes(): ?array {
        return $this->usedBlobHashes;
    }

    public function getExtraPayload(): string {
        return $this->extraPayload;
    }

    protected function decodePayload(PacketSerializer $in): void {
        $this->chunkPosition = ChunkPosition::read($in);
        $this->dimensionId = $in->getVarInt();

        $subChunkRequestType = $in->getUnsignedVarInt();
        $this->clientSubChunkRequestsEnabled = in_array($subChunkRequestType, [
            self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT,
            self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT,
        ]);

		$this->subChunkCount = $this->clientSubChunkRequestsEnabled ? ($subChunkRequestType === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT)
			? PHP_INT_MAX
			: $in->getLShort() : $subChunkRequestType;

        if ($in->getBool()) {
            $this->usedBlobHashes = [];
            $count = $in->getUnsignedVarInt();

            if ($count > self::MAX_BLOB_HASHES) {
                throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
            }

            for ($i = 0; $i < $count; ++$i) {
                $this->usedBlobHashes[] = $in->getLLong();
            }
        }

        $this->extraPayload = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out): void {
        $this->chunkPosition->write($out);
        $out->putVarInt($this->dimensionId);

        if ($this->clientSubChunkRequestsEnabled) {
            if ($this->subChunkCount === PHP_INT_MAX) {
                $out->putUnsignedVarInt(self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
            } else {
                $out->putUnsignedVarInt(self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
                $out->putLShort($this->subChunkCount);
            }
        } else {
            $out->putUnsignedVarInt($this->subChunkCount);
        }

        $out->putBool($this->usedBlobHashes !== null);
        if ($this->usedBlobHashes !== null) {
            $out->putUnsignedVarInt(count($this->usedBlobHashes));
            foreach ($this->usedBlobHashes as $hash) {
                $out->putLLong($hash);
            }
        }
        $out->putString($this->extraPayload);
    }

    public function handle(PacketHandlerInterface $handler): bool {
        return $handler->handleLevelChunk($this);
    }
}
