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
 use pocketmine\network\mcpe\protocol\types\login\JwtChain;
 use pocketmine\utils\BinaryStream;
 use JsonException;
 use function count;
 use function is_array;
 use function json_decode;
 use function json_encode;
 use function strlen;
 use const JSON_THROW_ON_ERROR;
 
 class LoginPacket extends DataPacket implements ServerboundPacket{
	 public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;
 
	 public int $protocol;
	 public JwtChain $chainDataJwt;
	 public string $clientDataJwt;
 
	 public static function create(int $protocol, JwtChain $chainDataJwt, string $clientDataJwt): self{
		 $instance = new self;
		 $instance->protocol = $protocol;
		 $instance->chainDataJwt = $chainDataJwt;
		 $instance->clientDataJwt = $clientDataJwt;
		 return $instance;
	 }
 
	 public function canBeSentBeforeLogin(): bool{
		 return true;
	 }
 
	 protected function decodePayload(PacketSerializer $in): void{
		 $this->protocol = $in->getInt();
		 $this->decodeConnectionRequest($in->getString());
	 }
 
	 protected function decodeConnectionRequest(string $binary): void{
		 $reader = new BinaryStream($binary);
		 $chainDataJsonLength = $reader->getLInt();
 
		 if($chainDataJsonLength <= 0){
			 throw new PacketDecodeException("Invalid chain data JSON length");
		 }
 
		 $chainDataJson = $this->decodeJson($reader->get($chainDataJsonLength), 'chain');
		 $this->chainDataJwt = $this->createJwtChain($chainDataJson['chain']);
 
		 $clientDataJwtLength = $reader->getLInt();
		 if($clientDataJwtLength <= 0){
			 throw new PacketDecodeException("Invalid clientData JWT length");
		 }
		 $this->clientDataJwt = $reader->get($clientDataJwtLength);
	 }
 
	 private function decodeJson(string $json, string $expectedKey): array{
		 try {
			 $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		 } catch (JsonException $e) {
			 throw new PacketDecodeException("Failed decoding JSON: " . $e->getMessage());
		 }
		 if(!isset($data[$expectedKey]) || !is_array($data[$expectedKey])){
			 throw new PacketDecodeException("Invalid structure for key: {$expectedKey}");
		 }
		 return $data;
	 }
 
	 private function createJwtChain(array $chain): JwtChain{
		 foreach($chain as $jwt){
			 if(!is_string($jwt)){
				 throw new PacketDecodeException("JWT must be a string");
			 }
		 }
		 $jwtChain = new JwtChain;
		 $jwtChain->chain = $chain;
		 return $jwtChain;
	 }
 
	 protected function encodePayload(PacketSerializer $out): void{
		 $out->putInt($this->protocol);
		 $out->putString($this->encodeConnectionRequest());
	 }
 
	 protected function encodeConnectionRequest(): string{
		 $writer = new BinaryStream();
		 $chainDataJson = json_encode($this->chainDataJwt, JSON_THROW_ON_ERROR);
 
		 $writer->putLInt(strlen($chainDataJson));
		 $writer->put($chainDataJson);
		 $writer->putLInt(strlen($this->clientDataJwt));
		 $writer->put($this->clientDataJwt);
 
		 return $writer->getBuffer();
	 }
 
	 public function handle(PacketHandlerInterface $handler): bool{
		 return $handler->handleLogin($this);
	 }
 }