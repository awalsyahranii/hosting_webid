<?php

// api/v1/domain/check.php

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Access-Control-Allow-Origin: *'
);

header(
    'Access-Control-Allow-Methods: POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, Accept'
);

if (
    $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
) {
    http_response_code(204);
    exit;
}

require_once __DIR__ .
    '/../../../controller/dashboardusers/domain/DomainController.php';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' =>
            'Method not allowed'
    ]);

    exit;
}

$rawInput =
    file_get_contents(
        'php://input'
    );

$input =
    json_decode(
        $rawInput ?: '{}',
        true
    );

if (
    !is_array($input)
) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' =>
            'Format JSON tidak valid'
    ]);

    exit;
}

$domain =
    trim(
        (string) (
            $input['domain'] ?? ''
        )
    );

$extension =
    trim(
        (string) (
            $input['extension'] ?? ''
        )
    );

$includeSuggestions =
    isset($input['include_suggestions'])
        ? (bool) $input['include_suggestions']
        : true;

$domainController =
    new DomainController();

$result =
    $domainController->checkDomain(
        $domain,
        $extension,
        $includeSuggestions
    );

http_response_code(
    (int) (
        $result['code'] ?? 500
    )
);

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);