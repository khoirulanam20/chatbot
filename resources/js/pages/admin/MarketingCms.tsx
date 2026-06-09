import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { Save, Palette } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface BrandColors {
    primary: string;
    primary_active: string;
    brand_accent: string;
    accent_muted: string;
    ink: string;
}

interface Props {
    brandColors: BrandColors;
}

export default function MarketingCms({ brandColors }: Props) {
    const { data, setData, put, processing, errors } = useForm<BrandColors>(brandColors);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/admin/marketing');
    };

    return (
        <Layout title="CMS Landing Page">
            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">CMS Landing Page</h1>
                    <p className="text-muted-foreground">Kelola warna brand dan tampilan halaman depan.</p>
                </div>

                <div className="grid gap-6 md:grid-cols-[1fr_300px]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Palette className="h-5 w-5" />
                                Warna Brand
                            </CardTitle>
                            <CardDescription>
                                Sesuaikan warna utama yang digunakan di landing page.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="primary">Primary Color</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="primary"
                                                type="color"
                                                value={data.primary}
                                                onChange={(e) => setData('primary', e.target.value)}
                                                className="h-10 w-14 p-1 cursor-pointer"
                                            />
                                            <Input
                                                type="text"
                                                value={data.primary}
                                                onChange={(e) => setData('primary', e.target.value)}
                                                className="flex-1 font-mono uppercase"
                                                pattern="^#[0-9A-Fa-f]{6}$"
                                            />
                                        </div>
                                        {errors.primary && <p className="text-sm text-destructive">{errors.primary}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="primary_active">Primary Active (Hover)</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="primary_active"
                                                type="color"
                                                value={data.primary_active}
                                                onChange={(e) => setData('primary_active', e.target.value)}
                                                className="h-10 w-14 p-1 cursor-pointer"
                                            />
                                            <Input
                                                type="text"
                                                value={data.primary_active}
                                                onChange={(e) => setData('primary_active', e.target.value)}
                                                className="flex-1 font-mono uppercase"
                                                pattern="^#[0-9A-Fa-f]{6}$"
                                            />
                                        </div>
                                        {errors.primary_active && <p className="text-sm text-destructive">{errors.primary_active}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="brand_accent">Brand Accent</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="brand_accent"
                                                type="color"
                                                value={data.brand_accent}
                                                onChange={(e) => setData('brand_accent', e.target.value)}
                                                className="h-10 w-14 p-1 cursor-pointer"
                                            />
                                            <Input
                                                type="text"
                                                value={data.brand_accent}
                                                onChange={(e) => setData('brand_accent', e.target.value)}
                                                className="flex-1 font-mono uppercase"
                                                pattern="^#[0-9A-Fa-f]{6}$"
                                            />
                                        </div>
                                        {errors.brand_accent && <p className="text-sm text-destructive">{errors.brand_accent}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="accent_muted">Accent Muted (Background)</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="accent_muted"
                                                type="color"
                                                value={data.accent_muted}
                                                onChange={(e) => setData('accent_muted', e.target.value)}
                                                className="h-10 w-14 p-1 cursor-pointer"
                                            />
                                            <Input
                                                type="text"
                                                value={data.accent_muted}
                                                onChange={(e) => setData('accent_muted', e.target.value)}
                                                className="flex-1 font-mono uppercase"
                                                pattern="^#[0-9A-Fa-f]{6}$"
                                            />
                                        </div>
                                        {errors.accent_muted && <p className="text-sm text-destructive">{errors.accent_muted}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="ink">Ink (Text Color)</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="ink"
                                                type="color"
                                                value={data.ink}
                                                onChange={(e) => setData('ink', e.target.value)}
                                                className="h-10 w-14 p-1 cursor-pointer"
                                            />
                                            <Input
                                                type="text"
                                                value={data.ink}
                                                onChange={(e) => setData('ink', e.target.value)}
                                                className="flex-1 font-mono uppercase"
                                                pattern="^#[0-9A-Fa-f]{6}$"
                                            />
                                        </div>
                                        {errors.ink && <p className="text-sm text-destructive">{errors.ink}</p>}
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Live Preview</CardTitle>
                                <CardDescription>Simulasi tampilan komponen</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div 
                                    className="rounded-xl border p-6 space-y-6"
                                    style={{
                                        backgroundColor: '#FAFAF9',
                                        color: data.ink,
                                    }}
                                >
                                    <div>
                                        <span 
                                            className="mb-3 block text-sm font-medium"
                                            style={{ color: data.primary }}
                                        >
                                            Eyebrow Text
                                        </span>
                                        <h3 className="font-bold text-2xl" style={{ color: data.ink }}>
                                            Heading Sample
                                        </h3>
                                    </div>

                                    <div className="flex gap-3">
                                        <button 
                                            className="px-4 py-2 rounded-md text-sm font-semibold text-white transition-colors"
                                            style={{ backgroundColor: data.primary }}
                                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = data.primary_active}
                                            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = data.primary}
                                        >
                                            Primary Button
                                        </button>
                                    </div>

                                    <div 
                                        className="p-4 rounded-lg flex items-center gap-3"
                                        style={{ backgroundColor: data.accent_muted }}
                                    >
                                        <div 
                                            className="w-8 h-8 rounded-full flex items-center justify-center text-white"
                                            style={{ backgroundColor: data.brand_accent }}
                                        >
                                            ✨
                                        </div>
                                        <span className="text-sm font-medium" style={{ color: data.primary }}>
                                            Highlight Box
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
