<?php
namespace DataTables\Adapters;

use Phalcon\Paginator\Adapter\QueryBuilder as PQueryBuilder;

class QueryBuilder extends AdapterInterface
{
    protected $builder;
    protected $global_search;
    protected $column_search;
    protected $_bind;

    public function setBuilder($builder)
    {
        $this->builder = $builder;
    }

    public function getResponse()
    {
        $builder = new PQueryBuilder([
            'builder' => $this->builder,
            'limit' => 1,
            'page' => 1,
        ]);

        $total = $builder->getPaginate();
        $this->global_search = [];
        $this->column_search = [];
        $this->_bind = [];

        $this->bind('global_search', function ($column, $search) {
            $key = "keyg_" . str_replace(".", "", $column);
            $this->global_search[] = "{$column} LIKE :{$key}:";
            $this->_bind[$key] = "%{$search}%";
        });

        $this->bind('column_search', function ($column, $search) {
            $key = "keyc_" . str_replace(" ", "", str_replace(".", "", $column));
            $this->column_search[] = "{$column} LIKE :{$key}:";
            $this->_bind[$key] = "%{$search}%";
        });

        $this->bind('order', function ($order) {
            if (!empty($order)) {
                $this->builder->orderBy(implode(', ', $order));
            }
        });

        if (!empty($this->global_search) || !empty($this->column_search)) {
            $where = implode(' OR ', $this->global_search);
            if (!empty($this->column_search))
                $where = (empty($where) ? '' : ('(' . $where . ') AND ')) . implode(' AND ', $this->column_search);
            $this->builder->andWhere($where, $this->_bind);
        }

        $builder = new PQueryBuilder([
            'builder' => $this->builder,
            'limit' => $this->parser->getLimit($total->total_items),
            'page' => $this->parser->getPage(),
        ]);

        $filtered = $builder->getPaginate();

        return $this->formResponse([
            'total' => $total->total_items,
            'filtered' => $filtered->total_items,
            'data' => $filtered->items->toArray(),
        ]);
    }
}
