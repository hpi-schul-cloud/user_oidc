<?php

namespace OCA\UserOIDC\User\Validator;

use OCA\UserOIDC\Db\Provider;
use OCA\UserOIDC\Service\DiscoveryService;
use OCA\UserOIDC\Service\ProvisioningService;
use OCA\UserOIDC\Vendor\Firebase\JWT\JWT;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Throwable;

class SelfEncodedTokenProvisioning implements IProvisioningStrategy {

	/** @var ProvisioningService */
	private $provisioningService;

	/** @var DiscoveryService */
	private $discoveryService;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ProvisioningService $provisioningService, DiscoveryService $discoveryService, LoggerInterface $logger) {
		$this->provisioningService = $provisioningService;
		$this->discoveryService = $discoveryService;
		$this->logger = $logger;
	}

	public function provisionUser(Provider $provider, string $userId, string $bearerToken): ?IUser {
		JWT::$leeway = 60;
		try {
			$payload = JWT::decode($bearerToken, $this->discoveryService->obtainJWK($provider), array_keys(JWT::$supported_algs));
		} catch (Throwable $e) {
			$this->logger->error('MY BAD! Impossible to decode OIDC token:' . $e->getMessage());
			return null;
		}

		return $this->provisioningService->provisionUser($userId, $provider->getId(), $payload);
	}
}
