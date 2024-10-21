<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\utils\BinaryDataException;
use function get_class;

abstract class DataPacket implements Packet
{
    public const NETWORK_ID = 0;
    public const PID_MASK = 0x3FF; // 10 bits
    private const SUBCLIENT_ID_MASK = 0x03; // 2 bits
    private const SENDER_SUBCLIENT_ID_SHIFT = 10;
    private const RECIPIENT_SUBCLIENT_ID_SHIFT = 12;

    public int $senderSubId = 0;
    public int $recipientSubId = 0;

    public static function create(): static
    {
        return new static();
    }

    public function pid(): int
    {
        return self::NETWORK_ID;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function canBeSentBeforeLogin(): bool
    {
        return false;
    }

    /**
     * @throws PacketDecodeException
     */
    final public function decode(PacketSerializer $in): void
    {
        try {
            $this->decodeHeader($in);
            $this->decodePayload($in);
        } catch (BinaryDataException | PacketDecodeException $e) {
            throw PacketDecodeException::wrap($e, $this->getName());
        }
    }

    /**
     * @throws BinaryDataException
     * @throws PacketDecodeException
     */
    protected function decodeHeader(PacketSerializer $in): void
    {
        $header = $in->getUnsignedVarInt();
        $pid = $header & self::PID_MASK;

        $this->validatePacketId($pid);

        $this->senderSubId = ($header >> self::SENDER_SUBCLIENT_ID_SHIFT) & self::SUBCLIENT_ID_MASK;
        $this->recipientSubId = ($header >> self::RECIPIENT_SUBCLIENT_ID_SHIFT) & self::SUBCLIENT_ID_MASK;
    }

    protected function validatePacketId(int $pid): void
    {
        if ($pid !== self::NETWORK_ID) {
            throw new PacketDecodeException("Invalid packet ID: expected " . self::NETWORK_ID . ", got $pid");
        }
    }

    /**
     * Decodes the packet body, excluding the packet ID and other generic header fields.
     *
     * @throws PacketDecodeException
     * @throws BinaryDataException
     */
    abstract protected function decodePayload(PacketSerializer $in): void;

    final public function encode(PacketSerializer $out): void
    {
        $this->encodeHeader($out);
        $this->encodePayload($out);
    }

    protected function encodeHeader(PacketSerializer $out): void
    {
        $out->putUnsignedVarInt(
            self::NETWORK_ID |
            ($this->senderSubId << self::SENDER_SUBCLIENT_ID_SHIFT) |
            ($this->recipientSubId << self::RECIPIENT_SUBCLIENT_ID_SHIFT)
        );
    }

    /**
     * Encodes the packet body, excluding the packet ID and other generic header fields.
     */
    abstract protected function encodePayload(PacketSerializer $out): void;

    public function __get(string $name)
    {
        throw new \Error("Undefined property: " . get_class($this) . "::\$$name");
    }

    public function __set(string $name, $value): void
    {
        throw new \Error("Undefined property: " . get_class($this) . "::\$$name");
    }
}
