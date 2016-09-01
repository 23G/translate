<?php

namespace DylanLamers\Translate;

use Illuminate\Database\Query\Builder as BaseBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use DylanLamers\Translate\Facades\Translate;

class LangScope implements Scope
{

    /**
     * Apply Scope To Query
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($translateAttributes = $model->getTranslateableAttributes()) {
            $table = $model->getTable();
            $tableSingular = $model->getTableSingular();
            $fallbackLanguageId = Translate::getFallbackLanguageId();
            $languageId = Translate::getLanguageId();

            /**
             * Laravel Join and Subquery wasn't playing nicely, so had to make the raw query. The languageId and fallbackLanguageId are always int so no need escaping.
             */
            $sql = false;
            if ($fallbackLanguageId && $fallbackLanguageId !== $languageId) {
                $sql = '(select `'.$table.'_lang`.`id` from `'.$table.'_lang` where `'.$tableSingular.'_id` = `'.$table.'`.`id` and (`language_id` = '.$languageId.' or `language_id` = '.$fallbackLanguageId.') order by (`language_id` = '.$languageId.') desc limit 1)';
            }

            $builder
                ->select($table.'.*')
                ->leftJoin($table.'_lang as translate', function ($join) use ($table, $tableSingular, $sql, $languageId) {

                    if ($sql) {
                        $join
                            ->on('translate.id', '=', \DB::raw($sql));
                    } else {
                        $join
                        ->on($table.'.id', '=', 'translate.'.$tableSingular.'_id')
                        ->where('translate.language_id', '=', $languageId);
                    }
                })
                ->addSelect(preg_filter('/^/', 'translate.', $translateAttributes)); //Prefix with translate alias
        }
    }
}
