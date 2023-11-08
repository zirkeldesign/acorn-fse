<?php

namespace Zirkeldesign\AcornFSE;

use Illuminate\Support\Arr;
use Roots\Acorn\Application;

class AcornFSE
{
    /**
     * The application instance.
     *
     * @var \Roots\Acorn\Application
     */
    protected $app;

    /**
     * Create a new AcornFSE instance.
     *
     * @param  \Roots\Acorn\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Retrieve a random inspirational quote.
     *
     * @return string
     */
    public function getQuote()
    {
        return Arr::random(
            config('example.quotes')
        );
    }
}
