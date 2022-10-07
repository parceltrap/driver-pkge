<?php

declare(strict_types=1);

namespace ParcelTrap\PKGE;

use DateTimeImmutable;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use ParcelTrap\Contracts\Driver;
use ParcelTrap\DTOs\TrackingDetails;
use ParcelTrap\Enums\Status;

class PKGE implements Driver
{
    public const IDENTIFIER = 'pkge';

    public const BASE_URI = 'https://api.pkge.net/v1';

    private ClientInterface $client;

    public function __construct(private readonly string $apiKey, ?ClientInterface $client = null)
    {
        $this->client = $client ?? GuzzleFactory::make(['base_uri' => self::BASE_URI]);
    }

    public function find(string $identifier, array $parameters = []): TrackingDetails
    {
        $response = $this->client->request('GET', 'packages', [
            RequestOptions::HEADERS => $this->getHeaders(),
            RequestOptions::QUERY => array_merge(['trackNumber' => $identifier], $parameters),
        ]);

        if ($response->getStatusCode() === 404) {
            return new TrackingDetails(
                identifier: $identifier,
                status: Status::Not_Found,
                events: [],
                raw: [],
            );
        }

        /** @var array $json */
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        assert(isset($json['code']) && $json['code'] === 200, "An error occurred with the returned status code: {$json['code']}");
        assert(isset($json['payload']), 'No shipment could be found with this id');

        $json = $json['payload'];

        assert(isset($json['track_number']), 'The shipment tracking number is missing from the response');
        assert(isset($json['status']), 'The status is missing from the response');
        assert(isset($json['last_status']), 'The summary is missing from the response');
        assert(isset($json['checkpoints']), 'The events array is missing from the response');

        return new TrackingDetails(
            identifier: $json['track_number'],
            status: $this->mapStatus($json['status'] ?? -1),
            summary: $json['last_status'] ?? null,
            estimatedDelivery: $json['est_delivery_date_from'] ? new DateTimeImmutable($json['est_delivery_date_from']) : null,
            events: $json['checkpoints'] ?? [],
            raw: $json,
        );
    }

    private function mapStatus(int $status): Status
    {
        return match ($status) {
            0, 1, 2 => Status::Pending,
            8 => Status::Pre_Transit,
            3, 4 => Status::In_Transit,
            5 => Status::Delivered,
            6, 7 => Status::Failure,
            default => Status::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function getHeaders(array $headers = []): array
    {
        return array_merge([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ], $headers);
    }
}
