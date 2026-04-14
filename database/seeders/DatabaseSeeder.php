<?php

namespace Database\Seeders;

use App\Models\BotEmbedConfig;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'tenant_id' => null,
            'name'      => 'Super Admin',
            'email'     => 'superadmin@chatbot.test',
            'password'  => Hash::make('password'),
            'role'      => 'super_admin',
        ]);

        $tenant = Tenant::create([
            'name'      => 'Demo Business',
            'slug'      => 'demo',
            'plan'      => 'pro',
            'is_active' => true,
            'settings'  => [],
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Demo',
            'email'     => 'admin@demo.test',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
        ]);

        $operator = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Operator Demo',
            'email'     => 'operator@demo.test',
            'password'  => Hash::make('password'),
            'role'      => 'operator',
        ]);

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'       => $tenant->id,
            'name'            => 'Ava',
            'system_prompt'   => 'Kamu adalah asisten layanan pelanggan yang ramah dan membantu dari Demo Business. Jawab pertanyaan berdasarkan knowledge base yang tersedia. Jika tidak menemukan jawaban, arahkan user ke agen manusia.',
            'model'           => 'gpt-4o',
            'temperature'     => 0.7,
            'max_context'     => 10,
            'language'        => 'id',
            'fallback_message' => 'Maaf, saya tidak dapat menemukan jawaban untuk pertanyaan Anda. Apakah Anda ingin saya hubungkan dengan agen kami?',
            'handoff_triggers' => ['agen', 'manusia', 'cs', 'operator', 'bicara dengan orang'],
            'is_active'       => true,
        ]);

        BotEmbedConfig::create([
            'chatbot_id'    => $chatbot->id,
            'primary_color' => '#4F46E5',
            'position'      => 'bottom-right',
            'size'          => 'normal',
            'greeting'      => 'Halo! 👋 Saya Ava, asisten virtual Demo Business. Ada yang bisa saya bantu?',
            'quick_replies' => [
                'Informasi produk',
                'Jam operasional',
                'Cara pemesanan',
                'Hubungi agen',
            ],
            'branding' => [
                'company_name' => 'Demo Business',
                'show_branding' => true,
            ],
            'sound_enabled' => false,
        ]);

        $this->command->info('Seeder selesai!');
        $this->command->info('Super Admin: superadmin@chatbot.test / password');
        $this->command->info('Admin Demo: admin@demo.test / password');
        $this->command->info('Operator Demo: operator@demo.test / password');
    }
}
