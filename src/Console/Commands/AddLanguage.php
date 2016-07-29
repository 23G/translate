<?php

namespace DylanLamers\Translate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use DylanLamers\Translate\Models\Language;

class AddLanguage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:addLanguage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add languages';

    /**
     * Description
     * @param Container $app
     * @return void
     */
    public function __construct(Container $app)
    {
        $this->app = $app;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $whileQuestion = 'Do you want to add an language?';

        $languages = [];
        $defaultIsSet = (bool) Language::whereSort(999)->count();

        while ($this->confirm($whileQuestion)) {
            $whileQuestion = 'Do you want to add another language?';

            $sort = null;
            $readable = $this->ask('What is the full name of the language (eg: English)');
            $code = $this->ask('What is the code of the language (eg: en)');
            
            if (!$defaultIsSet && $this->confirm('Is this the default language?')) {
                $sort = 999;
                $defaultIsSet = true;
            }

            $languages[] = compact('code', 'readable', 'sort');
        }

        if ($languages) {
            Language::insert($languages);
        }
    }
}
