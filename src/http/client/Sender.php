<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\http\client;

use froq\http\client\curl\{Curl, CurlMulti};

/**
 * Sender class used in client instances for sending single/multi requests.
 *
 * @package froq\http\client
 * @class   froq\http\client\Sender
 * @author  Kerem Güneş
 * @since   3.0, 4.0
 * @static
 */
class Sender extends \StaticClass
{
    /**
     * Send a request with a single client.
     *
     * @param  froq\http\client\Client $client
     * @return froq\http\client\Response
     */
    public static function send(Client $client): Response
    {
        $curl = new Curl($client);
        $client->setCurl($curl);
        $client->sent = true;

        $curl->run();

        return $client->getResponse();
    }

    /**
     * Send multi requests with multi clients.
     *
     * @param  array<froq\http\client\Client> $clients
     * @return array<froq\http\client\Response>
     */
    public static function sendMulti(array $clients): array
    {
        foreach ($clients as $client) {
            $client->setCurl(new Curl($client));
            $client->sent = true;
        }

        $curlMulti = new CurlMulti($clients);
        $curlMulti->run();

        $responses = [];

        foreach ($curlMulti->getClients() as $client) {
            $responses[] = $client->getResponse();
        }

        return $responses;
    }
}
