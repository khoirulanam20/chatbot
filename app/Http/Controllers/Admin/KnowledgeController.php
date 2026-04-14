<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentJob;
use App\Models\Chatbot;
use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeController extends Controller
{
    public function index(Request $request)
    {
        $chatbots  = Chatbot::all();
        $chatbotId = $request->get('chatbot_id');

        $query = KnowledgeDocument::with('chatbot')
            ->whereIn('chatbot_id', $chatbots->pluck('id'));

        if ($chatbotId) {
            $query->where('chatbot_id', $chatbotId);
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        return view('admin.knowledge.index', compact('documents', 'chatbots', 'chatbotId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'chatbot_id'  => 'required|exists:chatbots,id',
            'description' => 'nullable|string|max:255',
            'tags'        => 'nullable|string',
            'files'       => 'required|array|max:10',
            'files.*'     => 'file|mimes:pdf,doc,docx,xls,xlsx,csv,txt|max:51200',
        ]);

        $chatbot = Chatbot::findOrFail($request->chatbot_id);
        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $ext       = $file->getClientOriginalExtension();
            $name      = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $path      = $file->store("knowledge/{$chatbot->tenant_id}", 'local');

            $document = KnowledgeDocument::create([
                'chatbot_id'    => $chatbot->id,
                'name'          => $name,
                'original_name' => $file->getClientOriginalName(),
                'type'          => strtolower($ext),
                'path'          => $path,
                'status'        => 'queued',
                'description'   => $request->description,
                'tags'          => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
            ]);

            ProcessDocumentJob::dispatch($document->id);
            $uploaded[] = $document;
        }

        return back()->with('success', count($uploaded) . ' dokumen berhasil diupload dan sedang diproses.');
    }

    public function storeFromUrl(Request $request)
    {
        $request->validate([
            'chatbot_id'  => 'required|exists:chatbots,id',
            'url'         => 'required|url|max:2048',
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'tags'        => 'nullable|string',
            'max_pages'   => 'nullable|integer|min:1|max:500',
            'crawl_mode'  => 'required|in:single,crawl',
        ]);

        $chatbot  = Chatbot::findOrFail($request->chatbot_id);
        $url      = $request->url;
        $maxPages = $request->crawl_mode === 'crawl' ? ($request->max_pages ?? 50) : 1;
        $name     = $request->name ?: parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);

        $document = KnowledgeDocument::create([
            'chatbot_id'    => $chatbot->id,
            'name'          => Str::limit($name, 200),
            'original_name' => $url,
            'type'          => 'url',
            'path'          => $url,
            'status'        => 'queued',
            'description'   => $request->description,
            'tags'          => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
            'metadata'      => ['max_pages' => $maxPages, 'crawl_mode' => $request->crawl_mode],
        ]);

        ProcessDocumentJob::dispatch($document->id);

        $modeLabel = $request->crawl_mode === 'crawl' ? "crawl hingga {$maxPages} halaman" : '1 halaman';
        return back()->with('success', "URL berhasil ditambahkan dan sedang di-scrape ({$modeLabel}).");
    }

    public function destroy(KnowledgeDocument $document)
    {
        if ($document->type !== 'url') {
            Storage::disk('local')->delete($document->path);
        }
        $document->chunks()->delete();
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function reindex(KnowledgeDocument $document)
    {
        $document->update(['status' => 'queued', 'error_message' => null]);
        ProcessDocumentJob::dispatch($document->id);

        return back()->with('success', 'Dokumen dijadwalkan untuk re-index.');
    }

    public function show(KnowledgeDocument $document)
    {
        $chunks = $document->chunks()->orderBy('chunk_index')->paginate(20);
        return view('admin.knowledge.show', compact('document', 'chunks'));
    }
}
