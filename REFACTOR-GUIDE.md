# 🔄 REFACTOR GUIDE: Chatbot App → shadcn/ui + Inertia.js

**Repository:** https://github.com/khoirulanam20/chatbot.git
**Framework:** Laravel + Livewire → React + Inertia.js + shadcn/ui
**Design Reference:** Cal.com Design System
**Date:** 31 May 2026

---

## 📊 Current vs Target

| Layer | Current | Target |
|-------|---------|--------|
| Frontend | Blade + Livewire + Alpine.js + CDN Tailwind | React + Inertia.js + shadcn/ui + Vite Tailwind |
| Styling | CDN Tailwind (no design system) | Cal.com design system |
| Real-time | Livewire `wire:poll` | Inertia reload + setInterval |
| Auth | Session forms | Inertia shared auth props |
| Widget | `public/chatbot.js` | ✅ **Keep unchanged** |

---

## 🎨 Cal.com Design Tokens

### Colors

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        // Canvas
        canvas: '#ffffff',
        
        // Primary
        primary: '#111111',
        'primary-active': '#242424',
        
        // Surface
        'surface-soft': '#f8f9fa',
        'surface-card': '#f5f5f5',
        'surface-strong': '#e5e7eb',
        'surface-dark': '#101010',
        'surface-dark-elevated': '#1a1a1a',
        
        // Hairline
        hairline: '#e5e7eb',
        'hairline-soft': '#f3f4f6',
        
        // Text
        ink: '#111111',
        body: '#374151',
        muted: '#6b7280',
        'muted-soft': '#898989',
        'on-primary': '#ffffff',
        'on-dark': '#ffffff',
        'on-dark-soft': '#a1a1aa',
        
        // Brand Accent
        'brand-accent': '#3b82f6',
        
        // Semantic
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
        
        // Badge Pastels
        'badge-orange': '#fb923c',
        'badge-pink': '#ec4899',
        'badge-violet': '#8b5cf6',
        'badge-emerald': '#34d399',
      },
      fontFamily: {
        display: ['"Cal Sans"', 'Inter', '-apple-system', 'sans-serif'],
        body: ['Inter', '-apple-system', 'sans-serif'],
      },
      borderRadius: {
        xs: '4px',
        sm: '6px',
        md: '8px',
        lg: '12px',
        xl: '16px',
        pill: '9999px',
      },
      spacing: {
        xxs: '4px',
        xs: '8px',
        sm: '12px',
        md: '16px',
        lg: '24px',
        xl: '32px',
        xxl: '48px',
        section: '96px',
      },
    },
  },
}
```

### Typography

| Token | Size | Weight | Line Height | Letter Spacing | Use |
|-------|------|--------|-------------|----------------|-----|
| display-xl | 64px | 600 | 1.05 | -2px | Homepage h1 |
| display-lg | 48px | 600 | 1.1 | -1.5px | Section heads |
| display-md | 36px | 600 | 1.15 | -1px | Sub-section heads |
| display-sm | 28px | 600 | 1.2 | -0.5px | CTA-band heads |
| title-lg | 22px | 600 | 1.3 | -0.3px | Pricing plan names |
| title-md | 18px | 600 | 1.4 | 0 | Feature card titles |
| title-sm | 16px | 600 | 1.4 | 0 | Small card titles |
| body-md | 16px | 400 | 1.5 | 0 | Default running-text |
| body-sm | 14px | 400 | 1.5 | 0 | Footer body |
| caption | 13px | 500 | 1.4 | 0 | Badge labels |
| button | 14px | 600 | 1.0 | 0 | Button labels |
| nav-link | 14px | 500 | 1.4 | 0 | Nav items |

---

## 📁 Phase 1: Foundation (Day 1-2)

### 1.1 Install Inertia.js

```bash
# Backend
composer require inertiajs/laravel-inertia

# Frontend
npm install @inertiajs/react react react-dom
npm install -D @types/react @types/react-dom typescript
```

### 1.2 Setup HandleInertiaRequests Middleware

```php
// app/Http/Middleware/HandleInertiaRequests.php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
```

### 1.3 Register Middleware

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\HandleInertiaRequests::class,
];
```

### 1.4 Create app.blade.php Root View

```blade
<!-- resources/views/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Chatbot') }}</title>
    @viteReactRefresh
    @vite('resources/css/app.css', 'resources/js/app.tsx')
</head>
<body>
    @inertia
</body>
</html>
```

### 1.5 Setup Vite Config

```js
// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
})
```

### 1.6 Create React Entry Point

