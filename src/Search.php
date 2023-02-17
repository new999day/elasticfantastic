<?php
namespace ES;

use Elastica\Exception\InvalidException;
use Elastica\Search as ElasticaSearch;
use Elastica\Type;

/**
 * Расширение класса Elastica\Search
 */
class Search extends ElasticaSearch
{
    /**
     * Устанавливает индекс для запроса
     *
     * @param  array|string $indexes
     * @return \ES\Search
     */
    public function setIndexes($indexes) : Search
    {
        if (\is_array($indexes)) {
            $this->_indices = $indexes;
        } else {
            $this->_indices = [$indexes];
        }

        return $this;
    }

    /**
     * Устанавливает тип для запроса
     *
     * @param  \Elastica\Type|string $type
     * @return \ES\Search
     */
    public function setType($type) : Search
    {
        if ($type instanceof Type) {
            $type = $type->getName();
        }

        if (!\is_string($type)) {
            throw new InvalidException('Invalid type type');
        }

        $this->_types = [$type];

        return $this;
    }
}
