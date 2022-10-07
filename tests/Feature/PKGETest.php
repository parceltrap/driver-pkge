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
        'code' => 200,
        'payload' => [
            'created_at' => '2021-07-06T13:46:25+03:00',
            'updated_at' => '2021-07-06T13:46:25+03:00',
            'started_tracking_at' => '2021-07-06T13:46:25+03:00',
            'track_number' => 'UA937578848US',
            'origin' => null,
            'destination' => null,
            'last_status' => 'Receiving status...',
            'status' => -1,
            'checkpoints' => [
                [
                    'id' => '',
                    'date' => '2021-07-06T13:46:25+03:00',
                    'title' => 'Tracking started',
                    'location' => 'The package most likely isn\'t shipped yet',
                    'latitude' => null,
                    'longitude' => null,
                    'courier_id' => null,
                ],
            ],
            'last_status_date' => '2021-07-06T13:46:25+03:00',
            'est_delivery_date_from' => null,
            'est_delivery_date_to' => null,
            'extra_track_numbers' => [],
            'hash' => 'f25370e9',
            'consolidated_track_number' => null,
            'consolidation_date' => null,
            'destination_country_code' => 'ru',
            'updating' => false,
            'last_tracking_date' => null,
            'days_on_way' => 1,
            'weight' => null,
            'extra_info' => [],
            'couriers_ids' => [
                1,
                4,
                10,
                366,
            ],
            'courier_id' => null,
            'info' => [],
        ],
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

    expect($this->app->make(Factory::class)->driver('pkge')->find('UA937578848US'))
        ->toBeInstanceOf(TrackingDetails::class)
        ->identifier->toBe('UA937578848US')
        ->status->toEqual(Status::Unknown)
        ->status->description()->toBe('Unknown')
        ->summary->toBe('Receiving status...')
        ->estimatedDelivery->toBeNull()
        ->raw->toBe($trackingDetails['payload']);
});
