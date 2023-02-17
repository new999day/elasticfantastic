<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/vendor/autoload.php';

use ES\ES;

/**
 * Временный конфиг
 */
const CONFIG = [
    'hosts'   => [
        'host:9206',
    ],
    'types'   => [
        'advert'         => 'advert',
        'comment'        => 'comment',
        'suggestion'     => 'suggestion',
        'region'         => 'region',
        'region-polygon' => 'region-polygon',
    ],
    'indexes' => [
        'advert'         => 'adverts',
        'comment'        => 'comments',
        'suggestion'     => 'suggestions',
        'region'         => 'regions',
        'region-polygon' => 'region-polygons',
    ],
];

//инициализация синглтона ESB
ES::init(CONFIG);

$result = ES::search('advert')
    ->query(
        ES::and(
            null,
            ES::where('system_data.storage_id', 'archive'),
            ES::where('system_data.free_weeks', [2,3])
        )
    )
    ->limit(0, 0)
    ->aggs(
        ES::aggs(
            ES::aggsTerm('emails', 'email', 20000),
            ES::aggs(
                ES::aggsMax('ids', 'id')
            )
        )
    )
    ->sort(['id' => 'desc'])
    ->fields(['id', 'data.email'])
    ->result();

//header('content-type: application/json');
//echo json_encode($search->query->toArray()); die();

dd($result->getResponse()->getData());

header('content-type: application/json');
echo json_encode($result);
