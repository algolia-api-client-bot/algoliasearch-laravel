<?php namespace Algolia\AlgoliasearchLaravel;

Trait AlgoliaEloquentTrait
{
    private static $method_get_name = 'getAlgoliaRecord';

    public $auto_index = true;
    public $auto_delete = true;

    /**
     * Static calls
     */

    public function _reindex()
    {
        /** @var \Algolia\AlgoliasearchLaravel\ModelHelper $model_helper */
        $model_helper = \App::make('\Algolia\AlgoliasearchLaravel\ModelHelper');

        $indices = $model_helper->getIndices($this);

        static::chunk(100, function ($models) use ($indices, $model_helper) {
            /** @var \AlgoliaSearch\Index $index */
            foreach ($indices as $index)
            {
                $records = [];

                foreach ($models as $model)
                    if ($model_helper->indexOnly($model, $index->indexName))
                        $records[] = $model->getAlgoliaRecordDefault();

                $index->addObjects($records);
            }

        });
    }

    public function _clearIndices()
    {
        /** @var \Algolia\AlgoliasearchLaravel\ModelHelper $model_helper */
        $model_helper = \App::make('\Algolia\AlgoliasearchLaravel\ModelHelper');

        $indices = $model_helper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index)
            $index->clearIndex();
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static();

        $method = '_'.$method;

        if (method_exists($instance, $method));
            return $instance->$method();

        return parent::__callStatic($method, $parameters);
    }

    /**
     * Methods
     */

    public function getAlgoliaRecordDefault()
    {
        /** @var \Algolia\AlgoliasearchLaravel\ModelHelper $model_helper */
        $model_helper = \App::make('\Algolia\AlgoliasearchLaravel\ModelHelper');

        $record = null;

        if (method_exists($this, static::$method_get_name))
            $record = $this->{static::$method_get_name}();
        else
            $record = $this->toArray();

        if (isset($record['objectID']) == false)
            $record['objectID'] = $model_helper->getObjectIdKey($this);

        return $record;
    }

    public function pushToindex()
    {
        /** @var \Algolia\AlgoliasearchLaravel\ModelHelper $model_helper */
        $model_helper = \App::make('\Algolia\AlgoliasearchLaravel\ModelHelper');

        $indices = $model_helper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index)
            if ($model_helper->indexOnly($this, $index->indexName))
                $index->addObject($this->_getAlgoliaRecord());
    }

    public function removeFromIndex()
    {
        /** @var \Algolia\AlgoliasearchLaravel\ModelHelper $model_helper */
        $model_helper = \App::make('\Algolia\AlgoliasearchLaravel\ModelHelper');

        $indices = $model_helper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index)
            $index->deleteObject($this->id);
    }


}