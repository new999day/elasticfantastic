<?php
namespace ES;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\Max as AggMax;
use Elastica\Aggregation\Terms as AggTerms;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use Elastica\Query\Wildcard;
use Elastica\QueryBuilder\DSL\Aggregation;
use Elastica\QueryBuilder\DSL\Query;

/**
 * Фасад для Elastic-билдера
 *
 * @package ES
 */
class ES
{
    /**
     * Инициализация синглтона ESB
     *
     * @param array $config
     */
    public static function init(array $config){
        ESB::getInstance($config);
    }

    /**
     * Поиск по указанному типу
     *
     * @param  string  $type
     * @return \ES\ESB
     */
    public static function search($type) : ESB
    {
        $esb = ESB::getInstance();
        $esb->search
            ->setIndexes($esb->getIndexByType($type))
            ->setType($esb->getTypeByName($type));

        return $esb;
    }

    /**
     * Формирование запроса
     *
     * @param  \Elastica\Query\BoolQuery ...$filters
     * @return \Elastica\Query\BoolQuery
     */
    public static function query(BoolQuery ...$filters) : BoolQuery
    {
        $esb   = ESB::getInstance();
        $query = $esb->builder->query()->bool();

        foreach ($filters as $filter) {
            $key = key($filter->getParams());
            $query->setParam($key, $filter->getParam($key));
        }

        return $query;
    }

    /**
     * Добавляет must в тело запроса
     *
     * @param  mixed                     ...$terms
     * @return \Elastica\Query\BoolQuery
     */
    public static function and(...$terms) : BoolQuery
    {
        return (new BoolQuery())->setParam('must', self::clearEmptyArgs($terms));
    }

    /**
     * Добавляет should в тело запроса
     *
     * @param  mixed                     ...$terms
     * @return \Elastica\Query\BoolQuery
     */
    public static function or(...$terms) : BoolQuery
    {
        return (new BoolQuery())->setParam('should', self::clearEmptyArgs($terms));
    }

    /**
     * Добавляет must_not в тело запроса
     *
     * @param  mixed                     ...$terms
     * @return \Elastica\Query\BoolQuery
     */
    public static function not(...$terms) : BoolQuery
    {
        return (new BoolQuery())->setParam('must_not', self::clearEmptyArgs($terms));
    }

    /**
     * "Универсальный" поиск по условию
     *
     * @param  string                        $field
     * @param  mixed                         $value
     * @param  string                        $operator
     * @return \Elastica\Query\AbstractQuery
     */
    public static function where(string $field, $value, $operator = '=') : AbstractQuery
    {
        switch ($operator) {
            case '=':
                if (\is_array($value)) {
                    return self::terms($field, $value);
                }

                return self::term($field, $value);
            case '>':
                return self::condition()->range($field, ['gt' => $value]);
            case '<':
                return self::condition()->range($field, ['lt' => $value]);
            case '>=':
                return self::condition()->range($field, ['gte' => $value]);
            case '<=':
                return self::condition()->range($field, ['lte' => $value]);
            case 'exists':
                return self::condition()->exists($field);
            case 'in':
                return self::terms($field, $value);
            case 'like':
                return self::condition()->match($field, $value);
            default:
                throw new \InvalidArgumentException('Invalid operator');
        }
    }

    /**
     * Добавляет term в тело запроса
     *
     * @param string                $field
     * @param mixed                 $value
     * @return \Elastica\Query\Term
     */
    public static function term(string $field, $value) : Term
    {
        return (new Term())->setTerm($field, $value);
    }

    /**
     * Добавляет terms в тело запроса
     *
     * @param string                $field
     * @param mixed                 $value
     * @return \Elastica\Query\Terms
     */
    public static function terms(string $field, $value) : Terms
    {
        return (new Terms())->setTerms($field, $value);
    }

    /**
     * Добавляет wildcard в тело запроса
     *
     * @param  string                   $field
     * @param  string                   $value
     * @return \Elastica\Query\Wildcard
     */
    public static function wildcard(string $field, string $value) : Wildcard
    {
        return (new Wildcard())->setValue($field, $value);
    }

    /**
     * Универсальный вызов всех возможных типов фильтров
     *
     * @return \Elastica\QueryBuilder\DSL\Query
     */
    public static function condition() : Query
    {
        return new Query();
    }

    /**
     * Добавляет контейнер для описания агрегации
     *
     * @param  \Elastica\Aggregation\AbstractAggregation      $aggregation
     * @param  \Elastica\Aggregation\AbstractAggregation|null $nextLevel
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public static function aggs(AbstractAggregation $aggregation, $nextLevel = null) : AbstractAggregation
    {
        if ($nextLevel) {
            $aggregation->addAggregation($nextLevel);
        }

        return $aggregation;
    }

    /**
     * Универсальный вызов всех возможных типов агрегаций
     *
     * @return \Elastica\QueryBuilder\DSL\Aggregation
     */
    public static function agg() : Aggregation
    {
        return new Aggregation();
    }

    /**
     * Добавляет агрегацию типа Term
     *
     * @param  string                                     $name
     * @param  string                                     $field
     * @param  int                                        $size
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public static function aggsTerm($name, $field, $size = 10) : AbstractAggregation
    {
        return (new AggTerms($name))->setField($field)->setSize($size);
    }

    /**
     * Добавляет агрегацию типа Max
     *
     * @param  string                                    $name
     * @param  string                                    $field
     * @return \Elastica\Aggregation\AbstractAggregation
     */
    public static function aggsMax(string $name, string $field) : AbstractAggregation
    {
        return (new AggMax($name))->setField($field);
    }

    /**
     * Очищает пустые аргументы
     *
     * @param  array $args
     * @return array
     */
    protected static function clearEmptyArgs(array $args) : array
    {
        $cleanedArgs = [];

        foreach ($args as $arg) {
            if (null !== $arg) {
                $cleanedArgs[] = $arg;
            }
        }

        return $cleanedArgs;
    }
}
