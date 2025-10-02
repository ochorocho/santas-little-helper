<?php

declare(strict_types = 1);

namespace Ochorocho\SantasLittleHelper\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GerritService
{
    private HttpClientInterface $client;
    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    /**
     * @param string $username
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function getGerritUserData(string $username): array
    {
        $request = $this->client->request(
            'GET',
            'https://review.typo3.org/accounts/' . urlencode($username) . '/?pp=0'
        );

        if($request->getStatusCode() > 200) {
            throw new \RuntimeException('The given username "' . $username . '" was not found on https://review.typo3.org/');
        }

        // Gerrit does not return valid JSON using their JSON API
        // therefore we need to chop off the first line
        // Sounds weird? See why https://gerrit-review.googlesource.com/Documentation/rest-api.html#output
        $validJson = str_replace(')]}\'', '', $request->getContent());

        return json_decode($validJson, true, 512, JSON_THROW_ON_ERROR);
    }
}