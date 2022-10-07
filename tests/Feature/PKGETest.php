<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ParcelTrap\Contracts\Factory;
use ParcelTrap\DTOs\TrackingDetails;
use ParcelTrap\Enums\Status;
use ParcelTrap\ParcelTrap;
use ParcelTrap\PKGE\PKGE;

it('can add the PKGE driver to ParcelTrap', function () {
    /** @var ParcelTrap $client */
    $client = $this->app->make(Factory::class);

    $client->extend('pkge_other', fn () => new PKGE(
        apiKey: 'abcdefg'
    ));

    expect($client)->driver(PKGE::IDENTIFIER)->toBeInstanceOf(PKGE::class)
        ->and($client)->driver('pkge_other')->toBeInstanceOf(PKGE::class);
});

it('can retrieve the PKGE driver from ParcelTrap', function () {
    expect($this->app->make(Factory::class)->driver(PKGE::IDENTIFIER))->toBeInstanceOf(PKGE::class);
});

it('can call `find` on the PKGE driver', function () {
    $trackingDetails = [
        'tracking_number' => 'ABCDEFG12345',
        'status' => 'transit',
        'estimated_delivery' => '2022-01-01T00:00:00+00:00',
    ];

    $httpMockHandler = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode($trackingDetails)),
    ]);

    $handlerStack = HandlerStack::create($httpMockHandler);

    $httpClient = new Client([
        'handler' => $handlerStack,
    ]);

    $this->app->make(Factory::class)->extend(PKGE::IDENTIFIER, fn () => new PKGE(
        apiKey: 'abcdefg',
        client: $httpClient,
    ));

    expect($this->app->make(Factory::class)->driver('pkge')->find('ABCDEFG12345'))
        ->toBeInstanceOf(TrackingDetails::class)
        ->identifier->toBe('ABCDEFG12345')
        ->status->toBe(Status::In_Transit)
        ->status->description()->toBe('In Transit')
        ->summary->toBe('Package status is: In Transit')
        ->estimatedDelivery->toEqual(new DateTimeImmutable('2022-01-01T00:00:00+00:00'))
        ->raw->toBe($trackingDetails);
});