```tsx
// resources/js/app.tsx
import './bootstrap'
import '../css/app.css'

import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const appName = import.meta.env.VITE_APP_NAME || 'Chatbot'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        return createRoot(el).render(<App {...props} />)
    },
    progress: {
        color: '#111111',
    },
})
```

### 1.7 Install shadcn/ui

```bash
# Initialize shadcn/ui
npx shadcn-ui@latest init

# Add components
npx shadcn-ui@latest add button
npx shadcn-ui@latest add card
npx shadcn-ui@latest add dialog
npx shadcn-ui@latest add input
npx shadcn-ui@latest add select
npx shadcn-ui@latest add table
npx shadcn-ui@latest add badge
npx shadcn-ui@latest add tabs
npx shadcn-ui@latest add dropdown-menu
npx shadcn-ui@latest add avatar
npx shadcn-ui@latest add separator
```

### 1.8 Install Additional Dependencies

```bash
# Charts (for dashboard)
npm install recharts

# Icons
npm install lucide-react

# Utilities
npm install clsx tailwind-merge
```

---

## 📁 Phase 2: Backend Adaptation (Day 2-3)

### 2.1 Convert Controllers to Inertia Responses

#### DashboardController.php

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $chatbots = Chatbot::all();
        
        // Stats
        $stats = [
            'total_tenants' => Tenant::count(),
            'total_chatbots' => Chatbot::count(),
            'total_conversations' => Conversation::count(),
            'active_conversations' => Conversation::whereIn('status', ['waiting', 'active'])->count(),
        ];
        
        // Recent conversations
        $recentConversations = Conversation::with(['chatbot', 'contact'])
            ->latest()
            ->take(10)
            ->get();
        
        return inertia('Dashboard', compact('stats', 'recentConversations', 'chatbots'));
    }
}
```

#### ChatbotConfigController.php

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\BotEmbedConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotConfigController extends Controller
{
    public function index()
    {
        $chatbots = Chatbot::with(['embedConfig', 'waInstance'])->get();
        return inertia('chatbot/Index', compact('chatbots'));
    }
    
    public function create()
    {
        return inertia('chatbot/Create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $chatbot = Chatbot::create([
            ...$validated,
            'tenant_id' => auth()->user()->tenant_id,
            'uuid' => Str::uuid(),
        ]);
        
        // Create embed config
        BotEmbedConfig::create([
            'chatbot_id' => $chatbot->id,
            'primary_color' => '#3b82f6',
            'bot_name' => $chatbot->name,
        ]);
        
        return redirect()->route('admin.chatbot.index')
            ->with('success', 'Chatbot created successfully');
    }
    
    public function edit(Chatbot $chatbot)
    {
        $chatbot->load(['embedConfig', 'waInstance']);
        return inertia('chatbot/Edit', compact('chatbot'));
    }
    
    public function update(Request $request, Chatbot $chatbot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $chatbot->update($validated);
        
        return redirect()->route('admin.chatbot.index')
            ->with('success', 'Chatbot updated successfully');
    }
    
    public function destroy(Chatbot $chatbot)
    {
        $chatbot->delete();
        
        return redirect()->route('admin.chatbot.index')
            ->with('success', 'Chatbot deleted successfully');
    }
    
    public function embedCode(Chatbot $chatbot)
    {
        $chatbot->load('embedConfig');
        return inertia('chatbot/EmbedCode', compact('chatbot'));
    }
}
```

#### ConversationController.php

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::with(['chatbot', 'contact', 'agent']);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('chatbot_id')) {
            $query->where('chatbot_id', $request->chatbot_id);
        }
        
        if ($request->filled('search')) {
            $query->whereHas('contact', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('identifier', 'like', "%{$request->search}%");
            });
        }
        
        $conversations = $query->latest()->paginate(20);
        
        return inertia('conversations/Index', compact('conversations'));
    }
    
    public function show(Conversation $conversation)
    {
        $conversation->load(['chatbot', 'contact', 'messages', 'agent']);
        
        return inertia('conversations/Show', compact('conversation'));
    }
    
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
        
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'agent',
            'content' => $validated['content'],
            'metadata' => json_encode([
                'agent_id' => auth()->id(),
                'agent_name' => auth()->user()->name,
            ]),
        ]);
        
        $conversation->update(['status' => 'active']);
        
        return back()->with('success', 'Message sent');
    }
    
    public function export(Request $request)
    {
        $conversations = Conversation::with(['chatbot', 'contact', 'messages'])
            ->when($request->filled('start_date'), fn ($q) => $q->where('created_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->where('created_at', '<=', $request->end_date))
            ->get();
        
        $csv = $this->generateCsv($conversations);
        
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="conversations.csv"',
        ]);
    }
    
    private function generateCsv($conversations)
    {
        $headers = ['ID', 'Chatbot', 'Contact', 'Status', 'Created At', 'Messages'];
        
        $rows = $conversations->map(fn ($c) => [
            $c->id,
            $c->chatbot->name,
            $c->contact->name ?? $c->contact->identifier,
            $c->status,
            $c->created_at->format('Y-m-d H:i:s'),
            $c->messages->count(),
        ]);
        
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        
        return $callback();
    }
}
```

#### UserController.php

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('tenant')->get();
        return inertia('users/Index', compact('users'));
    }
    
    public function create()
    {
        return inertia('users/Create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,operator,viewer',
        ]);
        
        User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'tenant_id' => auth()->user()->tenant_id,
        ]);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully');
    }
    
    public function edit(User $user)
    {
        return inertia('users/Edit', compact('user'));
    }
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:super_admin,admin,operator,viewer',
        ]);
        
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }
        
        $user->update($validated);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }
    
    public function destroy(User $user)
    {
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }
}
```

