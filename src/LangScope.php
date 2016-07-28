<?php 

namespace DylanLamers\Translate;

use Illuminate\Database\Query\Builder as BaseBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;
use DylanLamers\Translate\Translate;

class LangScope implements ScopeInterface {

    /**
     * Apply Scope To Query
     * @param Builder $builder 
     * @param Model $model 
     * @return void
     */
    public function apply(Builder $builder, Model $model){
        $table = $model->getTable();
        $tableSingular = $model->getTableSingular();

        $builder->leftJoin($table.'_lang as lang', function($join) use($translate){
            $join
                ->on($table.'.id', '=', 'lang.'.$tableSingular.'_id')
                ->where('lang.language_id', '=', $translate->getLanguageId())
                ->orWere('lang.language_id', '=', $translate->getFallbackLanguageId());
        });
    }

}