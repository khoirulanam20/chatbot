import { FormEventHandler, useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { getCsrfToken } from '@/lib/csrf';
import { Loader2, Sparkles, Trash2 } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { ChatbotSubNav } from '@/components/ChatbotSubNav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ChatbotPersona, PersonaHumanizeSettings, PersonaTemplateRecord } from '@/types';

const DEFAULT_HUMANIZE: PersonaHumanizeSettings = {
    enabled: true,
    channels: ['whatsapp', 'web'],
    emoji_level: 'minimal',
    message_length: 'short',
    split_bubbles: true,
    pacing_ms: 1200,
    use_fillers: true,
    avoid_markdown: true,
};

interface Props {
    chatbot: { id: number; name: string; tenant_id?: number };
    persona: ChatbotPersona;
    effective_system_prompt: string;
    uses_legacy_prompt: boolean;
    legacy_system_prompt?: string | null;
    custom_templates: PersonaTemplateRecord[];
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

function composeHumanizePreview(humanize: PersonaHumanizeSettings): string {
    if (!humanize.enabled) {
        return '';
    }

    const channelLabels = (humanize.channels ?? [])
        .map((c) => (c === 'whatsapp' ? 'WhatsApp' : 'Widget web'))
        .join(', ');

    const lengthMap: Record<string, string> = {
        short: 'Pendek (1-3 kalimat per bubble)',
        medium: 'Sedang (2-4 kalimat per bubble)',
        long: 'Lebih panjang (3-5 kalimat per bubble)',
    };
    const emojiMap: Record<string, string> = {
        none: 'Tanpa emoji',
        minimal: 'Emoji sangat jarang',
        medium: 'Emoji sesekali',
        often: 'Emoji cukup sering',
    };

    const lines = [
        `[Humanisasi — ${channelLabels || 'semua channel'}]`,
        `- Panjang: ${lengthMap[humanize.message_length ?? 'short'] ?? lengthMap.short}`,
        `- Emoji: ${emojiMap[humanize.emoji_level ?? 'minimal'] ?? emojiMap.minimal}`,
    ];

    if (humanize.use_fillers) {
        lines.push('- Fillers natural (Oke, Hmm, Baik)');
    }
    if (humanize.avoid_markdown) {
        lines.push('- Tanpa markdown/bullet');
    }
    if (humanize.split_bubbles) {
        lines.push(`- Multi-bubble (jeda ${humanize.pacing_ms ?? 1200}ms)`);
    }

    return lines.join('\n');
}

const BUILTIN_TEMPLATES: { title: string; description: string; persona: ChatbotPersona }[] = [
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

function templateSubtitle(description?: string, role?: string, tone?: string): string {
    if (description?.trim()) {
        return description.trim();
    }
    if (role?.trim()) {
        return role.trim();
    }
    if (tone?.trim()) {
        return `Gaya ${TONE_LABELS[tone] ?? tone}`;
    }
    return 'Template personal';
}

interface TemplateCardProps {
    title: string;
    description: string;
    onSelect: () => void;
    onDelete?: () => void;
}

function TemplateCard({ title, description, onSelect, onDelete }: TemplateCardProps) {
    return (
        <div className="group relative rounded-lg border border-hairline bg-surface-soft p-4 transition-colors hover:border-accent">
            <button
                type="button"
                onClick={onSelect}
                className={`w-full text-left ${onDelete ? 'pr-8' : ''}`}
            >
                <p className="text-sm font-medium">{title}</p>
                <p className="mt-1 line-clamp-2 text-xs text-muted">{description}</p>
            </button>
            {onDelete && (
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        onDelete();
                    }}
                    className="absolute right-3 top-3 rounded p-0.5 text-muted hover:bg-error/10 hover:text-error"
                    title="Hapus template"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            )}
        </div>
    );
}

export default function Persona({
    chatbot,
    persona: initialPersona,
    effective_system_prompt,
    uses_legacy_prompt,
    legacy_system_prompt,
    custom_templates,
}: Props) {
    const { props: pageProps } = usePage<{ csrf_token?: string }>();
    const initialHumanize = { ...DEFAULT_HUMANIZE, ...initialPersona.humanize };

    const { data, setData, put, processing } = useForm({
        role: initialPersona.role ?? '',
        tone: initialPersona.tone ?? 'ramah',
        instructions: initialPersona.instructions ?? '',
        restrictions: initialPersona.restrictions ?? '',
        greeting_style: initialPersona.greeting_style ?? '',
        humanize: initialHumanize,
    });

    const [templateName, setTemplateName] = useState('');
    const [savingTemplate, setSavingTemplate] = useState(false);
    const [aiDescription, setAiDescription] = useState('');
    const [generating, setGenerating] = useState(false);
    const [generateError, setGenerateError] = useState<string | null>(null);

    const preview = useMemo(() => {
        const composed = composePersonaPreview(data);
        const humanizeBlock = composeHumanizePreview(data.humanize);
        const parts = [composed, humanizeBlock].filter(Boolean);

        if (parts.length > 0) {
            return parts.join('\n\n');
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
            humanize: { ...DEFAULT_HUMANIZE, ...template.humanize },
        });
    };

    const toggleHumanizeChannel = (channel: 'whatsapp' | 'web') => {
        const channels = data.humanize.channels ?? [];
        const next = channels.includes(channel)
            ? channels.filter((c) => c !== channel)
            : [...channels, channel];
        setData('humanize', { ...data.humanize, channels: next.length ? next : [channel] });
    };

    const saveAsTemplate = () => {
        if (!templateName.trim()) {
            return;
        }
        setSavingTemplate(true);
        router.post(
            '/admin/persona-templates',
            {
                name: templateName.trim(),
                tenant_id: chatbot.tenant_id,
                role: data.role,
                tone: data.tone,
                instructions: data.instructions,
                restrictions: data.restrictions,
                greeting_style: data.greeting_style,
            },
            {
                preserveScroll: true,
                onFinish: () => setSavingTemplate(false),
                onSuccess: () => {
                    setTemplateName('');
                    router.reload({ only: ['custom_templates'] });
                },
            }
        );
    };

    const deleteTemplate = (id: number, name: string) => {
        if (!confirm(`Hapus template "${name}"?`)) {
            return;
        }
        router.delete(`/admin/persona-templates/${id}`, {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['custom_templates'] }),
        });
    };

    const generateWithAi = async () => {
        const description = aiDescription.trim();
        if (!description) {
            setGenerateError('Masukkan deskripsi singkat terlebih dahulu.');
            return;
        }

        setGenerating(true);
        setGenerateError(null);

        const csrfToken = getCsrfToken(pageProps.csrf_token);
        if (!csrfToken) {
            setGenerateError('CSRF token tidak tersedia. Muat ulang halaman lalu coba lagi.');
            setGenerating(false);
            return;
        }

        try {
            const res = await fetch(`/admin/chatbot/${chatbot.id}/persona/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ description }),
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                setGenerateError(json.message ?? 'Gagal membuat persona.');
                return;
            }

            const persona = json.persona as ChatbotPersona;
            setData({
                role: persona.role ?? '',
                tone: persona.tone ?? 'ramah',
                instructions: persona.instructions ?? '',
                restrictions: persona.restrictions ?? '',
                greeting_style: persona.greeting_style ?? '',
                humanize: { ...DEFAULT_HUMANIZE, ...persona.humanize },
            });
        } catch {
            setGenerateError('Terjadi kesalahan jaringan. Coba lagi.');
        } finally {
            setGenerating(false);
        }
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

                    <div className="mt-6 space-y-4 rounded-lg border border-hairline bg-surface-soft p-4">
                        <div className="flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-accent" />
                            <h2 className="text-sm font-semibold">Buat dengan AI</h2>
                        </div>
                        <p className="text-xs text-muted">
                            Jelaskan singkat karakter chatbot yang diinginkan, AI akan mengisi form persona.
                        </p>
                        <textarea
                            rows={3}
                            value={aiDescription}
                            onChange={(e) => setAiDescription(e.target.value)}
                            placeholder="Contoh: Chatbot toko fashion ramah, fokus rekomendasi produk dan ukuran..."
                            className="w-full rounded-md border border-hairline px-3 py-2 text-sm"
                        />
                        {generateError && (
                            <p className="text-sm text-error">{generateError}</p>
                        )}
                        <Button type="button" variant="outline" onClick={generateWithAi} disabled={generating}>
                            {generating ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Membuat persona...
                                </>
                            ) : (
                                'Generate dengan AI'
                            )}
                        </Button>
                    </div>

                    <div className="mt-6 space-y-6">
                        <div>
                            <h2 className="mb-3 text-sm font-semibold">Template bawaan</h2>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {BUILTIN_TEMPLATES.map((t) => (
                                    <TemplateCard
                                        key={t.title}
                                        title={t.title}
                                        description={t.description}
                                        onSelect={() => applyTemplate(t.persona)}
                                    />
                                ))}
                            </div>
                        </div>

                        {custom_templates.length > 0 && (
                            <div>
                                <h2 className="mb-3 text-sm font-semibold">Template saya</h2>
                                <div className="grid gap-3 sm:grid-cols-3">
                                    {custom_templates.map((t) => (
                                        <TemplateCard
                                            key={t.id}
                                            title={t.name}
                                            description={templateSubtitle(t.description, t.role, t.tone)}
                                            onSelect={() =>
                                                applyTemplate({
                                                    role: t.role,
                                                    tone: t.tone,
                                                    instructions: t.instructions,
                                                    restrictions: t.restrictions,
                                                    greeting_style: t.greeting_style,
                                                })
                                            }
                                            onDelete={() => deleteTemplate(t.id, t.name)}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
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

                        <div className="space-y-4 rounded-lg border border-hairline bg-surface-soft p-4">
                            <h2 className="text-sm font-semibold">Humanisasi lanjutan</h2>
                            <p className="text-xs text-muted">
                                Membuat jawaban dan pengiriman pesan terasa lebih natural seperti chat manusia.
                            </p>
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.humanize.enabled}
                                    onChange={(e) =>
                                        setData('humanize', { ...data.humanize, enabled: e.target.checked })
                                    }
                                />
                                Aktifkan humanisasi natural
                            </label>
                            {data.humanize.enabled && (
                                <>
                                    <div>
                                        <Label className="mb-2 block">Channel</Label>
                                        <div className="flex gap-4">
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={data.humanize.channels?.includes('whatsapp')}
                                                    onChange={() => toggleHumanizeChannel('whatsapp')}
                                                />
                                                WhatsApp
                                            </label>
                                            <label className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={data.humanize.channels?.includes('web')}
                                                    onChange={() => toggleHumanizeChannel('web')}
                                                />
                                                Widget web
                                            </label>
                                        </div>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label>Level emoji</Label>
                                            <select
                                                value={data.humanize.emoji_level}
                                                onChange={(e) =>
                                                    setData('humanize', {
                                                        ...data.humanize,
                                                        emoji_level: e.target.value as PersonaHumanizeSettings['emoji_level'],
                                                    })
                                                }
                                                className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                            >
                                                <option value="none">Tanpa emoji</option>
                                                <option value="minimal">Minimal</option>
                                                <option value="medium">Sedang</option>
                                                <option value="often">Sering</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Panjang pesan</Label>
                                            <select
                                                value={data.humanize.message_length}
                                                onChange={(e) =>
                                                    setData('humanize', {
                                                        ...data.humanize,
                                                        message_length: e.target.value as PersonaHumanizeSettings['message_length'],
                                                    })
                                                }
                                                className="mt-1 flex h-10 w-full rounded-md border border-hairline px-3 text-sm"
                                            >
                                                <option value="short">Pendek</option>
                                                <option value="medium">Sedang</option>
                                                <option value="long">Panjang</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.humanize.split_bubbles}
                                            onChange={(e) =>
                                                setData('humanize', {
                                                    ...data.humanize,
                                                    split_bubbles: e.target.checked,
                                                })
                                            }
                                        />
                                        Pecah jadi beberapa bubble
                                    </label>
                                    {data.humanize.split_bubbles && (
                                        <div>
                                            <Label>Jeda antar bubble (ms)</Label>
                                            <Input
                                                type="number"
                                                min={500}
                                                max={3000}
                                                step={100}
                                                value={data.humanize.pacing_ms}
                                                onChange={(e) =>
                                                    setData('humanize', {
                                                        ...data.humanize,
                                                        pacing_ms: Number(e.target.value),
                                                    })
                                                }
                                                className="mt-1"
                                            />
                                        </div>
                                    )}
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.humanize.use_fillers}
                                            onChange={(e) =>
                                                setData('humanize', {
                                                    ...data.humanize,
                                                    use_fillers: e.target.checked,
                                                })
                                            }
                                        />
                                        Gaya percakapan (fillers natural)
                                    </label>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.humanize.avoid_markdown}
                                            onChange={(e) =>
                                                setData('humanize', {
                                                    ...data.humanize,
                                                    avoid_markdown: e.target.checked,
                                                })
                                            }
                                        />
                                        Hindari format markdown
                                    </label>
                                </>
                            )}
                        </div>

                        <div className="rounded-lg border border-hairline bg-surface-soft p-4">
                            <Label>Simpan sebagai template personal</Label>
                            <p className="mt-1 text-xs text-muted">
                                Template hanya tersimpan di akun Anda dan bisa dipakai ulang kapan saja.
                            </p>
                            <div className="mt-3 flex gap-2">
                                <Input
                                    value={templateName}
                                    onChange={(e) => setTemplateName(e.target.value)}
                                    placeholder="Nama template"
                                    className="flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={saveAsTemplate}
                                    disabled={savingTemplate || !templateName.trim()}
                                >
                                    {savingTemplate ? 'Menyimpan...' : 'Simpan template'}
                                </Button>
                            </div>
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
