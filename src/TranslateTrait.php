<?php

namespace DylanLamers\Translate;

use Illuminate\Database\Eloquent\Builder;
use DylanLamers\Translate\Facades\Translate;

trait TranslateTrait
{

    protected $langId = 0;
    protected $langCode = '';
    protected $langName = '';
    protected $newTranslate = false;
    protected $forceLanguage = false;

    /**
     * Add the joining scope to the model.
     * @return void
     */
    public static function bootTranslateTrait()
    {
        static::addGlobalScope(new LangScope);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->translate)) {
            return parent::getAttribute($key);
        }
        
        return parent::getAttribute($key);
    }

    /**
     * Set a given attribute on the model by the parent function, but also checks if we need insert to the translations table.
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($this->isKeyTranslatable($key) && !array_key_exists($key, $this->original)) {
            $this->newTranslate = true;
        } else if ($this->key === 'language_id') {
            return $this; //We can't change this.
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Force a language code
     * @param string $code
     * @return $this
     */
    public function forceLanguage($code)
    {
        if ($language = Translate::getLanguageByCode($code)) {
            $this->forceLanguage = $language->id;
        }

        return $this;
    }

    /**
     * Get Language Id
     * @return int
     */
    public function getLanguageId()
    {
        return $this->forceLanguage ? $this->forceLanguage : Translate::getLanguageId();
    }

    /**
     * Tells if the key belongs to the model or the translations table.
     * @param string $key
     * @return bool
     */
    public function isKeyTranslatable(string $key)
    {
        return in_array($key, $this->translate);
    }

    /**
     * Save the model to the database using the parent function but also save the changes to the translations table.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        /*
            We just need to update, we have to do this before te parent sets sync the original attributes and the getDirty does not see the change anymore.
        */
        if (!$this->newTranslate && $dirty = $this->getDirty(true)) {
            $table = $this->getTable().'_lang';
            $builder = \DB::table($table)->where($this->getTableSingular().'_id', '=', $this->attributes['id'])->whereLanguageId($this->getLanguageId())->limit(1);

            if ($this->language_id == $this->getLanguageId() || $builder->count()) {
                $builder->update($dirty);
            } else {
                $this->newTranslate = true;
            }
        }

        /*
            Call the parent function and save the attributes belonging to the actual model, the getDirt function is altered to make sure no translateable properties are being saved.
        */
        $returnParent = parent::save();

        /*
            We can do this only after we have the parent function has set the id.
        */
        if ($this->newTranslate) {
            $table = $this->getTable().'_lang';
            $attributes = $this->getAttributes(true);
            $attributes[$this->getTableSingular().'_id'] = $this->attributes['id'];
            $attributes['language_id'] = $this->getLanguageId();

            \DB::table($table)->insert($attributes);

            $this->newTranslate = false;
        }

        $this->forceLanguage = false; // Reset to false after save
        return $returnParent;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty($translations = false)
    {
        $dirty = [];

        $attributes = $this->getAttributes($translations);

        foreach ($attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                                 ! $this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Returns the attributes for either the actual model or the translations table
     * @param bool $translations
     * @param bool $attributes
     * @return array
     */
    public function getAttributes($translations = false, $attributes = false)
    {
        if (!$attributes) {
            $attributes = $this->attributes;
        }
    
        $translate = $this->translate + ['language_id'];

        $translate = array_flip($translate);

        if ($translations) {
            if (config('translate.use_timestamps')) {
                $translate = array_merge($translate, ['created_at', 'updated_at']);
            }
            $attributes = array_intersect_key($attributes, $translate);
        } else {
            $attributes = array_diff_key($attributes, $translate);
        }

        return $attributes;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $attributes
     * @return void
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $attributes = $this->getAttributes(); //This is altered to use our getAttributes function. This way it won't insert translateables.

        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    /**
     * getTable function returns the pluralized table, yet for the relation we need the singular form.
     * @return string
     */
    public function getTableSingular()
    {
        return isset($this->table) ? $this->table : str_replace('\\', '', snake_case((class_basename($this))));
    }

    /**
     * Check if we have translateable attributes
     * @return bool
     */
    public function hasTranslate()
    {
        return (bool) $this->translate;
    }

    /**
     * Get Translateable Attributes
     * @return array
     */
    public function getTranslateableAttributes()
    {
        return $this->translate;
    }
}
