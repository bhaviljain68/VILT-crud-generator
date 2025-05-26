<?php

namespace artisanalbyte\VILTCrudGenerator\Utils;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;


/**
 * Loads stub files and performs placeholder replacements.
 */
class StubRenderer
{
    protected Filesystem $files;
    protected string $stubPath;

    /**
     * @param Filesystem $files Illuminate filesystem for reading/writing files
     * @param string|null $stubPath Optional base path to stubs; defaults to package stubs dir
     */
    public function __construct(Filesystem $files, ?string $stubPath = null)
    {
        $this->files = $files;
        $this->stubPath = $stubPath ?? __DIR__ . '/../../stubs';
    }

    /**
     * Render a stub template by name with the given replacements.
     *
     * @param string $stubName Filename of the stub (e.g. 'model.stub')
     * @param array<string,string> $replacements Key => value pairs to replace in stub
     * @return string Rendered content
     * @throws \InvalidArgumentException If stub file not found
     */
    public function render(string $stubName, array $replacements): string
    {
        // 1) Check for a published override in resources/stubs/vilt-crud-generator
        $override = resource_path('stubs/vilt-crud-generator/' . str_replace('\\', '/', $stubName));

        if ($this->files->exists($override)) {
            $path = $override;
        } else {
            // 2) Fall back to the package’s bundled stubs
            $path = rtrim($this->stubPath, '/\\') . DIRECTORY_SEPARATOR . $stubName;
        }

        if (! $this->files->exists($path)) {
            throw new \InvalidArgumentException("Stub file not found: {$path}");
        }

        $content = $this->files->get($path);

        // Perform simple placeholder replacement: {{ key }}
        foreach ($replacements as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $content = Str::replace($placeholder, $value, $content);
        }

        return $content;
    }
}
