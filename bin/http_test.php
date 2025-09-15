<?php

function http_request(string $method, string $url, array $payload = []): array {
    $opts = [
        'http' => [
            'method' => strtoupper($method),
            'header' => "Content-Type: application/json\r\n",
            'ignore_errors' => true,
            'timeout' => 10,
        ]
    ];

    if (!empty($payload)) {
        $opts['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create($opts);
    $body = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? 'HTTP/1.1 000';
    preg_match('#\s(\d{3})#', $statusLine, $m);
    $status = isset($m[1]) ? (int)$m[1] : 0;

    return [
        'status' => $status,
        'body' => $body,
    ];
}

$base = 'http://127.0.0.1:8000';

// 1) Criar usuário com dados únicos
$suffix = substr(sha1((string) microtime(true)), 0, 6);
$userPayload = [
    'user_type' => 'PF',
    'full_name' => 'Joao Silva',
    'document' => '123456' . $suffix, // 12 dígitos, suficiente para teste
    'email' => 'joao' . $suffix . '@example.com',
    'password' => '123456',
];
$userResp = http_request('POST', $base . '/api/useraccounts', $userPayload);
echo "Create user => status={$userResp['status']} body={$userResp['body']}\n";

$user = json_decode($userResp['body'] ?? '', true) ?: [];
$accountId = $user['id'] ?? null;

if ($accountId) {
    // 2) Depósito
    $depositPayload = [
        'accountId' => $accountId,
        'amount' => 100.00,
    ];
    $depResp = http_request('POST', $base . '/api/deposit', $depositPayload);
    echo "Deposit => status={$depResp['status']} body={$depResp['body']}\n";
} else {
    echo "Falhou ao obter accountId do usuário criado.\n";
}


