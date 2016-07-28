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
                ->leftJoin($table.'_lang as lang', function ($join) use ($table, $tableSingular) {
                    $fallbackLanguageId = Translate::getFallbackLanguageId();
                    $languageId = Translate::getLanguageId();

                    $join
                        ->on($table.'.id', '=', 'lang.'.$tableSingular.'_id')
                        ->where('lang.language_id', '=', Translate::getLanguageId());

                    if ($languageId != $fallbackLanguageId) {
                        $join->orWhere('lang.language_id', '=', $fallbackLanguageId);
                    }
                })
                ->addSelect(preg_filter('/^/', 'lang.', $model->translate));
        }
    }
}
