import type {
    Paginator,
    TableMeta,
} from '../../../../../../resources/js/index';

export interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string | null;
    created_at: string;
}

export interface TablePayload {
    rows: Paginator<UserRow>;
    meta: TableMeta;
}
