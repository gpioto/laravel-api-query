<?php

namespace Pioto\Laravel\Api;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiQueryScope implements Scope
{

    private $request;

    public function __construct(Request $request){
        $this->request = $request;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $availableMethods = [
            'where',
            'whereIn',
            'whereNotIn',
            'whereNull',
            'whereNotNull',
            'whereBetween',
            'whereNotBetween',
            // 'orWhere',
            // 'orWhereIn',
            // 'orWhereNotIn',
            // 'orWhereNull',
            // 'orWhereNotNull',
            // 'orWhereBetween',
            // 'orWhereNotBetween',
            'orderBy',
            'with',
            'select',         
        ];
        
        $methods = $this->request->intersect($availableMethods);
        foreach($methods as $method => $search){
            $fields = explode(';', $search);
            
            if (stripos($method, 'where') !== false) {
                foreach ($fields as $row) {
                    $params = explode(':', $row);
                    $field = $params[0];
                    $condition = $params[1];
                    $value = isset($params[2]) ? $params[2] : $params[1];
                    $relation = null;
                    if(stripos($field, '.')) {
                        $explode = explode('.', $field);
                        $params[0] = array_pop($explode);
                        $relation = implode('.', $explode);
                    }
                    if(stripos($method, 'in') !== false || stripos($method, 'between') !== false){
                        $params[1] = explode(',', $value);
                        unset($params[2]);
                    }
                    if(stripos($method, 'null') !== false){
                        unset($params[1]);
                        unset($params[2]);
                    }

                    if($relation){
                        $builder->whereHas($relation, function($query) use ($method, $params){
                            $query->$method(...$params);
                        });
                    } else {
                        $builder->$method(...$params);
                    }
                }
            } else {
                switch($method) {
                    case 'select':
                    case 'with':
                    // case 'orderBy':
                        $builder->{$method}($fields);
                        break;
                }
            }
        }
    }
}