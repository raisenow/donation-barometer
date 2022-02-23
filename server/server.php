<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

############ Begin Configuration ############

const RAISENOW_MERCHANT_IDENTIFIER = '';
const RAISENOW_API_USERNAME = '';
const RAISENOW_API_PASSWORD = '';

############ End Configuration ############


$client = new GuzzleHttp\Client();
$response = $client->request(
    'GET',
    sprintf(
        'https://api.raisenow.com/epayment/api/%s/transactions/search',
        RAISENOW_MERCHANT_IDENTIFIER
    ),
    [
        'auth' => [
            RAISENOW_API_USERNAME,
            RAISENOW_API_PASSWORD
        ],
        'query' => [
            'sort' => [
                [
                    'field_name' => 'created',
                    'order' => 'desc'
                ]
            ],
            'filters' => [
                [
                    'field_name' => 'last_status',
                    'type' => 'term',
                    'value' => 'final_success'
                ],
                [
                    'field_name' => 'test_mode',
                    'type' => 'term',
                    'value' => 'production'
                ],
                [
                    'field_name' => 'currency_identifier',
                    'type' => 'term',
                    'value' => 'chf'
                ],
            ],
            'facets' => [
                [
                    'field_name' => 'amount',
                    'type' => 'stats',
                    'name' => 'total_amount'
                ]
            ],
            'displayed_fields' => 'epp_transaction_id,created,currency_identifier,amount,payment_method_identifier',
            'records_per_page' => 5
        ]
    ]
);

$body = $response->getBody()->getContents();
$body = json_decode($body, true);

// A few notes:
// - The response will contain an array of facets, each having a name equal to the name
//   you have specified in the query
// - The total amount returned by the facet is in cents.

$totalSum = $body['result']['facets'][0]['stats']['total'] / 100;