---

## 📁 Phase 3: React Page Components (Day 3-5)

### 3.1 Page Structure

```
resources/js/pages/
├── auth/
│   └── Login.tsx
├── Dashboard.tsx
├── chatbot/
│   ├── Index.tsx
│   ├── Create.tsx
│   ├── Edit.tsx
│   └── EmbedCode.tsx
├── knowledge/
│   ├── Index.tsx
│   └── Show.tsx
├── conversations/
│   ├── Index.tsx
│   └── Show.tsx
├── users/
│   ├── Index.tsx
│   ├── Create.tsx
│   └── Edit.tsx
├── wa/
│   ├── Index.tsx
│   ├── Create.tsx
│   └── Edit.tsx
├── tenants/
│   ├── Index.tsx
│   ├── Create.tsx
│   └── Edit.tsx
├── settings/
│   └── Index.tsx
└── components/
    ├── Layout.tsx
    ├── StatsCard.tsx
    ├── DataTable.tsx
    ├── StatusBadge.tsx
    └── NavPillGroup.tsx
```

### 3.2 Layout Component

```tsx
// resources/js/components/Layout.tsx
import { ReactNode } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { 
    LayoutDashboard, 
    Bot, 
    MessageSquare, 
    Users, 
    Settings,
    LogOut 
} from 'lucide-react'

interface LayoutProps {
    children: ReactNode
}

const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Chatbots', href: '/admin/chatbot', icon: Bot },
    { name: 'Conversations', href: '/admin/conversations', icon: MessageSquare },
    { name: 'Users', href: '/admin/users', icon: Users },
    { name: 'Settings', href: '/admin/settings', icon: Settings },
]

export function Layout({ children }: LayoutProps) {
    const { url } = usePage()
    const { auth } = usePage().props

    return (
        <div className="min-h-screen bg-canvas">
            {/* Sidebar */}
            <aside className="fixed left-0 top-0 h-full w-64 bg-surface-dark text-on-dark">
                {/* Logo */}
                <div className="p-6">
                    <h1 className="font-display text-xl font-semibold tracking-tight">
                        Chatbot
                    </h1>
                </div>

                {/* Navigation */}
                <nav className="px-4">
                    {navigation.map((item) => {
                        const isActive = url.startsWith(item.href)
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`flex items-center gap-3 px-4 py-3 rounded-md mb-1 transition-colors ${
                                    isActive 
                                        ? 'bg-surface-dark-elevated text-on-dark' 
                                        : 'text-on-dark-soft hover:bg-surface-dark-elevated'
                                }`}
                            >
                                <item.icon className="w-5 h-5" />
                                <span className="text-sm font-medium">{item.name}</span>
                            </Link>
                        )
                    })}
                </nav>

                {/* User section */}
                <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-surface-dark-elevated">
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-surface-dark-elevated flex items-center justify-center">
                            <span className="text-sm font-medium">
                                {auth.user?.name?.charAt(0)}
                            </span>
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">{auth.user?.name}</p>
                            <p className="text-xs text-on-dark-soft truncate">{auth.user?.email}</p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            className="text-on-dark-soft hover:text-on-dark"
                        >
                            <LogOut className="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <main className="ml-64 min-h-screen">
                {/* Header */}
                <header className="sticky top-0 z-10 bg-canvas border-b border-hairline">
                    <div className="px-8 py-4">
                        <h1 className="font-display text-2xl font-semibold tracking-tight text-ink">
                            {/* Page title will be set by each page */}
                        </h1>
                    </div>
                </header>

                {/* Page content */}
                <div className="p-8">
                    {children}
                </div>
            </main>

            {/* Dark footer */}
            <footer className="bg-surface-dark text-on-dark-soft py-16">
                <div className="max-w-[1200px] mx-auto px-8">
                    <div className="grid grid-cols-4 gap-8">
                        <div>
                            <h3 className="font-display text-lg font-semibold text-on-dark mb-4">
                                Chatbot
                            </h3>
                            <p className="text-sm">
                                AI-powered chatbot platform for your business.
                            </p>
                        </div>
                        <div>
                            <h4 className="font-medium text-on-dark mb-4">Product</h4>
                            <ul className="space-y-2 text-sm">
                                <li><a href="#" className="hover:text-on-dark">Features</a></li>
                                <li><a href="#" className="hover:text-on-dark">Pricing</a></li>
                                <li><a href="#" className="hover:text-on-dark">Docs</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-medium text-on-dark mb-4">Company</h4>
                            <ul className="space-y-2 text-sm">
                                <li><a href="#" className="hover:text-on-dark">About</a></li>
                                <li><a href="#" className="hover:text-on-dark">Blog</a></li>
                                <li><a href="#" className="hover:text-on-dark">Contact</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-medium text-on-dark mb-4">Legal</h4>
                            <ul className="space-y-2 text-sm">
                                <li><a href="#" className="hover:text-on-dark">Privacy</a></li>
                                <li><a href="#" className="hover:text-on-dark">Terms</a></li>
                            </ul>
                        </div>
                    </div>
                    <div className="mt-12 pt-8 border-t border-surface-dark-elevated text-sm">
                        © 2026 Chatbot. All rights reserved.
                    </div>
                </div>
            </footer>
        </div>
    )
}
```

