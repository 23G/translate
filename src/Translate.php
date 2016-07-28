<?php

namespace DylanLamers\Translate;

use Illuminate\Support\Facades\Config;
use DylanLamers\Translate\Models\Language;
use App;
use Illuminate\Session\SessionManager as Session;

class Translate
{

    protected $languages = [];
    protected $session;
    protected $sessionIsSet = false;

    /**
     * Set the session object.
     * @param Request $request
     * @return void
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Only if we really need to we are going to change locale and only if the session is not yet set we are going to do a database lookup for the id and set it to the session.
     * @return void
     */
    public function init()
    {
        $sessionExists = $this->session->has('language_code');

        if ($this->session->has('language_code')) {
            $this->sessionIsSet = true;

            $currentLocale = App::getLocale();
            $languageCode = $sessionExists ? $this->session->get('language_code') : $currentLocale;

            if ($currentLocale != $languageCode) {
                App::setLocale($languageCode);
            }
        }
    }

    protected function setSession()
    {
        if (! $this->sessionIsSet) {
            $this->setLanguage(App::getLocale(), false);
        }
    }

    /**
     * Only if we need the languages we will call the database once.
     * @return Collection
     */
    protected function languages()
    {
        if (! $this->languages) {
            $this->languages = Language::sort()->get();
        }

        return $this->languages;
    }

    /**
     * Retrieves current language by code from collection.
     * @param string $languageCode
     * @return Language
     */
    public function getLanguageByCode(string $languageCode)
    {
        return $this->languages()->where('code', $languageCode)->first();
    }

    /**
     * Retrieves current language by id from collection.
     * @param int $id
     * @return Language
     */
    public function getLanguageById(int $id)
    {
        return $this->languages()->where('id', $id)->first();
    }

    /**
     * Better naming convention to substract languages from this class.
     * @return Collection
     */
    public function getLanguages()
    {
        return $this->languages();
    }

    /**
     * Retrieves current language from collection.
     * @return Language
     */
    public function getLanguage()
    {
        $languageCode = App::getLocale();
        return $this->getLanguageByCode($languageCode);
    }

    /**
     * Get the current language id and try to do it withouth a database call. This could save a query in the full request in some ocassions.
     * @return int
     */
    public function getLanguageId()
    {
        $this->setSession(); // Do it at the point we need it.
        return $this->session->has('language_id') ? $this->session->get('language_id') : $this->getLanguage()->id;
    }

    /**
     * Get the id for the fallback language IF set and exists in the database.
     * @return int|bool
     */
    public function getFallbackId()
    {
        $this->setSession(); // Do it at the point we need it.
        return $this->session->has('fallback_language_id') ? $this->session->get('fallback_language_id') : false;
    }

    /**
     * Set the Language,
     * @param string $languageCode
     * @param bool $alsoLocale
     * @return void
     */
    public function setLanguage(string $languageCode, $alsoLocale = true)
    {
        if (is_int($languageCode)) {
            $language = $this->getLanguageById($languageCode);
        } else {
            $language = $this->getLanguageByCode($languageCode);
        }

        if ($language) {
            if ($alsoLocale) {
                App::setLocale($language->code);
            }

            $this->session->set('language_id', $language->id);
            $this->session->set('language_code', $language->code);
        }

        if (config('app.fallback_locale') &&
            $fallbackLanguage = $this->getLanguageByCode(config('app.fallback_locale'))) {
            $this->session->set('fallback_language_id', $fallbackLanguage->id);
        }
    }
}
