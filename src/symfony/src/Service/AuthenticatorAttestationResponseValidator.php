<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\Bundle\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator as BaseAuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Event\AuthenticatorAttestationResponseValidationFailedEvent;
use Webauthn\Bundle\Event\AuthenticatorAttestationResponseValidationSucceededEvent;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\TokenBinding\TokenBindingHandler;

final class AuthenticatorAttestationResponseValidator extends BaseAuthenticatorAttestationResponseValidator
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(AttestationStatementSupportManager $attestationStatementSupportManager, PublicKeyCredentialSourceRepository $publicKeyCredentialSource, TokenBindingHandler $tokenBindingHandler, ExtensionOutputCheckerHandler $extensionOutputCheckerHandler, EventDispatcherInterface $eventDispatcher, bool $enforceMetadataStatementVerification = false)
    {
        parent::__construct($attestationStatementSupportManager, $publicKeyCredentialSource, $tokenBindingHandler, $extensionOutputCheckerHandler, $enforceMetadataStatementVerification);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function check(AuthenticatorAttestationResponse $authenticatorAttestationResponse, PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, ServerRequestInterface $request): PublicKeyCredentialSource
    {
        try {
            $result = parent::check($authenticatorAttestationResponse, $publicKeyCredentialCreationOptions, $request);
            $this->eventDispatcher->dispatch(new AuthenticatorAttestationResponseValidationSucceededEvent(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                $request,
                $result
            ));

            return $result;
        } catch (Throwable $throwable) {
            $this->eventDispatcher->dispatch(new AuthenticatorAttestationResponseValidationFailedEvent(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                $request,
                $throwable
            ));

            throw $throwable;
        }
    }
}
