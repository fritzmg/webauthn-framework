<?php

declare(strict_types=1);

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Controller\AssertionControllerFactory;
use Webauthn\Bundle\Controller\AttestationControllerFactory;
use Webauthn\Bundle\Controller\DummyControllerFactory;
use Webauthn\Bundle\Repository\DummyPublicKeyCredentialSourceRepository;
use Webauthn\Bundle\Repository\DummyPublicKeyCredentialUserEntityRepository;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\Bundle\Routing\Loader;
use Webauthn\Bundle\Service\DefaultFailureHandler;
use Webauthn\Bundle\Service\DefaultSuccessHandler;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\Denormalizer\AttestationObjectDenormalizer;
use Webauthn\Denormalizer\AttestationStatementDenormalizer;
use Webauthn\Denormalizer\AttestedCredentialDataNormalizer;
use Webauthn\Denormalizer\AuthenticationExtensionNormalizer;
use Webauthn\Denormalizer\AuthenticationExtensionsDenormalizer;
use Webauthn\Denormalizer\AuthenticatorAssertionResponseDenormalizer;
use Webauthn\Denormalizer\AuthenticatorAttestationResponseDenormalizer;
use Webauthn\Denormalizer\AuthenticatorDataDenormalizer;
use Webauthn\Denormalizer\AuthenticatorResponseDenormalizer;
use Webauthn\Denormalizer\CollectedClientDataDenormalizer;
use Webauthn\Denormalizer\ExtensionDescriptorDenormalizer;
use Webauthn\Denormalizer\PublicKeyCredentialDenormalizer;
use Webauthn\Denormalizer\PublicKeyCredentialDescriptorNormalizer;
use Webauthn\Denormalizer\PublicKeyCredentialOptionsDenormalizer;
use Webauthn\Denormalizer\PublicKeyCredentialSourceDenormalizer;
use Webauthn\Denormalizer\PublicKeyCredentialUserEntityDenormalizer;
use Webauthn\Denormalizer\VerificationMethodANDCombinationsDenormalizer;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\SimpleFakeCredentialGenerator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure();

    $container
        ->set(CeremonyStepManagerFactory::class)
    ;

    $container
        ->set('webauthn.clock.default')
        ->class(NativeClock::class)
    ;

    $container
        ->set('webauthn.ceremony_step_manager.creation')
        ->class(CeremonyStepManager::class)
        ->factory([service(CeremonyStepManagerFactory::class), 'creationCeremony'])
        ->args([param('webauthn.secured_relying_party_ids')])
    ;

    $container
        ->set(SimpleFakeCredentialGenerator::class)
        ->args([service(CacheItemPoolInterface::class)->nullOnInvalid()])
    ;

    $container
        ->set('webauthn.ceremony_step_manager.request')
        ->class(CeremonyStepManager::class)
        ->factory([service(CeremonyStepManagerFactory::class), 'requestCeremony'])
        ->args([param('webauthn.secured_relying_party_ids')])
    ;

    $container
        ->set(AuthenticatorAttestationResponseValidator::class)
        ->args([service('webauthn.ceremony_step_manager.creation')])
        ->public();
    $container
        ->set(AuthenticatorAssertionResponseValidator::class)
        ->class(AuthenticatorAssertionResponseValidator::class)
        ->args([service('webauthn.ceremony_step_manager.request')])
        ->public();
    $container
        ->set(PublicKeyCredentialCreationOptionsFactory::class)
        ->args([param('webauthn.creation_profiles')])
        ->public();
    $container
        ->set(PublicKeyCredentialRequestOptionsFactory::class)
        ->args([param('webauthn.request_profiles')])
        ->public();

    $container
        ->set(ExtensionOutputCheckerHandler::class);
    $container
        ->set(AttestationObjectLoader::class)
        ->args([service(AttestationStatementSupportManager::class)]);
    $container
        ->set(AttestationStatementSupportManager::class);
    $container
        ->set(NoneAttestationStatementSupport::class);

    $container
        ->set(ThrowExceptionIfInvalid::class)
        ->autowire(false);

    $container
        ->set(Loader::class)
        ->tag('routing.loader');

    $container
        ->set(AttestationControllerFactory::class)
        ->args([
            service(SerializerInterface::class),
            service(AuthenticatorAttestationResponseValidator::class),
            service(PublicKeyCredentialSourceRepositoryInterface::class),
        ]);
    $container
        ->set(AssertionControllerFactory::class)
        ->args([
            service(SerializerInterface::class),
            service(AuthenticatorAssertionResponseValidator::class),
            service(PublicKeyCredentialSourceRepositoryInterface::class),
        ]);

    $container
        ->set(DummyPublicKeyCredentialSourceRepository::class)
        ->autowire(false);
    $container
        ->set(DummyPublicKeyCredentialUserEntityRepository::class)
        ->autowire(false);

    $container
        ->set(DummyControllerFactory::class);

    $container
        ->set('webauthn.logger.default')
        ->class(NullLogger::class);

    $container
        ->alias('webauthn.http_client.default', HttpClientInterface::class);

    $container
        ->set(VerificationMethodANDCombinationsDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(ExtensionDescriptorDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AttestationObjectDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AttestationStatementDenormalizer::class)
        ->args([service(AttestationStatementSupportManager::class)])
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticationExtensionNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(PublicKeyCredentialDescriptorNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AttestedCredentialDataNormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticationExtensionsDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticatorAssertionResponseDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticatorAttestationResponseDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticatorDataDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(AuthenticatorResponseDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(CollectedClientDataDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(PublicKeyCredentialDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(PublicKeyCredentialOptionsDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(PublicKeyCredentialSourceDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container
        ->set(PublicKeyCredentialUserEntityDenormalizer::class)
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ]);
    $container->set(WebauthnSerializerFactory::class)
        ->args([service(AttestationStatementSupportManager::class)])
    ;
    $container->set(DefaultFailureHandler::class);
    $container->set(DefaultSuccessHandler::class);
};
