<?php
namespace ES;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\QueryBuilder;
use Elastica\ResultSet;
use Elastica\Scroll;
use ES\Search;

/**
 * Основной класс Elastic-билдера
 *
 * @package ES
 */
class ESB
{
    /**
     * Хранит инстанс самого себя
     *
     * @var \ES\ESB
     */
    private static $instance;

    /**
     * Конфиг
     *
     * @var array
     */
    private $config;

    /**
     * Класс клиента от Elastica
     *
     * @var \Elastica\Client
     */
    public $client;

    /**
     * Класс билдера от Elastica
     *
     * @var \Elastica\QueryBuilder
     */
    public $builder;

    /**
     * Класс запроса от Elastica
     *
     * @var \Elastica\Query
     */
    public $query;

    /**
     * Класс поиска
     *
     * @var \ES\Search
     */
    public $search;

    /**
     * Возвращает экземпляр класса
     *
     * @param  array   $config
     * @return \ES\ESB
     * @throws \InvalidArgumentException
     */
    public static function getInstance(array $config = []) : self
    {
        if (null === static::$instance) {
            $instance = new static();
            $instance->config  = $config;
            $instance->client  = new Client(['url' => self::getHostUrl($config)]);
            $instance->query   = new Query();
            $instance->builder = new QueryBuilder();
            $instance->search  = new Search($instance->client);

            static::$instance = $instance;
        }

        return static::$instance;
    }

    /**
     * Возвращает имя индекса по типу
     *
     * @param  string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getIndexByType(string $type) : string
    {
        if (!isset($this->config['indexes'][$type])) {
            throw new \InvalidArgumentException('Неизвестный тип ' . $type);
        }

        return $this->config['indexes'][$type];
    }

    /**
     * Возвращает тип из конфига по имени
     *
     * @param  string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getTypeByName(string $type) : string
    {
        if (!isset($this->config['types'][$type])) {
            throw new \InvalidArgumentException('Неизвестный тип ' . $type);
        }

        return $this->config['types'][$type];
    }

    /**
     * Формирование запроса из массива параметров
     *
     * @param  array   $query
     * @return \ES\ESB
     */
    public function queryArray(array $query = []) : self
    {
        $this->search->setQuery($query);

        return $this;
    }

    /**
     * Формирование запроса
     *
     * @param  \Elastica\Query\BoolQuery ...$args
     * @return \ES\ESB
     */
    public function query(BoolQuery ...$args) : self
    {
        $query = $this->builder->query()->bool();

        foreach ($args as $arg) {
            $key = key($arg->getParams());
            $query->setParam($key, $arg->getParam($key));
        }

        $this->query->setQuery($query);
        $this->search->setQuery($this->query);

        return $this;
    }

    /**
     * Формирование агрегаций
     *
     * @param  \Elastica\Aggregation\AbstractAggregation ...$args
     * @return \ES\ESB
     */
    public function aggs(AbstractAggregation ...$args) : self
    {
        foreach ($args as $arg) {
            $this->query->addAggregation($arg);
        }

        return $this;
    }

    /**
     * Устанавливает лимит и смещение
     *
     * @param  int     $size
     * @param  int     $from
     * @return \ES\ESB
     */
    public function limit($size = 0, $from = 0) : self
    {
        $this
            ->query
            ->setFrom($from)
            ->setSize($size);

        return $this;
    }

    /**
     * Получение результата поиска с помощью скрола
     *
     * @param  string           $expireTime
     * @return \Elastica\Scroll
     */
    public function scroll(string $expireTime = '1m') : Scroll
    {
        return $this->search->scroll($expireTime);
    }

    /**
     * Устанавливает поля, которые необходимо вернуть в результате
     *
     * @param  array   $fields
     * @return \ES\ESB
     */
    public function fields(array $fields) : self
    {
        $this->query->setStoredFields($fields);

        return $this;
    }

    /**
     * Устанавливает сортировку
     *
     * @param  array   $sorting
     * @return \ES\ESB
     */
    public function sort(array $sorting) : self
    {
        $this->query->setSort($sorting);

        return $this;
    }

    /**
     * Возвращает результат
     *
     * @return \Elastica\ResultSet
     */
    public function result() : ResultSet
    {
        return $this->search->search();
    }

    /**
     * Возвращает хост из конфига
     *
     * @param  array  $config
     * @return string
     */
    protected static function getHostUrl(array $config) : string
    {
        if (!isset($config['hosts'][0])) {
            throw new \InvalidArgumentException('Конфиг должен содержать хотябы один хост');
        }

        return $config['hosts'][0] . '/';
    }

    /**
     * Экземпляр этого класса не может быть создан через конструктор
     */
    protected function __construct() {}

    /**
     * Экземпляр этого класса не может быть склонирован
     */
    protected function __clone() {}
}
