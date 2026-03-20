import { inject, provide } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
import type { UseHeadlessTableApi } from '../../../../../../resources/js/index';
import type { UserRow } from './tableTypes';

export interface TableContext {
    table: UseHeadlessTableApi;
    rows: ComputedRef<UserRow[]>;
    total: ComputedRef<number>;
    from: ComputedRef<number | null>;
    to: ComputedRef<number | null>;
}

const TableContextKey: InjectionKey<TableContext> = Symbol('TableContext');

export function provideTableContext(context: TableContext): void {
    provide(TableContextKey, context);
}

export function useTableContext(): TableContext {
    const context = inject(TableContextKey, null);

    if (!context) {
        throw new Error('useTableContext must be used inside <TableRoot>.');
    }

    return context;
}
