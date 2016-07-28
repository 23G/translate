<?php

namespace DylanLamers\Translate;

use Illuminate\Support\Facades\Config;
use DylanLamers\Translate\Models\Language;
use Illuminate\Foundation\Application as App;
use Illuminate\Session\SessionManager as Session;

class Translate
{

    protected $languages = [];
    protected $session;
    protected $sessionIsSet = false;
    protected $useNiceUrls;

    /**
     * Set the session object.
     * @param Request $request
     * @param App $app
     * @return void
     */
    public function __construct(Session $session, App $app)
    {
        $this->session = $session;
        $this->app = $app;

        $this->useNiceUrls = config('translate.use_nice_urls');
    }

    /**
     * Only if we really need to we are going to change locale and only if the session is not yet set we are going to do a database lookup for the id and set it to the session.
     * @param string|bool $possibleCode;
     * @return void
     */
    public function init($possibleCode)
    {
        $sessionExists = $this->session->has('translate_language_code');

        /**
         * possibleCode will always be set to false when useNiceUrls is false (by its caller)
         */
        if ($possibleCode) {
            $possibleCode = in_array($possibleCode, config('translate.language_codes')) ? $possibleCode : false;
        }

        if ($sessionExists) {
            $this->sessionIsSet = true;

            $defaultLocale = $this->app->getLocale();
            $sessionLanguageCode = $this->session->get('translate_language_code');

            if ($this->useNiceUrls && $possibleCode !== $sessionLanguageCode) {
                /**
                 * Language code from the URL is off with the session. Set the locale and destory the session.
                 */
                $this->destroySession();
                $this->setLocale($possibleCode ? $possibleCode : $defaultLocale);
            } else if (! $this->useNiceUrls && $defaultLocale !== $sessionLanguageCode) {
                $this->setLocale($sessionLanguageCode);
            }
        } else if ($possibleCode) {
            /**
             * Session has not yet been set, so just worry about locale for now. Session will set it self when it needs to.
             */
            $this->setLocale($possibleCode);
        }
    }

    /**
     * Set locale withouth firing event.
     * @param string $locale
     * @return type
     */
    protected function setLocale(string $locale)
    {
        $this->app['config']->set('app.locale', $locale);
        $this->app['translator']->setLocale($locale);
    }

    /**
     * Destroys translation related sessions
     * @return void
     */
    protected function destroySession()
    {
        $this->session->forget(['translate_language_code', 'translate_language_id', 'translate_fallback_language_id']);
        $this->sessionIsSet = false;
    }

    protected function setSession()
    {
        if (! $this->sessionIsSet) {
            $this->setLanguage($this->app->getLocale(), false);
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

    public function localeChanged()
    {
        $this->setLanguage($this->app->getLocale(), false);
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
        $languageCode = $this->app->getLocale();
        return $this->getLanguageByCode($languageCode);
    }

    /**
     * Get the current language id and try to do it withouth a database call. This could save a query in the full request in some ocassions.
     * @return int
     */
    public function getLanguageId()
    {
        $this->setSession(); // Do it at the point we need it.
        return $this->session->has('translate_language_id') ? $this->session->get('translate_language_id') : $this->getLanguage()->id;
    }

    /**
     * Get the id for the fallback language IF set and exists in the database.
     * @return int|bool
     */
    public function getFallbackLanguageId()
    {
        $this->setSession(); // Do it at the point we need it.
        return $this->session->has('translate_fallback_language_id') ? $this->session->get('translate_fallback_language_id') : false;
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
                $this->setLocale($language->code);
            }

            $this->session->set('translate_language_id', $language->id);
            $this->session->set('translate_language_code', $language->code);
            $this->sessionIsSet = true;
        }

        if (config('app.fallback_locale') &&
            $fallbackLanguage = $this->getLanguageByCode(config('app.fallback_locale'))) {
            $this->session->set('translate_fallback_language_id', $fallbackLanguage->id);
        }
    }
}
