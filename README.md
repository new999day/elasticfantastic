#ElasticFantastic
##Библиотека ускоренной работы с запросами в ES

###Обычный запрос в ElasticSearch в стандартном клиенте:
```php
// Аггрегация 20000 email пользователей с максимальным количеством документов в архиве с free_weeks=3 и возможностью сбора последнего ID объявления (MaxAggregation по ID)
$query = [
    'index' => 'adverts',
    'type' => 'advert',
    'from' => 0,
    'size' => 0,
    'body' => [
        'query' => [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'system_data.storage_id' => [
                                'value' => 'archive',
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'system_data.free_weeks' => [
                                'value' => 3,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'aggs' => [
            'emails' => [
                'terms' => [
                    'field' => 'data.email',
                    'size' => 20000,
                ],
                'aggs' => [
                    'ids' => [
                        'max' => [
                            'field' => 'id',
                        ],
                    ],
                ],
            ],
        ],
        'sort' => [
            [
                'id' => 'desc',
            ],
        ],
    ],
    'fields' => [
        'id',
        'data.email',
    ],
];

$result = $this->getElasticSearch->search($query);
```
###Запрос при использовании ElasticFantastic
```php
$result = ES::search('advert')
    ->query(
        ES::must(
            ES::term('system_data.storage_id', 'archive'),
            ES::term('system_data.free_weeks', 3)
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
```