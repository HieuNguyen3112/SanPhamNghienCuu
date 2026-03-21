<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$requests = [
    '/api/public/research-works?work_type=ARTICLE&page=1&page_size=10',
    '/api/public/research-works?work_type=BOOK&page=1&page_size=10',
    '/api/public/research-works/lookups',
];
foreach ($requests as $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    echo "URI={$uri}\n";
    echo "STATUS=" . $response->getStatusCode() . "\n";
    echo substr((string) $response->getContent(), 0, 400) . "\n---\n";
    $kernel->terminate($request, $response);
}