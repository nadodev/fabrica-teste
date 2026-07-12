<?php

it('keeps framework dependencies outside domain layers', function () {
    $modules = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Modules')));

    foreach ($modules as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || ! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        expect($contents)
            ->not->toContain('Illuminate\\')
            ->not->toContain('Eloquent\\');
    }
});
