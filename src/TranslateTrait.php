<?php 

namespace DylanLamers\Translate;

use Illuminate\Database\Eloquent\Builder;
use DylanLamers\Translate\Facades\Translate;

trait TranslateTrait {

    protected $langId = 0;
    protected $langCode = '';
    protected $langName = '';
    protected $newTranslate = false;

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
        return 'trans: '.parent::getAttribute($key);
        
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
        if($this->isKeyTranslatable($key) && !array_key_exists($key, $this->original)){
            $this->newTranslate = true;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Tells if the key belongs to the model or the translations table.
     * @param string $key 
     * @return bool
     */
    public function isKeyTranslatable(string $key)
    {
        return in_array($key, $this->translateable);
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
        if(!$this->newTranslate && $dirty = $this->getDirty(true)){
            $table = $this->getTable().'_lang';
            \DB::table($table)->whereTextId($this->attributes['id'])->whereLanguageId(Translate::getLanguageId())->update($dirty);
        }

        /*
            Call the parent function and save the attributes belonging to the actual model, the getDirt function is altered to make sure no translateable properties are being saved.
        */
        $returnParent = parent::save();

        /*
            We can do this only after we have the parent function has set the id.
        */
        if($this->newTranslate){
            $table = $this->getTable().'_lang';
            $attributes = $this->getAttributes(true);
            $attributes[$this->getTableSingular().'_id'] = $this->attributes['id'];
            $attributes['language_id'] = Translate::getLanguageId();

            \DB::table($table)->insert($attributes);

            $this->newTranslate = false;
        }

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
    public function getAttributes($translations = false, $attributes = false){
        if(!$attributes){
            $attributes = $this->attributes;
        }

        $translateable = array_flip($this->translateable);

        if($translations){
            $attributes = array_intersect_key($attributes, $translateable);
        }else{
            $attributes = array_diff_key($attributes, $translateable);
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
        $attributes = $this->getAttributes();

        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id);
    }

    /**
     * getTable function returns the pluralized table, yet for the relation we need the singular form.
     * @return string
     */
    public function getTableSingular(){
        return isset($this->table) ? $this->table : str_replace('\\', '', snake_case((class_basename($this))));
    }


}