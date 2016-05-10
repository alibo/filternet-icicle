<?php

require __DIR__ . '/vendor/autoload.php';

use Icicle\Coroutine;
use Icicle\Dns;
use Icicle\Http\Driver\Reader\Http1Reader;
use Icicle\Http\Message\BasicResponse;
use Icicle\Loop;

$url = $argv[1] ?? 'http://google.com';

$coroutine = Coroutine\create(function () use ($url) {

    $parsedUrl = parse_url($url);

    $path = $parsedUrl['path'] ?? '/';
    $host = $parsedUrl['host'];

    /** @var \Icicle\Socket\Socket $socket */
    $socket = yield from Dns\connect($host, 80);

    $request = "GET {$path} HTTP/1.1\r\n";
    $request .= "Host: {$host}\r\n";
    $request .= "Connection: close\r\n";
    $request .= "\r\n";

    yield from $socket->write($request);

    /** @var BasicResponse $response */
    $response = yield from (new Http1Reader())->readResponse($socket);

    echo $response->getStatusCode() . ' ' . $response->getReasonPhrase() . PHP_EOL;

    echo json_encode($response->getHeaders(), JSON_PRETTY_PRINT) . PHP_EOL;

    $body = $response->getBody();

    if ($body->isReadable()) {
        $content = yield from $body->read(196);
        echo $content . PHP_EOL;
    }
});
$coroutine
    ->capture(function (Exception $e) {
        echo "Exception: {$e->getMessage()}\n";
    }
    )->done();
Loop\run();