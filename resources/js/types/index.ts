export interface Tenant {
    id: number;
    name: string;
    slug?: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: string;
    tenant_id: number | null;
    tenant: Tenant | null;
}

export interface AppNotification {
    id: string;
    title: string;
    body: string;
    url: string;
    read_at?: string | null;
    created_at: string;
}

export interface PageProps {
    csrf_token?: string;
    app?: { version: string };
    auth: { user: AuthUser | null };
    flash: { success?: string; error?: string };
    notifications?: {
        unread_count: number;
        recent: AppNotification[];
    };
    [key: string]: unknown;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface PersonaHumanizeSettings {
    enabled?: boolean;
    channels?: ('whatsapp' | 'web')[];
    emoji_level?: 'none' | 'minimal' | 'medium' | 'often';
    message_length?: 'short' | 'medium' | 'long';
    split_bubbles?: boolean;
    pacing_ms?: number;
    use_fillers?: boolean;
    avoid_markdown?: boolean;
}

export interface ChatbotPersona {
    role?: string;
    tone?: 'ramah' | 'formal' | 'profesional' | 'santai' | string;
    instructions?: string;
    restrictions?: string;
    greeting_style?: string;
    humanize?: PersonaHumanizeSettings;
}

export interface PersonaTemplateRecord {
    id: number;
    name: string;
    description?: string;
    role?: string;
    tone?: string;
    instructions?: string;
    restrictions?: string;
    greeting_style?: string;
    user?: { id: number; name: string };
    can_delete?: boolean;
}

export interface Chatbot {
    id: number;
    name: string;
    tenant_id: number;
    model?: string;
    is_active?: boolean;
    settings?: {
        agent_session_minutes?: number;
        agent_session_message?: string;
        persona?: ChatbotPersona;
    };
    embed_config?: EmbedConfig;
    wa_instance?: WaInstance;
    conversations_count?: number;
}

export interface EmbedConfig {
    primary_color?: string;
    position?: string;
    greeting?: string;
    size?: string;
    sound_enabled?: boolean;
    allow_file_upload?: boolean;
    quick_replies?: string[];
}

export interface Conversation {
    id: number;
    status: string;
    channel: string;
    chatbot_id: number;
    chatbot?: Chatbot;
    contact?: Contact;
    assigned_agent?: User;
    messages_count?: number;
    last_message_at?: string;
    created_at: string;
    is_ai_active?: boolean;
    agent_session_started_at?: string | null;
    agent_session_ends_at?: string | null;
}

export interface Contact {
    id: number;
    name?: string;
    identifier: string;
}

export interface Message {
    id: number;
    role: string;
    content: string;
    metadata?: {
        type?: string;
        url?: string;
        mime?: string;
        size?: number;
    } | null;
    created_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    tenant_id?: number;
    tenant?: Tenant;
}

export interface KnowledgeDocument {
    id: number;
    name: string;
    original_name?: string;
    type: string;
    status: string;
    chunk_count?: number;
    chatbot_id: number;
    chatbot?: Chatbot;
    created_at: string;
}

export interface WaInstance {
    id: number;
    phone_number: string;
    instance_id?: string | null;
    typing_enabled?: boolean;
    typing_duration_ms?: number;
    status: string;
    chatbot_id: number;
    chatbot?: Chatbot;
    tenant?: Tenant;
    metadata?: {
        last_error?: string | null;
        last_tested_at?: string | null;
        chatery_status?: string | null;
        chatery_phone?: string | null;
    } | null;
}

export interface TenantFull extends Tenant {
    plan?: string;
    is_active?: boolean;
    users_count?: number;
    chatbots_count?: number;
}
