<?php

namespace App\Jobs;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Services\DocumentProcessorService;
use App\Services\SumopodService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 2;

    public function __construct(
        public readonly int $documentId
    ) {
        $this->onQueue('documents');
    }

    public function uniqueId(): string
    {
        return (string) $this->documentId;
    }

    public function handle(
        DocumentProcessorService $processor,
        SumopodService $sumopodBase
    ): void {
        $document = KnowledgeDocument::find($this->documentId);

        if (! $document) {
            Log::warning("Document {$this->documentId} not found");

            return;
        }

        $sumopod = $sumopodBase->withTenantSettings(
            $document->chatbot?->tenant?->getAiConfig() ?? []
        );

        $document->update(['status' => 'processing']);

        try {
            if ($document->type === 'url') {
                $pagesMap = $processor->scrapeMultiplePages(
                    $document->path,
                    maxPages: (int) ($document->metadata['max_pages'] ?? 1)
                );
                $text = implode("\n\n---\n\n", array_map(
                    fn ($url, $content) => "Sumber: {$url}\n\n{$content}",
                    array_keys($pagesMap),
                    array_values($pagesMap)
                ));
            } else {
                $filePath = Storage::path($document->path);
                if (! file_exists($filePath)) {
                    throw new \RuntimeException("File tidak ditemukan: {$document->path}");
                }
                $text = $processor->extractText($filePath, $document->type);
            }

            $chunks = $processor->chunkText($text, chunkSize: 500, overlap: 50);

            if ($chunks === []) {
                throw new \RuntimeException('Dokumen tidak menghasilkan konten yang cukup untuk diindeks.');
            }

            $chunkCount = 0;

            DB::transaction(function () use ($document, $sumopod, $chunks, &$chunkCount) {
                $document->chunks()->delete();

                $batchSize = 20;

                foreach (array_chunk($chunks, $batchSize) as $batch) {
                    $embeddings = $sumopod->embedBatch($batch);

                    foreach ($batch as $index => $chunkText) {
                        KnowledgeChunk::create([
                            'document_id' => $document->id,
                            'content'     => $chunkText,
                            'embedding'   => $sumopod->formatEmbeddingForStorage($embeddings[$index]),
                            'chunk_index' => $chunkCount,
                        ]);
                        $chunkCount++;
                    }
                }

                $document->update([
                    'status'      => 'indexed',
                    'chunk_count' => $chunkCount,
                    'error_message' => null,
                ]);
            });

            Log::info("Document {$document->id} indexed with {$chunkCount} chunks");
        } catch (\Throwable $e) {
            Log::error('Document processing failed', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);

            $document->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        KnowledgeDocument::find($this->documentId)?->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
