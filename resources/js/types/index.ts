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

export interface PageProps {
    auth: { user: AuthUser | null };
    flash: { success?: string; error?: string };
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

export interface Chatbot {
    id: number;
    name: string;
    tenant_id: number;
    model?: string;
    is_active?: boolean;
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
    status: string;
    chatbot_id: number;
    chatbot?: Chatbot;
    tenant?: Tenant;
}

export interface TenantFull extends Tenant {
    plan?: string;
    is_active?: boolean;
    users_count?: number;
    chatbots_count?: number;
}
