<?php
// api/v1/dashboardusers/order/create.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../controller/dashboardusers/order/OrderController.php';


/**
 * =====================================================
 * HELPER RESPONSE
 * =====================================================
 */
function responseJson(
    $statusCode,
    $data
) {
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/**
 * =====================================================
 * METHOD HARUS POST
 * =====================================================
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    responseJson(
        405,
        [
            'status' => 'error',
            'code' => 405,
            'message' =>
                'Method tidak diperbolehkan'
        ]
    );
}


/**
 * =====================================================
 * AMBIL JSON BODY
 * =====================================================
 */
$rawInput =
    file_get_contents('php://input');


$data =
    json_decode(
        $rawInput,
        true
    );


if (!is_array($data)) {

    $data = $_POST;
}


/**
 * =====================================================
 * USER ID
 *
 * Untuk sementara ambil dari request.
 *
 * Nanti bisa diganti dengan hasil
 * autentikasi/token.
 * =====================================================
 */
$userId =
    $data['user_id']
    ?? $_POST['user_id']
    ?? null;


if (empty($userId)) {

    responseJson(
        401,
        [
            'status' => 'error',
            'code' => 401,
            'message' =>
                'User ID wajib diisi'
        ]
    );
}


/**
 * =====================================================
 * SERVICE CATEGORY
 *
 * website / iklan
 * =====================================================
 */
$serviceCategory =
    strtolower(
        trim(
            $data['service_category']
            ?? 'website'
        )
    );


/**
 * =====================================================
 * BILLING PERIOD
 *
 * yearly / monthly / weekly
 * =====================================================
 */
$billingPeriod =
    strtolower(
        trim(
            $data['billing_period']
            ?? 'yearly'
        )
    );


/**
 * =====================================================
 * CONTROLLER
 * =====================================================
 */
try {

    $controller =
        new OrderController();


    $result =
        $controller->createOrder(
            $userId,
            $serviceCategory,
            $billingPeriod
        );


    responseJson(
        $result['code'] ?? 500,
        $result
    );


} catch (Throwable $e) {

    responseJson(
        500,
        [
            'status' => 'error',
            'code' => 500,
            'message' =>
                'Terjadi kesalahan server',

            // Untuk development
            'error' =>
                $e->getMessage()
        ]
    );
}