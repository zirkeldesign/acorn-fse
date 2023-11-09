<?php

namespace Zirkeldesign\AcornFSE\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Blade;
use Roots\Acorn\ServiceProvider;
use Zirkeldesign\AcornFSE\AcornFSE;
use Zirkeldesign\AcornFSE\Console\AcornFSECommand;

class AcornFSEServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('acorn.fse', AcornFSE::class);

        $this->mergeConfigFrom(
            __DIR__.'/../../config/fse.php',
            'fse'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function boot()
    {
        if ($this->app->bound('sage')) {
            $this->app['acorn.fse']->prepareSageTheme();
        }

        if ($this->app->bound('blade.compiler')) {
            $this->registerDirectives();
        }

        $this->publishes(
            [
                __DIR__.'/../../config/fse.php' => $this->app->configPath('fse.php'),
            ],
            'fse-config'
        );

        $this->publishes(
            [
                __DIR__.'/../../stubs/parts' => $this->app->basePath('parts'),
                __DIR__.'/../../stubs/patterns' => $this->app->basePath('patterns'),
                __DIR__.'/../../stubs/templates' => $this->app->basePath('templates'),
            ],
            'fse-theme-files'
        );

        $this->commands([
            AcornFSECommand::class,
        ]);

        $this->app->make('acorn.fse');
    }

    protected function registerDirectives(): void
    {
        Blade::directive('parseblocks', static fn () => "<?php if (\wp_is_block_theme()): ob_start(); ?>");
        Blade::directive('endparseblocks', static fn () => '<?php $block_content = ob_get_clean(); echo do_blocks($block_content); endif; ?>');

        Blade::directive(
            'blocktemplate',
            static fn ($block) => $block !== ''
                ? "<?php if (\wp_is_block_theme()): block_template_part({$block}); else: ?>"
                : "<?php if (!\wp_is_block_theme()): ?>"
        );

        Blade::directive('endblocktemplate', static fn () => '<?php endif; ?>');
    }
}
