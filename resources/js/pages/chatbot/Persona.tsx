import { FormEventHandler, useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Layout } from '@/components/Layout';
import { ChatbotSubNav } from '@/components/ChatbotSubNav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ChatbotPersona } from '@/types';

interface Props {
    chatbot: { id: number; name: string };
    persona: ChatbotPersona;
    effective_system_prompt: string;
    uses_legacy_prompt: boolean;
    legacy_system_prompt?: string | null;
}

const TONE_LABELS: Record<string, string> = {
    ramah: 'ramah dan hangat',
    formal: 'formal dan sopan',
    profesional: 'profesional dan to the point',
    santai: 'santai dan akrab',
};

function composePersonaPreview(persona: ChatbotPersona): string {
    const parts: string[] = [];
    const role = (persona.role ?? '').trim();
    if (role) {
        parts.push(`Peran: ${role}`);
    }
    const tone = (persona.tone ?? '').trim();
    if (tone) {
        parts.push(`Gaya bicara: ${TONE_LABELS[tone] ?? tone}`);
    }
    const instructions = (persona.instructions ?? '').trim();
    if (instructions) {
        parts.push(`Instruksi:\n${instructions}`);
    }
    const restrictions = (persona.restrictions ?? '').trim();
    if (restrictions) {
        parts.push(`Larangan:\n${restrictions}`);
    }
    const greeting = (persona.greeting_style ?? '').trim();
    if (greeting) {
        parts.push(`Gaya sapaan: ${greeting}`);
    }
    return parts.join('\n\n');
}

const TEMPLATES: { title: string; description: string; persona: ChatbotPersona }[] = [
    {
        title: 'Customer Service ramah',
        description: 'Sapa hangat, jawaban singkat',
        persona: {
            role: 'Agen layanan pelanggan',
            tone: 'ramah',
            instructions:
                'Jawab singkat dan jelas dalam Bahasa Indonesia. Gunakan knowledge base jika tersedia. Jika tidak tahu, akui dengan jujur dan tawarkan bantuan agen.',
            restrictions: 'Jangan mengarang harga atau kebijakan. Jangan sebut kompetitor.',
            greeting_style: 'Sapa dengan nama jika diketahui',
        },
    },
    {
        title: 'Sales profesional',
        description: 'Fokus produk & konversi',
        persona: {
            role: 'Konsultan penjualan',
            tone: 'profesional',
            instructions:
                'Bantu pelanggan memahami manfaat produk. Ajukan pertanyaan klarifikasi bila perlu. Dorong langkah berikutnya (demo, pembelian, kontak sales).',
            restrictions: 'Jangan memberi diskon atau janji yang tidak ada di knowledge base.',
            greeting_style: 'Perkenalkan diri singkat lalu tanyakan kebutuhan',
        },
    },
    {
        title: 'Support teknis',
        description: 'Langkah demi langkah, teknis',
        persona: {
            role: 'Ahli dukungan teknis',
            tone: 'formal',
            instructions:
                'Berikan langkah troubleshooting terurut. Minta detail error jika belum jelas. Verifikasi solusi sebelum menutup topik.',
            restrictions: 'Jangan menebak penyebab tanpa data. Jangan instruksikan tindakan berbahaya.',
            greeting_style: 'Konfirmasi masalah utama pengguna',
        },
    },
];

export default function Persona({
    chatbot,
    persona: initialPersona,
    effective_system_prompt,
    uses_legacy_prompt,
    legacy_system_prompt,
}: Props) {
    const { data, setData, put, processing } = useForm({
        role: initialPersona.role ?? '',
        tone: initialPersona.tone ?? 'ramah',
        instructions: initialPersona.instructions ?? '',
        restrictions: initialPersona.restrictions ?? '',
        greeting_style: initialPersona.greeting_style ?? '',
    });

    const preview = useMemo(() => {
        const composed = composePersonaPreview(data);
        if (composed) {
            return composed;
        }
        if (uses_legacy_prompt && legacy_system_prompt) {
            return legacy_system_prompt;
        }
        return 'Isi minimal satu field persona untuk melihat preview.';
    }, [data, uses_legacy_prompt, legacy_system_prompt]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/chatbot/${chatbot.id}/persona`);
    };

    const applyTemplate = (template: ChatbotPersona) => {
        setData({
            role: template.role ?? '',
            tone: template.tone ?? 'ramah',
            instructions: template.instructions ?? '',
            restrictions: template.restrictions ?? '',
            greeting_style: template.greeting_style ?? '',
        });
    };

    return (
        <Layout>
            <Head title={`Persona — ${chatbot.name}`} />
            <div className="mx-auto max-w-3xl space-y-6">
                <ChatbotSubNav chatbotId={chatbot.id} active="persona" />
                <div className="rounded-lg border border-hairline bg-surface-card p-6">
                    <h1 className="font-display text-lg font-semibold">Persona — {chatbot.name}</h1>
                    <p className="mt-1 text-sm text-muted">
                        Atur karakter AI. Prompt efektif digabung otomatis untuk RAG.
                    </p>
                    {uses_legacy_prompt && (
                        <p className="mt-3 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-800 dark:text-amber-200">
                            Chatbot ini masih memakai system prompt lama. Isi persona di bawah lalu simpan untuk
                            menggantinya.
                        </p>
                    )}
                    <div className="mt-6 grid gap-3 sm:grid-cols-3">
                        {TEMPLATES.map((t) => (
                            <button
                                key={t.title}
                                type="button"
                                onClick={() => applyTemplate(t.persona)}
                                className="rounded-lg border border-hairline bg-surface-soft p-4 text-left transition-colors hover:border-accent"
                            >
                                <p className="font-medium text-sm">{t.title}</p>
                                <p className="mt-1 text-xs text-muted">{t.description}</p>
                            </button>
                        ))}
                    </div>
                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <div>
                            <Label>Peran</Label>
                            <Input
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                className="mt-1"
                                placeholder="Agen layanan pelanggan"
                            />
                        </div>
                        <div>
                            <Label>Gaya bicara</Label>
                            <select
                                value={data.tone}
                                onChange={(e) => setData('tone', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                            >
                                <option value="ramah">Ramah</option>
                                <option value="formal">Formal</option>
                                <option value="profesional">Profesional</option>
                                <option value="santai">Santai</option>
                            </select>
                        </div>
                        <div>
                            <Label>Instruksi utama</Label>
                            <textarea
                                rows={5}
                                value={data.instructions}
                                onChange={(e) => setData('instructions', e.target.value)}
                                className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <Label>Larangan</Label>
                            <textarea
                                rows={3}
                                value={data.restrictions}
                                onChange={(e) => setData('restrictions', e.target.value)}
                                className="mt-1 w-full rounded-md border border-hairline px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <Label>Gaya sapaan (opsional)</Label>
                            <Input
                                value={data.greeting_style}
                                onChange={(e) => setData('greeting_style', e.target.value)}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Preview prompt</Label>
                            <pre className="mt-1 max-h-64 overflow-auto whitespace-pre-wrap rounded-lg border border-hairline bg-surface-soft p-4 text-xs text-muted">
                                {preview}
                            </pre>
                            {!composePersonaPreview(data) && effective_system_prompt && (
                                <p className="mt-1 text-xs text-muted">
                                    Prompt aktif di server (setelah simpan terakhir): sama dengan preview di atas
                                    bila persona kosong.
                                </p>
                            )}
                        </div>
                        <Button type="submit" disabled={processing}>
                            Simpan Persona
                        </Button>
                    </form>
                </div>
            </div>
        </Layout>
    );
}
