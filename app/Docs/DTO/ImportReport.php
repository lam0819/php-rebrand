<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * A summary of a single import run: what was imported, skipped, and what failed.
 *
 * Mutable by design — the orchestrator accumulates results into it as it streams
 * through the documents — but it exposes only plain values.
 */
final class ImportReport
{
    /** @var list<string> */
    private array $imported = [];

    /** @var array<string, string> docId => reason */
    private array $skipped = [];

    /** @var list<string> docIds whose source XML was unchanged since last import */
    private array $unchanged = [];

    /** @var array<string, string> sourcePath => reason */
    private array $failed = [];

    public function recordImported(string $docId): void
    {
        $this->imported[] = $docId;
    }

    public function recordUnchanged(string $docId): void
    {
        $this->unchanged[] = $docId;
    }

    public function recordSkipped(string $docId, string $reason): void
    {
        $this->skipped[$docId] = $reason;
    }

    public function recordFailed(string $sourcePath, string $reason): void
    {
        $this->failed[$sourcePath] = $reason;
    }

    /** @return list<string> */
    public function imported(): array
    {
        return $this->imported;
    }

    /** @return array<string, string> */
    public function skipped(): array
    {
        return $this->skipped;
    }

    /** @return list<string> */
    public function unchanged(): array
    {
        return $this->unchanged;
    }

    /** @return array<string, string> */
    public function failed(): array
    {
        return $this->failed;
    }

    public function importedCount(): int
    {
        return count($this->imported);
    }

    public function skippedCount(): int
    {
        return count($this->skipped);
    }

    public function unchangedCount(): int
    {
        return count($this->unchanged);
    }

    public function failedCount(): int
    {
        return count($this->failed);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'unchanged' => $this->unchanged,
            'failed' => $this->failed,
            'counts' => [
                'imported' => $this->importedCount(),
                'skipped' => $this->skippedCount(),
                'unchanged' => $this->unchangedCount(),
                'failed' => $this->failedCount(),
            ],
        ];
    }
}
