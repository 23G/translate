<?php

namespace DylanLamers\Translate;

use Illuminate\Database\Query\Builder as BaseBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;
use DylanLamers\Translate\Facades\Translate;

class LangScope implements ScopeInterface
{

    /**
     * Apply Scope To Query
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model->translate) {
            $table = $model->getTable();
            $tableSingular = $model->getTableSingular();

            $builder
                ->select($table.'.*')
                ->leftJoin($table.'_lang as translate', function ($join) use ($table, $tableSingular) {
                    $fallbackLanguageId = Translate::getFallbackLanguageId();
                    $languageId = Translate::getLanguageId();

                    $join
                        ->on($table.'.id', '=', 'translate.'.$tableSingular.'_id')
                        ->where('translate.language_id', '=', Translate::getLanguageId());

                    if ($languageId != $fallbackLanguageId) {
                        $join->orWhere('translate.language_id', '=', $fallbackLanguageId);
                    }
                })
                ->addSelect(preg_filter('/^/', 'translate.', $model->translate)); //Prefix with translate alias
        }
    }
}
