import { Head, Link } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { Pagination } from '@/components/Pagination';
import { StatusBadge } from '@/components/StatusBadge';
import type { KnowledgeDocument, Paginated } from '@/types';

interface Chunk {
    id: number;
    chunk_index: number;
    content: string;
}

interface Props {
    document: KnowledgeDocument;
    chunks: Paginated<Chunk>;
}

export default function KnowledgeShow({ document, chunks }: Props) {
    return (
        <Layout>
            <Head title={document.name} />
            <div className="space-y-6">
                <Link href="/admin/knowledge" className="text-sm text-muted">← Kembali</Link>
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <div className="mb-6 grid gap-4 md:grid-cols-3">
                        <div><p className="text-xs text-muted">Nama</p><p className="font-medium">{document.name}</p></div>
                        <div><p className="text-xs text-muted">Tipe</p><p className="font-medium uppercase">{document.type}</p></div>
                        <div><p className="text-xs text-muted">Status</p><StatusBadge status={document.status} /></div>
                        <div><p className="text-xs text-muted">Chunks</p><p className="font-medium">{document.chunk_count ?? 0}</p></div>
                    </div>
                    <h2 className="mb-4 font-semibold">Preview Chunks</h2>
                    <div className="space-y-3">
                        {chunks.data.map((chunk) => (
                            <div key={chunk.id} className="rounded-lg border border-hairline p-4">
                                <p className="mb-2 text-xs text-muted">Chunk #{chunk.chunk_index + 1} · {chunk.content.length} karakter</p>
                                <p className="text-sm leading-relaxed">{chunk.content}</p>
                            </div>
                        ))}
                    </div>
                    <Pagination data={chunks} />
                </div>
            </div>
        </Layout>
    );
}
