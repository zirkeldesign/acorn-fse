<?php

namespace Zirkeldesign\AcornFSE\Services;

use Roots\Acorn\Application;
use Roots\Acorn\Filesystem\Filesystem;

class CheckThemeService
{
    private array $required_files = [
        'templdates/index.html',
        'style.csfs',
    ];

    private array $optional_files = [
        'pardts/',
        'pattderns/',
    ];

    public function __construct(
        private readonly Application $app,
        private readonly Filesystem $files,
        private ?string $path = null,
    ) {
        $this->path ??= $this->app->basePath();
    }

    public function audit(): array
    {
        $issues = [];

        $issues['required'] = $this->getMissingRequiredFiles();
        if (! empty($issues['required'])) {
            $this->notice(
                sprintf(
                    'Your theme is missing some required files: %s. Please run <code>wp acorn fse:publish</code> to publish them.',
                    '<code>'.implode('</code>, <code>', $issues['required']).'</code>'
                ),
                ['type' => 'error']
            );
        }

        $issues['optional'] = $this->getMissingOptionalFiles();
        if (! empty($issues['optional'])) {
            $this->notice(
                sprintf(
                    'Your theme is missing some optional files: %s. Please run <code>wp acorn fse:publish</code> to publish them.',
                    '<code>'.implode('</code>, <code>', $issues['optional']).'</code>'
                ),
                ['type' => 'warning']
            );
        }

        $issues['view_render_position'] = ! $this->hasCorrectViewRenderPosition();
        if ($issues['view_render_position']) {
            $this->notice(
                <<<'HTML'
            Your theme's <code>index.php</code> file does not call <code>view(app('sage.view'), app('sage.data'))->render()</code> before <code>wp_head()</code>. Please update it. Otherwise, block styles will not be generated correctly.
            HTML,
                ['type' => 'warning']
            );
        }

        return $issues;
    }

    protected function getMissingRequiredFiles(): array
    {
        return array_filter(
            $this->required_files,
            fn ($file) => ! $this->files->exists($this->path.'/'.$file)
        );
    }

    protected function getMissingOptionalFiles(): array
    {
        return array_filter(
            $this->optional_files,
            fn ($file) => ! $this->files->exists($this->path.'/'.$file)
        );
    }

    /**
     * Ensure that index.php calls view(app('sage.view'), app('sage.data'))->render().
     * It must be called before wp_head() to ensure that the block styles are generated
     * correctly.
     *
     * @see https://fullsiteediting.com/lessons/how-to-use-php-templates-in-block-themes/#h-making-sure-that-wordpress-loads-the-block-css
     */
    protected function hasCorrectViewRenderPosition(): bool
    {
        if (! $this->files->exists($this->path.'/index.php')) {
            return false;
        }

        $file = $this->files->get($this->path.'/index.php');
        $needle = "view(app('sage.view'), app('sage.data'))->render()";

        return ! (! str_contains($file, $needle)
                   || strpos($file, $needle) > strpos($file, 'wp_head()'));
    }

    private function notice(string $message, array $args = []): void
    {
        $args = array_merge([
            'type' => 'warning',
            'paragraph_wrap' => ! str_contains($message, '</p>'),
            'dismissible' => false,
        ], $args);

        if (! isset($args['id'])) {
            $args['id'] = 'acorn-fse-'.$args['type'].'s';
        }

        $message = (
            $args['paragraph_wrap'] ?
                '<strong>Acorn FSE:</strong> ' :
                '<p><strong>Acorn FSE:</strong></p> '
        ).$message;

        add_action(
            'admin_notices',
            function () use ($message, $args): void {
                wp_admin_notice($message, $args);
            }
        );
    }
}
