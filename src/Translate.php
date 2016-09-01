<?php

namespace DylanLamers\Translate;

use Illuminate\Support\Facades\Config;
use DylanLamers\Translate\Models\Language;
use Illuminate\Foundation\Application as App;
use Illuminate\Session\SessionManager as Session;
use Illuminate\Http\Request;
use DylanLamers\Translate\Exceptions\LanguageNotFoundException;

class Translate
{

    protected $languages = [];
    protected $session;
    protected $sessionIsSet = false;
    protected $ignorePath;
    protected $forceLanguage = false;
    protected $initialized = false;
    protected $languageCodeFromUrl = false;
    protected $request;
    protected $originalLocale;

    /**
     * Set the session object.
     * @param Session $session
     * @param App $app
     * @return void
     */
    public function __construct(Session $session, App $app)
    {
        $this->session = $session;
        $this->app = $app;
        $this->request = $this->app->request;

        $this->ignorePath = config('translate.ignore_path');
    }

    /**
     * Only if we really need to we are going to change locale and only if the session is not yet set we are going to do a database lookup for the id and set it to the session.
     * @param Illuminate\Http\Request $request;
     * @return void
     */
    public function init(Request $request)
    {
        if ($this->initialized) {
            return;
        }

        $this->originalLocale = $this->app->getLocale();

        $sessionExists = $this->session->has('translate_language_code');

        if ($sessionExists) {
            $this->sessionIsSet = true;
            $sessionLanguageCode = $this->session->get('translate_language_code');

            if ($this->languageCodeFromUrl &&
                $this->languageCodeFromUrl !== $sessionLanguageCode
            ) {
                /**
                 * Language code from the URL is off with the session. Set the locale and destory the session. But only if it is different from default locale.
                 * (Becuase base url should be default locale)
                 */
                if ($this->languageCodeFromUrl || (!$this->languageCodeFromUrl && $this->originalLocale != $sessionLanguageCode)) {
                    $this->destroySession();
                    $this->setLocale($this->languageCodeFromUrl ? $this->languageCodeFromUrl : $this->originalLocale);
                }
            } else if (! $this->languageCodeFromUrl && $this->originalLocale !== $sessionLanguageCode) {
                /**
                 * No nice URL's so we can set the locale based on the session on the base url
                 */
                $this->setLocale($sessionLanguageCode);
            }
        } else if ($this->languageCodeFromUrl) {
            /**
             * Session has not yet been set, so just worry about locale for now. Session will set it self when it needs to.
             */
            $this->setLocale($this->languageCodeFromUrl);
        }

        $this->getLanguageId();

        $this->initialized = true;
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

    /**
     * This function is called when app->setLocale is fired
     * @return void
     */
    public function localeChanged()
    {
        $this->setLanguage($this->app->getLocale(), false);
    }

    public function forceLanguage($code)
    {
        if ($language = $this->getLanguageByCode($code)) {
            $this->forceLanguage = $language;
        }
    }

    /**
     * Retrieves current language by code from collection.
     * @param string $languageCode
     * @return Language
     */
    public function getLanguageByCode(string $languageCode)
    {
        if ($language = $this->languages()->where('code', $languageCode)->first()) {
            return $language;
        }

        throw new LanguageNotFoundException('Make sure to add the language for the default locale in the database');
    }

    /**
     * Retrieves current language by id from collection.
     * @param int $id
     * @return Language
     */
    public function getLanguageById(int $id)
    {
        if ($language = $this->languages()->where('id', $id)->first()) {
            return $language;
        }

        throw new LanguageNotFoundException('Make sure to add the language for the default locale in the database');
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
        if ($this->forceLanguage) {
            return $this->forceLanguage;
        }

        $languageCode = $this->app->getLocale();
        return $this->getLanguageByCode($languageCode);
    }

    /**
     * Get the current language id and try to do it withouth a database call. This could save a query in the full request in some ocassions.
     * @return int
     */
    public function getLanguageId()
    {
        if ($this->forceLanguage) {
            return $this->forceLanguage->id;
        }

        $this->setSession(); // Do it at the point we need it.
        return (int) ($this->session->has('translate_language_id') ? $this->session->get('translate_language_id') : $this->getLanguage()->id);
    }

    /**
     * Get the id for the fallback language IF set and exists in the database.
     * @return int
     */
    public function getFallbackLanguageId()
    {
        if ($this->forceLanguage) {
            return false;
        }

        $this->setSession(); // Do it at the point we need it.
        return (int) ($this->session->has('translate_fallback_language_id') ? $this->session->get('translate_fallback_language_id') : false);
    }

    public function getLanguageCode()
    {
        return $this->session->get('translate_language_code');
    }

    /**
     * Set the Language,
     * @param string|int $languageCode
     * @param bool $alsoLocale
     * @return void
     */
    public function setLanguage($languageCode, $alsoLocale = true)
    {
        if (is_int($languageCode)) {
            if ($languageCode === $this->getLanguageId()) {
                return; // No Change
            }

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

    public function prefix($prefix = null){
        $languageCodeFromUrl = $this->request->segment(1);

        if ($languageCodeFromUrl === $this->ignorePath) {
            return $prefix;
        }

        $this->languageCodeFromUrl = in_array($languageCodeFromUrl, config('translate.language_codes')) ? $languageCodeFromUrl : false;

        if ($this->languageCodeFromUrl && $this->languageCodeFromUrl !== $this->app->getLocale()) {
            if (! $prefix) {
                return $this->languageCodeFromUrl;
            }

            return $this->languageCodeFromUrl.'/'.$prefix;
        }

        if (! $this->languageCodeFromUrl) {
            $this->languageCodeFromUrl = $this->app->getLocale();
        }

        return $prefix;
    }

    public function url($locale = null, $actualPath = null)
    {
        $path = '';
        $actualPath = $actualPath ? $actualPath : $this->request->path();

        if(! in_array($actualPath, config('translate.language_codes'))){
            $path = preg_replace('~^('.$this->getLanguageCode().')\/~i', '', $actualPath);
        }

        $locale = $locale ? $locale : $this->app->getLocale();
        $locale = $this->originalLocale === $locale ? '' : '/'.$locale.'/';

        return url($locale.$path);
    }

    public function route($route, $parameters, $locale = null)
    {
        return $this->url($locale, $this->urlPath(route($route, $parameters)));
    }

    protected function urlPath($url)
    {
        return ltrim(parse_url($url, PHP_URL_PATH), '/');
    }
}
