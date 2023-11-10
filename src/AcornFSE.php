<?php

namespace Zirkeldesign\AcornFSE;

use Roots\Acorn\Application;
use Roots\Acorn\Filesystem\Filesystem;
use Roots\Acorn\Sage\SageServiceProvider;
use Roots\Acorn\Sage\ViewFinder;

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
        private readonly Filesystem $files,
        private ?string $path = null,
    ) {
        $this->path ??= $this->app->basePath();

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
                $this->checkThemeCompability();
                $this->bindCompatFilters();
            });
    }

    public function checkThemeCompability()
    {
        if (! is_admin()) {
            return;
        }

        $issues = [];

        // Check whether required FSE files exist
        $required_files = [
            'templates/index.html',
            'style.css',
        ];
        $issues['required'] = array_filter($required_files, fn ($file) => ! $this->files->exists($this->path.'/'.$file));

        // Check for optional FSE files
        $optional_files = [
            'parts/',
            'patterns/',
        ];
        $issues['optional'] = array_filter($optional_files, fn ($file) => ! $this->files->exists($this->path.'/'.$file));

        // Ensure that index.php calls view(app('sage.view'), app('sage.data'))->render().
        // It must be called before wp_head() to ensure that the block styles are generated
        // correctly. We should use a loose comparison here, because the view() function
        // might be called with different parameters.
        // @see https://fullsiteediting.com/lessons/how-to-use-php-templates-in-block-themes/#h-making-sure-that-wordpress-loads-the-block-css
        $index_php = $this->files->get($this->path.'/index.php');
        $needle = 'view(app(\'sage.view\'), app(\'sage.data\'))->render()';
        if (! str_contains($index_php, $needle)
            || strpos($index_php, $needle) > strpos($index_php, 'wp_head()')
        ) {
            $issues['view_render_position'] = true;
        }

        // Build error message
        $message = '';
        if (! empty($issues['required'])) {
            $message .= '<p><strong>Required FSE files are missing:</strong></p>';
            $message .= '<ul class="ul-disc">';
            foreach ($issues['required'] as $file) {
                $message .= '<li>'.$file.'</li>';
            }
            $message .= '</ul>';
        }
        if (! empty($issues['optional'])) {
            $message .= '<p><strong>Optional FSE files are missing:</strong></p>';
            $message .= '<ul class="ul-disc">';
            foreach ($issues['optional'] as $file) {
                $message .= '<li>'.$file.'</li>';
            }
            $message .= '</ul>';
        }
        if (! empty($issues['view_render_position'])) {
            $message .= <<<'HTML'
<p><strong>index.php</strong> does not call <code>view(app('sage.view'), app('sage.data'))->render()</code> before <code>wp_head()</code>.
To generate the block styles correctly, it must be called before <code>wp_head()</code>. Please modify your <strong>index.php</strong> accordingly.</p>
HTML;
        }

        // Display error message
        if (! empty($message)) {
            add_action(
                'admin_notices',
                function () use ($message) {
                    wp_admin_notice(
                        <<<HTML
                            <p><strong>Acorn FSE:</strong> We found following issues:</p>
                            {$message}
                        HTML,
                        [
                            'type' => 'warning',
                            'paragraph_wrap' => false,
                            'dismissible' => false,
                        ]
                    );
                }
            );
        }

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

        // Build hierarchy with original $files and FSE paths on top.
        return array_merge($files, $fse_paths, $hierarchy);
    }

    /**
     * Add short circuit filter to return existing (non-blade) files early.
     *
     *
     * @return string
     */
    public function filterTemplateInclude($file)
    {
        if (@file_exists($file)
            && ! str_contains($file, '.blade.php')
        ) {
            return $file;
        }

        return $this->app['sage']?->filterTemplateInclude($file);
    }
}