### 3.3 StatsCard Component

```tsx
// resources/js/components/StatsCard.tsx
import { ReactNode } from 'react'

interface StatsCardProps {
    title: string
    value: string | number
    icon?: ReactNode
    change?: number
}

export function StatsCard({ title, value, icon, change }: StatsCardProps) {
    return (
        <div className="bg-surface-card rounded-lg p-8">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-muted text-sm">{title}</p>
                    <p className="text-ink font-display text-2xl font-semibold tracking-tight mt-1">
                        {value}
                    </p>
                    {change !== undefined && (
                        <p className={`text-sm mt-2 ${change >= 0 ? 'text-success' : 'text-error'}`}>
                            {change >= 0 ? '+' : ''}{change}%
                        </p>
                    )}
                </div>
                {icon && (
                    <div className="text-muted">
                        {icon}
                    </div>
                )}
            </div>
        </div>
    )
}
```

### 3.4 StatusBadge Component

```tsx
// resources/js/components/StatusBadge.tsx
import { Badge } from '@/components/ui/badge'

interface StatusBadgeProps {
    status: string
}

const statusConfig: Record<string, { label: string; variant: string }> = {
    active: { label: 'Active', variant: 'success' },
    waiting: { label: 'Waiting', variant: 'warning' },
    closed: { label: 'Closed', variant: 'secondary' },
    pending: { label: 'Pending', variant: 'warning' },
    processing: { label: 'Processing', variant: 'default' },
    completed: { label: 'Completed', variant: 'success' },
    failed: { label: 'Failed', variant: 'destructive' },
}

export function StatusBadge({ status }: StatusBadgeProps) {
    const config = statusConfig[status] || { label: status, variant: 'secondary' }
    
    return (
        <Badge variant={config.variant as any}>
            {config.label}
        </Badge>
    )
}
```

### 3.5 NavPillGroup Component

