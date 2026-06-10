import { FormEventHandler, useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Download, FileText, Trash2 } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { Pagination } from '@/components/Pagination';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Chatbot, KnowledgeDocument, Paginated } from '@/types';

interface Props {
    documents: Paginated<KnowledgeDocument>;
    chatbots: Chatbot[];
    chatbotId: string | null;
}

const defaultChatbotId = (chatbotId: string | null, chatbots: Chatbot[]) =>
    chatbotId ?? String(chatbots[0]?.id ?? '');

const KB_TEMPLATES = [
    { slug: 'umum', label: 'Template umum', description: 'Panduan layanan lengkap' },
    { slug: 'faq', label: 'Template FAQ', description: 'Pertanyaan umum pelanggan' },
    { slug: 'produk', label: 'Template produk', description: 'Katalog produk & layanan' },
    { slug: 'kebijakan', label: 'Template kebijakan', description: 'SOP & kebijakan resmi' },
] as const;

export default function KnowledgeIndex({ documents, chatbots, chatbotId }: Props) {
    const [tab, setTab] = useState<'upload' | 'url' | ''>('');
    const [fileInputKey, setFileInputKey] = useState(0);

    const uploadForm = useForm<{
        chatbot_id: string;
        description: string;
        tags: string;
        files: File[];
    }>({
        chatbot_id: defaultChatbotId(chatbotId, chatbots),
        description: '',
        tags: '',
        files: [],
    });

    const urlForm = useForm({
        chatbot_id: defaultChatbotId(chatbotId, chatbots),
        url: '',
        name: '',
        description: '',
        tags: '',
        crawl_mode: 'single',
        max_pages: '50',
    });

    const hasProcessing = documents.data.some((d) => ['queued', 'processing'].includes(d.status));

    useEffect(() => {
        const id = defaultChatbotId(chatbotId, chatbots);
        uploadForm.setData('chatbot_id', id);
        urlForm.setData('chatbot_id', id);
    }, [chatbotId, chatbots]);

    useEffect(() => {
        if (!hasProcessing) return;
        const interval = setInterval(() => {
            router.reload({ only: ['documents'] });
        }, 5000);
        return () => clearInterval(interval);
    }, [hasProcessing]);

    const filterChatbot = (id: string) => {
        router.get('/admin/knowledge', { chatbot_id: id || undefined }, { preserveState: true });
    };

    const submitUpload: FormEventHandler = (e) => {
        e.preventDefault();

        if (uploadForm.data.files.length === 0) {
            uploadForm.setError('files', 'Pilih minimal satu file.');
            return;
        }

        uploadForm.post('/admin/knowledge', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                uploadForm.reset('description', 'tags', 'files');
                setFileInputKey((k) => k + 1);
                setTab('');
            },
        });
    };

    const submitUrl: FormEventHandler = (e) => {
        e.preventDefault();
        urlForm.post('/admin/knowledge/from-url', {
            preserveScroll: true,
            onSuccess: () => {
                urlForm.reset('url', 'name', 'description', 'tags');
                setTab('');
            },
        });
    };

    const confirmDelete = () => {
        if (!confirm('Hapus dokumen ini? Tindakan tidak dapat dibatalkan.')) {
            return false;
        }
    };

    return (
        <Layout>
            <Head title="Knowledge Base" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold">Knowledge Base</h1>
                        <p className="text-muted">Sumber pengetahuan chatbot dari file atau website</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant={tab === 'upload' ? 'default' : 'outline'}
                            onClick={() => setTab(tab === 'upload' ? '' : 'upload')}
                        >
                            Upload File
                        </Button>
                        <Button
                            type="button"
                            variant={tab === 'url' ? 'default' : 'outline'}
                            onClick={() => setTab(tab === 'url' ? '' : 'url')}
                        >
                            Dari Website
                        </Button>
                    </div>
                </div>

                <select
                    value={chatbotId ?? ''}
                    onChange={(e) => filterChatbot(e.target.value)}
                    className="h-10 rounded-md border border-hairline px-3 text-sm"
                >
                    <option value="">Semua Chatbot</option>
                    {chatbots.map((b) => (
                        <option key={b.id} value={b.id}>
                            {b.name}
                        </option>
                    ))}
                </select>

                <div className="rounded-lg border border-hairline bg-surface-soft p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="text-sm font-semibold">Template & Panduan</h2>
                            <p className="mt-1 text-xs text-muted">
                                Format dokumen dioptimalkan untuk RAG (chunk ~500 kata). Isi placeholder
                                lalu upload sebagai TXT/DOCX.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <a href="/admin/knowledge/guide" download>
                                <Download className="mr-1.5 h-3.5 w-3.5" />
                                Panduan lengkap
                            </a>
                        </Button>
                    </div>
                    <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        {KB_TEMPLATES.map((t) => (
                            <a
                                key={t.slug}
                                href={`/admin/knowledge/templates/${t.slug}`}
                                download
                                className="flex items-center gap-2 rounded-md border border-hairline bg-surface-card px-3 py-2 text-sm transition-colors hover:border-accent"
                            >
                                <Download className="h-4 w-4 shrink-0 text-muted" />
                                <span>
                                    <span className="font-medium">{t.label}</span>
                                    <span className="mt-0.5 block text-xs text-muted">{t.description}</span>
                                </span>
                            </a>
                        ))}
                    </div>
                </div>

                {tab === 'upload' && (
                    <form onSubmit={submitUpload} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                        <h2 className="font-semibold">Upload Dokumen</h2>
                        <div>
                            <Label>Chatbot *</Label>
                            <select
                                value={uploadForm.data.chatbot_id}
                                onChange={(e) => uploadForm.setData('chatbot_id', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                required
                            >
                                {chatbots.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>File *</Label>
                            <p className="mt-0.5 text-xs text-muted">PDF, DOCX, XLSX, CSV, TXT — maks. 50MB per file</p>
                            <Input
                                key={fileInputKey}
                                type="file"
                                multiple
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                                className="mt-1"
                                required
                                onChange={(e) => {
                                    uploadForm.setData('files', Array.from(e.target.files ?? []));
                                    uploadForm.clearErrors('files');
                                }}
                            />
                            {uploadForm.data.files.length > 0 && (
                                <ul className="mt-2 space-y-1 text-sm text-muted">
                                    {uploadForm.data.files.map((file) => (
                                        <li key={`${file.name}-${file.size}`}>{file.name}</li>
                                    ))}
                                </ul>
                            )}
                            {uploadForm.errors.files && (
                                <p className="mt-1 text-sm text-error">{uploadForm.errors.files}</p>
                            )}
                        </div>
                        <div>
                            <Label>Deskripsi</Label>
                            <Input
                                value={uploadForm.data.description}
                                onChange={(e) => uploadForm.setData('description', e.target.value)}
                                className="mt-1"
                                placeholder="Deskripsi singkat dokumen..."
                            />
                        </div>
                        <div>
                            <Label>Tags</Label>
                            <Input
                                value={uploadForm.data.tags}
                                onChange={(e) => uploadForm.setData('tags', e.target.value)}
                                className="mt-1"
                                placeholder="FAQ, Produk (pisahkan koma)"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={uploadForm.processing}>
                                {uploadForm.processing ? 'Mengupload...' : 'Upload & Proses'}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => setTab('')}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}

                {tab === 'url' && (
                    <form onSubmit={submitUrl} className="space-y-4 rounded-lg border border-hairline bg-surface-card p-6">
                        <h2 className="font-semibold">Tambah dari URL</h2>
                        <p className="text-sm text-muted">Scrape konten teks dari halaman website sebagai sumber knowledge.</p>
                        <div>
                            <Label>URL *</Label>
                            <Input
                                type="url"
                                value={urlForm.data.url}
                                onChange={(e) => urlForm.setData('url', e.target.value)}
                                className="mt-1 font-mono"
                                placeholder="https://example.com/halaman-faq"
                                required
                            />
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Chatbot *</Label>
                                <select
                                    value={urlForm.data.chatbot_id}
                                    onChange={(e) => urlForm.setData('chatbot_id', e.target.value)}
                                    className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                    required
                                >
                                    {chatbots.map((b) => (
                                        <option key={b.id} value={b.id}>
                                            {b.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Nama sumber</Label>
                                <Input
                                    value={urlForm.data.name}
                                    onChange={(e) => urlForm.setData('name', e.target.value)}
                                    className="mt-1"
                                    placeholder="Otomatis dari URL jika kosong"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Mode scraping *</Label>
                            <select
                                value={urlForm.data.crawl_mode}
                                onChange={(e) => urlForm.setData('crawl_mode', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                            >
                                <option value="single">Halaman tunggal</option>
                                <option value="crawl">Crawl multi-halaman</option>
                            </select>
                        </div>
                        {urlForm.data.crawl_mode === 'crawl' && (
                            <div>
                                <Label>Maksimal halaman</Label>
                                <select
                                    value={urlForm.data.max_pages}
                                    onChange={(e) => urlForm.setData('max_pages', e.target.value)}
                                    className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                >
                                    <option value="10">10 halaman</option>
                                    <option value="25">25 halaman</option>
                                    <option value="50">50 halaman</option>
                                    <option value="100">100 halaman</option>
                                    <option value="200">200 halaman</option>
                                    <option value="500">500 halaman</option>
                                </select>
                            </div>
                        )}
                        <div>
                            <Label>Deskripsi</Label>
                            <Input
                                value={urlForm.data.description}
                                onChange={(e) => urlForm.setData('description', e.target.value)}
                                className="mt-1"
                                placeholder="Deskripsi singkat..."
                            />
                        </div>
                        <div>
                            <Label>Tags</Label>
                            <Input
                                value={urlForm.data.tags}
                                onChange={(e) => urlForm.setData('tags', e.target.value)}
                                className="mt-1"
                                placeholder="FAQ, Produk (pisahkan koma)"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={urlForm.processing}>
                                {urlForm.processing ? 'Memproses...' : 'Tambah URL'}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => setTab('')}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}

                <div className="space-y-2">
                    {documents.data.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-hairline p-8 text-center text-sm text-muted">
                            Belum ada dokumen. Upload file atau tambah dari URL untuk memulai.
                        </p>
                    ) : (
                        documents.data.map((doc) => (
                            <div
                                key={doc.id}
                                className="flex items-center justify-between rounded-lg border border-hairline bg-surface-card p-4"
                            >
                                <Link href={`/admin/knowledge/${doc.id}`} className="flex flex-1 items-center gap-3">
                                    <FileText className="h-5 w-5 shrink-0 text-muted" />
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{doc.name}</p>
                                        <p className="text-sm text-muted">
                                            {doc.chatbot?.name} · {doc.type.toUpperCase()}
                                        </p>
                                    </div>
                                </Link>
                                <div className="flex shrink-0 items-center gap-2">
                                    <StatusBadge status={doc.status} />
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={`/admin/knowledge/${doc.id}/reindex`} method="post" preserveScroll>
                                            Reindex
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={`/admin/knowledge/${doc.id}`}
                                            method="delete"
                                            preserveScroll
                                            onBefore={confirmDelete}
                                            aria-label={`Hapus ${doc.name}`}
                                        >
                                            <Trash2 className="h-4 w-4 text-error" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
                <Pagination data={documents} />
            </div>
        </Layout>
    );
}
