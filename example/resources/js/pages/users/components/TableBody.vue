<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    HeadlessTableCells,
    HeadlessTableHeads,
    useTableSelection,
} from "../../../../../../resources/js/index";
import { useTableContext } from "./tableContext";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

const context = useTableContext();
const rows = context.rows;

const {
    selectionCount,
    isRowSelected,
    toggleRowSelected,
    areAllRowsSelected,
    areSomeRowsSelected,
    toggleAllRowsSelected,
    clearSelection,
} = useTableSelection(context.table);

function sortArrow(direction: string | null): string {
    return direction === "asc" ? "^" : "v";
}
</script>

<template>
    <div v-if="selectionCount > 0" class="flex items-center justify-between rounded-md border p-3">
        <p class="text-sm text-muted-foreground">
            {{ selectionCount }} row{{ selectionCount === 1 ? "" : "s" }} selected
        </p>
        <Button variant="outline" size="sm" @click="clearSelection">Clear selection</Button>
    </div>

    <Table>
        <TableHeader>
            <TableRow>
                <TableHead class="w-10">
                    <Checkbox :model-value="areAllRowsSelected(rows.map((user) => user.id))
                        ? true
                        : areSomeRowsSelected(rows.map((user) => user.id))
                            ? 'indeterminate'
                            : false
                        " @update:model-value="
                            toggleAllRowsSelected(rows.map((user) => user.id))
                            " />
                </TableHead>
                <slot name="heads" :table="context.table" :sort-arrow="sortArrow">
                    <HeadlessTableHeads :table="context.table">
                        <template #default="{ label, sortable, direction, toggleSort }">
                            <TableHead>
                                <Button v-if="sortable" variant="ghost" class="h-auto p-0 font-semibold"
                                    @click="toggleSort()">
                                    {{ label }} {{ sortArrow(direction) }}
                                </Button>
                                <span v-else>{{ label }}</span>
                            </TableHead>
                        </template>
                    </HeadlessTableHeads>
                </slot>
            </TableRow>
        </TableHeader>

        <TableBody>
            <TableRow v-for="user in rows" :key="user.id" :data-state="isRowSelected(user.id) ? 'selected' : undefined">
                <TableCell>
                    <Checkbox :model-value="isRowSelected(user.id)" @update:model-value="toggleRowSelected(user.id)" />
                </TableCell>
                <slot name="cells" :table="context.table" :row="user">
                    <HeadlessTableCells :table="context.table" :row="user">
                        <template #default="{ value }">
                            <TableCell>{{ value ?? "-" }}</TableCell>
                        </template>
                    </HeadlessTableCells>
                </slot>
            </TableRow>

            <TableRow v-if="rows.length === 0">
                <TableCell colspan="5" class="py-8 text-center text-muted-foreground">
                    No users found.
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