```tsx
// resources/js/components/NavPillGroup.tsx
import { ReactNode } from 'react'

interface NavPillGroupProps {
    children: ReactNode
}

export function NavPillGroup({ children }: NavPillGroupProps) {
    return (
        <div className="inline-flex items-center gap-1 p-1 bg-surface-soft rounded-pill">
            {children}
        </div>
    )
}

interface NavPillItemProps {
    active?: boolean
    onClick: () => void
    children: ReactNode
}

export function NavPillItem({ active, onClick, children }: NavPillItemProps) {
    return (
        <button
            onClick={onClick}
            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                active 
                    ? 'bg-canvas text-ink shadow-sm' 
                    : 'text-muted hover:text-ink'
            }`}
        >
            {children}
        </button>
    )
}
```

---

## 📁 Phase 4: Page Implementations (Day 4-6)

### 4.1 Login Page

```tsx
// resources/js/pages/auth/Login.tsx
import { FormEventHandler } from 'react'
import { Head, useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    })

    const submit: FormEventHandler = (e) => {
        e.preventDefault()
        post('/login')
    }

    return (
        <>
            <Head title="Log in" />
            
            <div className="min-h-screen flex items-center justify-center bg-canvas">
                <div className="w-full max-w-md">
                    <div className="text-center mb-8">
                        <h1 className="font-display text-3xl font-semibold tracking-tight text-ink">
                            Welcome back
                        </h1>
                        <p className="text-muted mt-2">
                            Sign in to your account
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="mt-1"
                            />
                            {errors.email && (
                                <p className="text-error text-sm mt-1">{errors.email}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1"
                            />
                            {errors.password && (
                                <p className="text-error text-sm mt-1">{errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center">
                            <input
                                id="remember"
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="rounded border-hairline"
                            />
                            <label htmlFor="remember" className="ml-2 text-sm text-muted">
                                Remember me
                            </label>
                        </div>

                        <Button 
                            type="submit" 
                            className="w-full bg-primary text-on-primary hover:bg-primary-active"
                            disabled={processing}
                        >
                            Sign in
                        </Button>
                    </form>
                </div>
            </div>
        </>
    )
}
```

### 4.2 Dashboard Page

```tsx
// resources/js/pages/Dashboard.tsx
import { Head } from '@inertiajs/react'
import { Layout } from '@/components/Layout'
import { StatsCard } from '@/components/StatsCard'
import { StatusBadge } from '@/components/StatusBadge'
import { 
    Building2, 
    Bot, 
    MessageSquare, 
    Activity 
} from 'lucide-react'
import { 
    BarChart, 
    Bar, 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    ResponsiveContainer 
} from 'recharts'

interface DashboardProps {
    stats: {
        total_tenants: number
        total_chatbots: number
        total_conversations: number
        active_conversations: number
    }
    recentConversations: any[]
    chartData: any[]
}

export default function Dashboard({ stats, recentConversations, chartData }: DashboardProps) {
    return (
        <Layout>
            <Head title="Dashboard" />
            
            <div className="space-y-8">
                {/* Stats Grid */}
                <div className="grid grid-cols-4 gap-6">
                    <StatsCard 
                        title="Tenants" 
                        value={stats.total_tenants}
                        icon={<Building2 className="w-5 h-5" />}
                    />
                    <StatsCard 
                        title="Chatbots" 
                        value={stats.total_chatbots}
                        icon={<Bot className="w-5 h-5" />}
                    />
                    <StatsCard 
                        title="Conversations" 
                        value={stats.total_conversations}
                        icon={<MessageSquare className="w-5 h-5" />}
                    />
                    <StatsCard 
                        title="Active Now" 
                        value={stats.active_conversations}
                        icon={<Activity className="w-5 h-5" />}
                    />
                </div>

                {/* Chart */}
                <div className="bg-surface-card rounded-lg p-8">
                    <h2 className="font-display text-lg font-semibold text-ink mb-6">
                        Conversations Trend
                    </h2>
                    <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={chartData}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                <XAxis dataKey="date" stroke="#6b7280" />
                                <YAxis stroke="#6b7280" />
                                <Tooltip />
                                <Bar dataKey="count" fill="#111111" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Recent Conversations */}
                <div className="bg-surface-card rounded-lg p-8">
                    <h2 className="font-display text-lg font-semibold text-ink mb-6">
                        Recent Conversations
                    </h2>
                    <div className="space-y-4">
                        {recentConversations.map((conv) => (
                            <div 
                                key={conv.id}
                                className="flex items-center justify-between p-4 bg-canvas rounded-lg border border-hairline"
                            >
                                <div className="flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-full bg-surface-card flex items-center justify-center">
                                        <span className="text-sm font-medium">
                                            {conv.contact?.name?.charAt(0) || '?'}
                                        </span>
                                    </div>
                                    <div>
                                        <p className="font-medium text-ink">
                                            {conv.contact?.name || conv.contact?.identifier}
                                        </p>
                                        <p className="text-sm text-muted">
                                            {conv.chatbot?.name} • {conv.created_at}
                                        </p>
                                    </div>
                                </div>
                                <StatusBadge status={conv.status} />
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </Layout>
    )
}
```

### 4.3 Chatbot Index Page

```tsx
// resources/js/pages/chatbot/Index.tsx
import { Head, Link } from '@inertiajs/react'
import { Layout } from '@/components/Layout'
import { Button } from '@/components/ui/button'
import { DataTable } from '@/components/DataTable'
import { StatusBadge } from '@/components/StatusBadge'
import { Plus, Code, Pencil, Trash2 } from 'lucide-react'

interface ChatbotIndexProps {
    chatbots: any[]
}

export default function ChatbotIndex({ chatbots }: ChatbotIndexProps) {
    const columns = [
        {
            key: 'name',
            label: 'Name',
            render: (row: any) => (
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-surface-card flex items-center justify-center">
                        <span className="text-sm font-medium">{row.name.charAt(0)}</span>
                    </div>
                    <span className="font-medium">{row.name}</span>
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row: any) => <StatusBadge status={row.status || 'active'} />,
        },
        {
            key: 'conversations',
            label: 'Conversations',
            render: (row: any) => row.conversations_count || 0,
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row: any) => (
                <div className="flex items-center gap-2">
                    <Link href={`/admin/chatbot/${row.id}/embed-code`}>
                        <Button variant="ghost" size="sm">
                            <Code className="w-4 h-4" />
                        </Button>
                    </Link>
                    <Link href={`/admin/chatbot/${row.id}/edit`}>
                        <Button variant="ghost" size="sm">
                            <Pencil className="w-4 h-4" />
                        </Button>
                    </Link>
                    <Link 
                        href={`/admin/chatbot/${row.id}`} 
                        method="delete"
                        as="button"
                    >
                        <Button variant="ghost" size="sm">
                            <Trash2 className="w-4 h-4 text-error" />
                        </Button>
                    </Link>
                </div>
            ),
        },
    ]

    return (
        <Layout>
            <Head title="Chatbots" />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-display text-2xl font-semibold tracking-tight text-ink">
                            Chatbots
                        </h1>
                        <p className="text-muted mt-1">
                            Manage your chatbot configurations
                        </p>
                    </div>
                    <Link href="/admin/chatbot/create">
                        <Button className="bg-primary text-on-primary hover:bg-primary-active">
                            <Plus className="w-4 h-4 mr-2" />
                            Create Chatbot
                        </Button>
                    </Link>
                </div>

                <div className="bg-surface-card rounded-lg p-6">
                    <DataTable columns={columns} data={chatbots} />
                </div>
            </div>
        </Layout>
    )
}
```

### 4.4 Conversations Index Page

```tsx
// resources/js/pages/conversations/Index.tsx
import { useState, useEffect } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { Layout } from '@/components/Layout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/StatusBadge'
import { NavPillGroup, NavPillItem } from '@/components/NavPillGroup'
import { Search, MessageSquare } from 'lucide-react'

interface ConversationsIndexProps {
    conversations: any[]
    filters: {
        status?: string
        search?: string
        chatbot_id?: string
    }
    chatbots: any[]
}

export default function ConversationsIndex({ conversations, filters, chatbots }: ConversationsIndexProps) {
    const [search, setSearch] = useState(filters.search || '')
    const [status, setStatus] = useState(filters.status || 'all')

    // Polling for new conversations (replaces wire:poll)
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ 
                only: ['conversations'],
                preserveState: true,
                preserveScroll: true,
            })
        }, 5000)
        return () => clearInterval(interval)
    }, [])

    // Search debounce
    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get('/admin/conversations', 
                { search, status: status === 'all' ? undefined : status },
                { preserveState: true, replace: true }
            )
        }, 300)
        return () => clearTimeout(timeout)
    }, [search, status])

    return (
        <Layout>
            <Head title="Conversations" />
            
            <div className="space-y-6">
                <div>
                    <h1 className="font-display text-2xl font-semibold tracking-tight text-ink">
                        Conversations
                    </h1>
                    <p className="text-muted mt-1">
                        Monitor and respond to chatbot conversations
                    </p>
                </div>

                {/* Filters */}
                <div className="flex items-center gap-4">
                    <div className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" />
                        <Input
                            placeholder="Search conversations..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10"
                        />
                    </div>
                    
                    <NavPillGroup>
                        <NavPillItem 
                            active={status === 'all'} 
                            onClick={() => setStatus('all')}
                        >
                            All
                        </NavPillItem>
                        <NavPillItem 
                            active={status === 'waiting'} 
                            onClick={() => setStatus('waiting')}
                        >
                            Waiting
                        </NavPillItem>
                        <NavPillItem 
                            active={status === 'active'} 
                            onClick={() => setStatus('active')}
                        >
                            Active
                        </NavPillItem>
                        <NavPillItem 
                            active={status === 'closed'} 
                            onClick={() => setStatus('closed')}
                        >
                            Closed
                        </NavPillItem>
                    </NavPillGroup>
                </div>

                {/* Conversation List */}
                <div className="space-y-2">
                    {conversations.data.length === 0 ? (
                        <div className="bg-surface-card rounded-lg p-12 text-center">
                            <MessageSquare className="w-12 h-12 text-muted mx-auto mb-4" />
                            <p className="text-muted">No conversations found</p>
                        </div>
                    ) : (
                        conversations.data.map((conv) => (
                            <Link 
                                key={conv.id} 
                                href={`/admin/conversations/${conv.id}`}
                                className="block bg-surface-card rounded-lg p-4 hover:bg-surface-soft transition-colors"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 rounded-full bg-canvas flex items-center justify-center">
                                            <span className="text-sm font-medium">
                                                {conv.contact?.name?.charAt(0) || '?'}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="font-medium text-ink">
                                                {conv.contact?.name || conv.contact?.identifier}
                                            </p>
                                            <p className="text-sm text-muted">
                                                {conv.chatbot?.name} • {conv.last_message?.substring(0, 50)}...
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <span className="text-sm text-muted">
                                            {conv.messages_count} messages
                                        </span>
                                        <StatusBadge status={conv.status} />
                                    </div>
                                </div>
                            </Link>
                        ))
                    )}
                </div>

                {/* Pagination */}
                {conversations.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {conversations.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                className={`px-4 py-2 rounded-md text-sm ${
                                    link.active 
                                        ? 'bg-primary text-on-primary' 
                                        : 'bg-surface-card text-ink hover:bg-surface-soft'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </Layout>
    )
}
```

### 4.5 Conversations Show Page

```tsx
// resources/js/pages/conversations/Show.tsx
import { useState, useEffect, useRef } from 'react'
import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { Layout } from '@/components/Layout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/StatusBadge'
import { ArrowLeft, Send, Bot, User, Headphones } from 'lucide-react'

interface ConversationsShowProps {
    conversation: {
        id: number
        status: string
        chatbot: any
        contact: any
        agent: any
        messages: any[]
    }
}

export default function ConversationsShow({ conversation }: ConversationsShowProps) {
    const { data, setData, post, processing } = useForm({
        content: '',
    })
    const messagesEndRef = useRef<HTMLDivElement>(null)
    const [messages, setMessages] = useState(conversation.messages)

    // Polling for new messages
    useEffect(() => {
        const interval = setInterval(() => {
            fetch(`/admin/conversations/${conversation.id}/messages`)
                .then(res => res.json())
                .then(data => setMessages(data))
        }, 3000)
        return () => clearInterval(interval)
    }, [conversation.id])

    // Auto-scroll to bottom
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages])

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        if (!data.content.trim()) return
        
        post(`/admin/conversations/${conversation.id}/message`, {
            onSuccess: () => setData('content', ''),
        })
    }

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'user': return <User className="w-4 h-4" />
            case 'assistant': return <Bot className="w-4 h-4" />
            case 'agent': return <Headphones className="w-4 h-4" />
            default: return null
        }
    }

    const getRoleColor = (role: string) => {
        switch (role) {
            case 'user': return 'bg-surface-card'
            case 'assistant': return 'bg-canvas border border-hairline'
            case 'agent': return 'bg-brand-accent text-on-primary'
            default: return 'bg-surface-card'
        }
    }

    return (
        <Layout>
            <Head title={`Conversation with ${conversation.contact?.name || conversation.contact?.identifier}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/conversations">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="font-display text-xl font-semibold tracking-tight text-ink">
                                {conversation.contact?.name || conversation.contact?.identifier}
                            </h1>
                            <p className="text-sm text-muted">
                                {conversation.chatbot?.name} • {conversation.messages.length} messages
                            </p>
                        </div>
                    </div>
                    <StatusBadge status={conversation.status} />
                </div>

                {/* Chat Area */}
                <div className="bg-surface-card rounded-lg overflow-hidden">
                    {/* Messages */}
                    <div className="h-96 overflow-y-auto p-6 space-y-4">
                        {messages.map((msg) => (
                            <div 
                                key={msg.id}
                                className={`flex gap-3 ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                            >
                                {msg.role !== 'user' && (
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${getRoleColor(msg.role)}`}>
                                        {getRoleIcon(msg.role)}
                                    </div>
                                )}
                                <div className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${getRoleColor(msg.role)}`}>
                                    <p className={`text-sm ${msg.role === 'agent' ? 'text-on-primary' : 'text-ink'}`}>
                                        {msg.content}
                                    </p>
                                    <p className={`text-xs mt-1 ${msg.role === 'agent' ? 'text-on-primary/70' : 'text-muted'}`}>
                                        {msg.created_at}
                                    </p>
                                </div>
                                {msg.role === 'user' && (
                                    <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                                        <User className="w-4 h-4 text-on-primary" />
                                    </div>
                                )}
                            </div>
                        ))}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <div className="border-t border-hairline p-4">
                        <form onSubmit={handleSubmit} className="flex gap-2">
                            <Input
                                value={data.content}
                                onChange={(e) => setData('content', e.target.value)}
                                placeholder="Type your message..."
                                className="flex-1"
                            />
                            <Button 
                                type="submit" 
                                disabled={processing || !data.content.trim()}
                                className="bg-primary text-on-primary hover:bg-primary-active"
                            >
                                <Send className="w-4 h-4" />
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </Layout>
    )
}
```

---

## 📁 Phase 5: Cleanup (Day 6-7)

### 5.1 Remove Old Files

```bash
# Remove Blade views
rm -rf resources/views/admin
rm -rf resources/views/auth
rm -rf resources/views/livewire
rm -rf resources/views/components
rm -rf resources/views/layouts
rm resources/views/welcome.blade.php

# Remove Livewire
composer remove livewire/livewire
rm -rf app/Http/Livewire
rm config/livewire.php

# Remove old CSS/JS
rm resources/js/app.js
rm resources/js/bootstrap.js
rm resources/css/app.css
```

### 5.2 Update package.json

```json
{
    "name": "chatbot",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "@types/react": "^18.2.0",
        "@types/react-dom": "^18.2.0",
        "@vitejs/plugin-react": "^4.0.0",
        "autoprefixer": "^10.4.14",
        "laravel-vite-plugin": "^1.0.0",
        "postcss": "^8.4.24",
        "tailwindcss": "^3.3.2",
        "typescript": "^5.0.0",
        "vite": "^5.0.0"
    },
    "dependencies": {
        "@inertiajs/react": "^1.0.0",
        "clsx": "^2.0.0",
        "lucide-react": "^0.300.0",
        "react": "^18.2.0",
        "react-dom": "^18.2.0",
        "recharts": "^2.10.0",
        "tailwind-merge": "^2.0.0"
    }
}
```

### 5.3 Create TypeScript Config

```json
// tsconfig.json
{
    "compilerOptions": {
        "target": "ES2020",
        "module": "ESNext",
        "lib": ["DOM", "DOM.Iterable", "ESNext"],
        "jsx": "react-jsx",
        "moduleResolution": "bundler",
        "strict": true,
        "esModuleInterop": true,
        "skipLibCheck": true,
        "forceConsistentCasingInFileNames": true,
        "resolveJsonModule": true,
        "isolatedModules": true,
        "noEmit": true,
        "paths": {
            "@/*": ["./resources/js/*"]
        }
    },
    "include": ["resources/js/**/*"],
    "exclude": ["node_modules"]
}
```

---

## 📋 CHECKLIST

### Backend (No Changes Needed)
- [x] Models — keep as-is
- [x] Services (RAG, SumoPod, DocumentProcessor, WaChatery) — keep as-is
- [x] Jobs — keep as-is
- [x] API Routes — keep as-is
- [x] `public/chatbot.js` — keep as-is
- [x] Database schema — keep as-is

### Frontend (Full Rewrite)
- [ ] Install Inertia.js + React
- [ ] Install shadcn/ui
- [ ] Setup Cal.com design tokens
- [ ] Create Layout component (sidebar + header + footer)
- [ ] Convert all controllers to Inertia responses
- [ ] Create 20+ React page components
- [ ] Create shared components (StatsCard, DataTable, etc.)
- [ ] Replace Livewire polling with Inertia reload
- [ ] Remove old Blade views
- [ ] Remove Livewire

---

## ⚠️ Key Considerations

1. **Real-time chat** — Livewire `wire:poll` → Use `router.reload({ only: [...] })` with `setInterval` or Pusher/WebSockets
2. **Chatbot widget** — `public/chatbot.js` stays completely decoupled
3. **Multi-tenancy** — Backend stays same, frontend just receives props
4. **File uploads** — Use `useForm()` from `@inertiajs/react`
5. **Auth** — Switch to Inertia shared props via middleware

---

## 📅 Timeline

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| Phase 1 | Day 1-2 | Inertia + shadcn/ui + design tokens installed |
| Phase 2 | Day 2-3 | Controllers converted to Inertia |
| Phase 3 | Day 3-5 | All page components created |
| Phase 4 | Day 5-6 | Cal.com design system applied |
| Phase 5 | Day 6-7 | Livewire removed, old Blade deleted |

---

**Guide by:** Hermes Agent
**Date:** 31 May 2026
