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
            $fallbackLanguageId = Translate::getFallbackLanguageId();
            $languageId = Translate::getLanguageId();

            /**
             * Laravel Join and Subquery wasn't playing nicely, so had to make the raw query. The languageId and fallbackLanguageId are always int so no need escaping.
             */
            $sql = '(select `texts_lang`.`id` from `texts_lang` where `text_id` = `texts`.`id` and (`language_id` = '.$languageId.' or `language_id` = '.$fallbackLanguageId.') order by (`language_id` = '.$languageId.') desc limit 1)';

            $builder
                ->select($table.'.*')
                ->leftJoin($table.'_lang as translate', function ($join) use ($table, $tableSingular, $sql) {
                    $join
                        ->on('translate.id', '=', \DB::raw($sql));
                })
                ->addSelect(preg_filter('/^/', 'translate.', $model->translate)); //Prefix with translate alias
        }
    }
}
