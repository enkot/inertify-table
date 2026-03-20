<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useHeadlessTablePagination } from "../../../../../../resources/js/index";
import { useTableContext } from "./tableContext";

const context = useTableContext();
const from = context.from;
const to = context.to;
const total = context.total;

const {
    page,
    lastPage,
    hasPrevious,
    hasNext,
    previous,
    next,
    perPage,
    perPageOptions,
    setPerPage,
} = useHeadlessTablePagination(context.table);
</script>

<template>
    <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
            Showing {{ from ?? 0 }}-{{ to ?? 0 }} of {{ total }}
        </p>

        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">Per page</span>
            <Select :model-value="String(perPage)" @update:model-value="(value) => setPerPage(Number(value))">
                <SelectTrigger class="w-24">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="option in perPageOptions" :key="option" :value="String(option)">
                        {{ option }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <Button :disabled="!hasPrevious" variant="outline" @click="previous">
            Previous
        </Button>
        <span class="min-w-24 text-center text-sm text-muted-foreground">
            Page {{ page }} / {{ lastPage }}
        </span>
        <Button :disabled="!hasNext" variant="outline" @click="next">
            Next
        </Button>
    </div>
</template>
