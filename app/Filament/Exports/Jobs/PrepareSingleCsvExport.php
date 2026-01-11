<?php

namespace App\Filament\Exports\Jobs;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Jobs\ExportCompletion;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use League\Csv\Bom;
use League\Csv\Writer;
use Throwable;

class PrepareSingleCsvExport implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    protected Exporter $exporter;

    public function __construct(
        protected Export $export,
        protected string $query,
        protected array $columnMap,
        protected array $options = [],
        protected int $chunkSize = 100,
        protected ?array $records = null,
    ) {
        $this->exporter = $this->export->getExporter(
            $this->columnMap,
            $this->options,
        );
    }

    public function handle(): void
    {
        $query = EloquentSerializeFacade::unserialize($this->query);

        foreach ($this->exporter->getCachedColumns() as $column) {
            $column->applyRelationshipAggregates($query);
            $column->applyEagerLoading($query);
        }

        $user = $this->export->user;
        Auth::setUser($user);

        $csv = Writer::createFromString('');
        $csv->setOutputBOM(Bom::Utf8);
        $csv->setDelimiter($this->exporter::getCsvDelimiter());

        $csv->insertOne(array_values($this->columnMap));

        $processedRows = 0;
        $successfulRows = 0;

        if (filled($this->records)) {
            $records = $query->find($this->records);

            foreach ($records as $record) {
                if ($this->batch()?->cancelled()) {
                    break;
                }

                try {
                    $csv->insertOne(($this->exporter)($record));
                    $successfulRows++;
                } catch (Throwable $exception) {
                    report($exception);
                }

                $processedRows++;
            }
        } else {
            $query->chunkById($this->chunkSize, function ($records) use ($csv, &$processedRows, &$successfulRows) {
                if ($this->batch()?->cancelled()) {
                    return false;
                }

                foreach ($records as $record) {
                    try {
                        $csv->insertOne(($this->exporter)($record));
                        $successfulRows++;
                    } catch (Throwable $exception) {
                        report($exception);
                    }

                    $processedRows++;
                }

                return true;
            });
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "registrations-export-{$timestamp}.csv";
        $filePath = $this->export->getFileDirectory().DIRECTORY_SEPARATOR.$fileName;

        $this->export->getFileDisk()->put(
            $filePath,
            $csv->toString(),
            Filesystem::VISIBILITY_PRIVATE
        );

        $this->export->update([
            'processed_rows' => $processedRows,
            'successful_rows' => $successfulRows,
            'total_rows' => $processedRows,
            'file_name' => $fileName,
        ]);

        if (! $this->batch()?->cancelled()) {
            ExportCompletion::dispatch(
                $this->export,
                $this->exporter->getFormats(),
                config('filament.default_filesystem_disk'),
            );
        }

        Auth::logout();
    }

    public function retryUntil(): \DateTime
    {
        return now()->addDay();
    }

    public function tags(): array
    {
        return ["export{$this->export->getKey()}"];
    }
}
