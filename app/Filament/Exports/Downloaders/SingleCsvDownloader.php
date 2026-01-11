<?php

namespace App\Filament\Exports\Downloaders;

use Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SingleCsvDownloader
{
    public function __invoke(Export $export): StreamedResponse
    {
        $disk = $export->getFileDisk();
        $directory = $export->getFileDirectory();

        return response()->streamDownload(function () use ($disk, $directory, $export): void {
            $fileName = $export->file_name ?? 'export.csv';
            $filePath = $directory.DIRECTORY_SEPARATOR.$fileName;

            if ($disk->exists($filePath)) {
                echo $disk->get($filePath);
            } else {
                $headerFile = $directory.DIRECTORY_SEPARATOR.'headers.csv';
                if ($disk->exists($headerFile)) {
                    echo $disk->get($headerFile);
                }

                foreach ($disk->files($directory) as $file) {
                    if (str_ends_with($file, 'headers.csv')) {
                        continue;
                    }

                    if (! str_ends_with($file, '.csv')) {
                        continue;
                    }

                    echo $disk->get($file);
                }
            }

            flush();
        }, $export->file_name.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
