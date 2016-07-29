#Dylan Lamers - Translate
Easy, fast and reliable way to do multi language (database) translations on models.

Yet another? Why is this so special?
 - Very lightweight.

 - Speed. This package uses joins instead of models. Eloquent models are great but on translations it can become a bit resource unfriendly. This bottlenecks that problem.

 - Smart URL. Availability to have nice looking urls like /hello /nl/hello /es/hello. But not required!

 - Console. Nice console commands to make installing and adding languages a breeze.

 - Comments. Comments have been used throughout the whole project.

 - PSR <3

#Setup

**Run installer**

    php artisan translate:install

This will run the migrations, publish config and ask to preset some languages

**Add trait to your eloquent model('s)**

    <?php
    
    namespace App;
    
    use Illuminate\Database\Eloquent\Model;
    use DylanLamers\Translate\TranslateTrait;
    
    class Article extends Model
    {
        public $translate = ['title', 'description']; // Specify all translateable attributes
        
        use TranslateTrait; // Include trait
    }

**Create migration for the translatable attributes (Or any other way you are used to create a table)**

    Schema::create('article_lang', function (Blueprint $table) {
        $table->increments('id'); // (Optional)
        $table->integer('text_id')->unsigned(); // (Required) Parent table name + _id
        $table->integer('language_id')->unsigned(); // (Required)
        $table->string('title'); // (Optional) Example
        $table->text('description'); // (Optional) Example
    });

#Usage

**Setting the language**
This will look familiar

    App::setLocale('en');
    $app->setLocale('en');
    app()->setLocale('en');
 If you just want to set the language for just the models and not the interface (in a cms for example)
 

    Translate::forceLanguage('en'); // This wil keep app.locale intact.
    
**Get and set the attributes from the model like you are used to**

    $foo = $article->foo; // Non translateable
    $article->foo = $foo.'bar'; // When saved will be saved to main table
    
    $title = $article->title; // Translateable
    $article->title = 'Titel' // When saved this will be saved to the _lang table.
**Sometimes we maybe want to force save to another language, check this out:**

    $article->forceLanguage('en')->save();

    
#Smart URL
This package can detect the language code on url basis. It will look for **{code}/** (example: h**p://foo.bar/en/hello)

**First of all enable this in the config**


    'use_nice_urls' => true
That's it. Make sure your routes look like the above example.

*When no language code is found in the url, the app.locale is used*

#Get all languages
Why not! Maybe for a nice selector.

    Translate::getLanguages(); // Returns collection of languages.
    Translate::getLanguage(); // Return current language (Eloquent Model)
    Translate::getCurrentLanguageId(); // Returns current language id

#Console
**Add language's**

    php artisan translate:addLanguage
