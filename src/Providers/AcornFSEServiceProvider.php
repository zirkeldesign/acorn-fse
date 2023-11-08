<?php

namespace Zirkeldesign\AcornFSE\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Blade;
use Roots\Acorn\Sage\SageServiceProvider;
use Roots\Acorn\ServiceProvider;
use Zirkeldesign\AcornFSE\Console\AcornFSECommand;
use Zirkeldesign\AcornFSE\AcornFSE;

use function Roots\add_filters;
use function Roots\remove_filters;

class AcornFSEServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('AcornFSE', function () {
            return new AcornFSE($this->app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/fse.php',
            'fse'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
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

        if ($this->app->bound('sage')) {
            $sage = $this->app['sage'];
            if (method_exists($sage, 'filterTemplateHierarchy')) {
                $this->app->getProvider(SageServiceProvider::class)?->booted(fn() => $this->bindCompatFilters());
            }
        }

        if ($this->app->bound('blade.compiler')) {
            $this->registerDirectives();
        }

        $this->app->make('AcornFSE');
    }

    protected function bindCompatFilters(): void
    {
        $sage       = $this->app['sage'];
        $sageFinder = $this->app['sage.finder'];

        $hooks = [
            'index_template_hierarchy',
            '404_template_hierarchy',
            'archive_template_hierarchy',
            'author_template_hierarchy',
            'category_template_hierarchy',
            'tag_template_hierarchy',
            'taxonomy_template_hierarchy',
            'date_template_hierarchy',
            'home_template_hierarchy',
            'frontpage_template_hierarchy',
            'page_template_hierarchy',
            'paged_template_hierarchy',
            'search_template_hierarchy',
            'single_template_hierarchy',
            'singular_template_hierarchy',
            'attachment_template_hierarchy',
            'privacypolicy_template_hierarchy',
            'embed_template_hierarchy',
        ];

        remove_filters(
            $hooks,
            [$sage, 'filterTemplateHierarchy']
        );

        add_filters(
            $hooks,
            function (array $files) use ($sageFinder) {
                $hierarchy = $sageFinder->locate($files);

                // Extract all entries, which point to an official FSE path (e.g. templates/...)
                $fse_paths = array_filter($hierarchy,
                    static fn($file) => str_starts_with($file, 'templates/') || str_contains($file, 'templates/'));
                $hierarchy = array_diff($hierarchy, $fse_paths);

                // Build hierarchy with original $files and FSE paths on top.
                return array_merge($files, $fse_paths, $hierarchy);
            },
            10
        );

        remove_filter(
            'template_include',
            [$sage, 'filterTemplateInclude'],
            100
        );

        add_filter(
            'template_include',
            function ($file) use ($sage) {
                if (@file_exists($file)
                    && ! str_contains($file, '.blade.php')
                ) {
                    return $file;
                }

                return $sage->filterTemplateInclude($file);
            },
            100
        );
    }

    protected function registerDirectives(): void
    {
        Blade::directive(
            'blockTemplate',
            static fn($block) => $block !== ''
                ? "<?php if (\wp_is_block_theme()): block_template_part({$block}); else: ?>"
                : "<?php if (!\wp_is_block_theme()): ?>"
        );

        Blade::directive('endblockTemplate', static fn() => "<?php endif; ?>");
        Blade::directive('endBlockTemplate', static fn() => "<?php endif; ?>");
    }
}
