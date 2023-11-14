<?php

namespace Zirkeldesign\AcornFSE;

use Roots\Acorn\Application;
use Roots\Acorn\Sage\SageServiceProvider;
use Roots\Acorn\Sage\ViewFinder;
use Zirkeldesign\AcornFSE\Services\CheckThemeService;

use function Roots\add_filters;
use function Roots\remove_filters;

class AcornFSE
{
    public array $hierarchy_hooks = [
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

    /**
     * Create a new AcornFSE instance.
     */
    public function __construct(
        private readonly Application $app,
        private readonly ViewFinder $sageFinder,
        private readonly CheckThemeService $checkThemeService,
    ) {
        add_action('after_setup_theme', [$this, 'addThemeSupport']);
    }

    /**
     * Enable theme support for FSE.
     */
    public function addThemeSupport(): void
    {
        \add_theme_support('block-templates');
        \add_theme_support('wp-block-styles');
    }

    public function prepareSageTheme()
    {
        if (! $this->app->bound('sage')) {
            return;
        }

        $this->app->getProvider(SageServiceProvider::class)?->booted(
            function () {
                $this->checkThemeService->audit();
                $this->bindCompatFilters();
            });
    }

    /**
     * Use own template filters besides Acorn filters to support FSE.
     */
    public function bindCompatFilters(): void
    {
        $this->removeAcornTemplateFilters();

        add_filters($this->hierarchy_hooks, [$this, 'filterTemplateHierarchy']);

        add_filter('template_include', [$this, 'filterTemplateInclude'], 100);
    }

    /**
     * Remove Acorn's template filters to avoid conflicts.
     */
    private function removeAcornTemplateFilters(): void
    {
        $sage = $this->app['sage'];

        remove_filters($this->hierarchy_hooks, [$sage, 'filterTemplateHierarchy']);

        \remove_filter('template_include', [$sage, 'filterTemplateInclude'], 100);
    }

    /**
     * Build template hierarchy with FSE paths on top.
     */
    public function filterTemplateHierarchy($files): array
    {
        $hierarchy = $this->sageFinder->locate($files);

        // Extract all entries, which point to an official FSE path (e.g. templates/...)
        $fse_paths = array_filter($hierarchy,
            static fn ($file) => str_starts_with($file, 'templates/') || str_contains($file, 'templates/'));
        $hierarchy = array_diff($hierarchy, $fse_paths);

        // Extract all entries, which point to a custom blade template (e.g. template-foo.blade.php)
        $custom_template = get_page_template_slug();
        $custom_template_paths = [];
        if ($custom_template) {
            $custom_template_paths = array_filter($hierarchy,
                static fn ($file) => str_contains($file, $custom_template)
            );
            $hierarchy = array_diff($hierarchy, $custom_template_paths);
        }

        // Rebuild hierarchy with original $files and FSE paths on top.
        return array_merge($custom_template_paths, $files, $fse_paths, $hierarchy);
    }

    /**
     * Add short circuit filter to return existing (non-blade) files early.
     *
     * @return string
     */
    public function filterTemplateInclude($file)
    {
        ray($file);
        if (@file_exists($file)
            && ! str_contains($file, '.blade.php')
        ) {
            return $file;
        }

        return $this->app['sage']?->filterTemplateInclude($file);
    }
}
