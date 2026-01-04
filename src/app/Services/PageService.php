<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PageService
{
    /**
     * Get the path to the pages directory
     */
    protected function getPagesPath(): string
    {
        return resource_path('views/pages');
    }

    /**
     * Get a page by its name
     */
    public function getPage(string $pageName): ?string
    {
        $path = $this->getPagesPath() . "/{$pageName}.md";

        if (!File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    /**
     * Check if a page exists
     */
    public function pageExists(string $pageName): bool
    {
        return File::exists($this->getPagesPath() . "/{$pageName}.md");
    }

    /**
     * Get all available pages
     */
    public function getAllPages(): array
    {
        $pages = [];
        $path = $this->getPagesPath();

        if (!File::exists($path)) {
            return $pages;
        }

        $files = File::files($path);
        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $pages[] = $file->getFilenameWithoutExtension();
            }
        }

        return $pages;
    }
}
