<script setup lang="ts">
import { X } from "lucide-vue-next";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    useInertifyTableFilters,
    useInertifyTableFilterLabels,
    useInertifyTableSearchFilter,
    useDraftFilterEditor
} from "../../../../../../resources/js/index";
import type { ActiveFilterPill } from "../../../../../../resources/js/index";
import { useTableContext } from "./tableContext";

const context = useTableContext();
const tableFilters = useInertifyTableFilters(context.table);

const {
    items,
    active,
    reset,
} = tableFilters;

const {
    ruleLabel,
    summaryLabel,
} = useInertifyTableFilterLabels(tableFilters);

const {
    draftFilterKey,
    draftRule,
    draftTextValue,
    draftSelectValue,
    draftRangeFrom,
    draftRangeTo,
    addFilterOptions,
    draftFilter,
    availableDraftRules,
    prepareAddDraft,
    clearFilterByKey,
    prepareEditDraft,
    syncDraftFromFilter,
    applyDraftFilter,
} = useDraftFilterEditor(tableFilters);

const { searchTerm, searchFilter } = useInertifyTableSearchFilter(tableFilters, {
    preferredKeys: ["name"],
    debounceMs: 300,
});

const addFilterBuilderOpen = ref(false);
const openEditorId = ref<string | null>(null);

function openAddFilterBuilder(): void {
    prepareAddDraft();
    addFilterBuilderOpen.value = true;
}

function openFilterEditor(pill: ActiveFilterPill): void {
    prepareEditDraft(pill.key);
    openEditorId.value = pill.id;
}

function onFilterEditorOpenChange(pill: ActiveFilterPill, open: boolean): void {
    if (open) {
        openFilterEditor(pill);

        return;
    }

    if (openEditorId.value === pill.id) {
        openEditorId.value = null;
    }
}

function applyDraftFilterAndClose(): void {
    applyDraftFilter();
    addFilterBuilderOpen.value = false;
    openEditorId.value = null;
}

function cancelDraftFilter(): void {
    addFilterBuilderOpen.value = false;
    openEditorId.value = null;
}
</script>

<template>
    <div v-if="items.length > 0" class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <Input v-if="searchFilter" v-model="searchTerm" placeholder="Search..." class="w-[280px]" />

            <DropdownMenu v-for="pill in active" :key="`pill-${pill.id}`" :open="openEditorId === pill.id"
                @update:open="(open) => onFilterEditorOpenChange(pill, open)">
                <DropdownMenuTrigger as-child>
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-md border bg-muted/40 px-3 py-1.5 text-sm">
                        <span>{{ summaryLabel(pill.filter, pill.value) }}</span>
                        <span class="text-muted-foreground" @click.stop="clearFilterByKey(pill.key)">
                            <X class="size-3" />
                        </span>
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="start" class="w-[360px] p-4">
                    <div v-if="draftFilter" class="space-y-3">
                        <div class="space-y-2">
                            <p class="text-xs text-muted-foreground">Rule</p>
                            <Select v-model="draftRule">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="rule in availableDraftRules"
                                        :key="`pill-rule-${draftFilter.key}-${rule}`" :value="rule">
                                        {{ ruleLabel(rule, draftFilter) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="draftRule !== 'has_any_value'" class="space-y-2">
                            <p class="text-xs text-muted-foreground">Value</p>
                            <Input v-if="draftFilter.control === 'text'" v-model="draftTextValue"
                                :placeholder="`Enter ${draftFilter.label.toLowerCase()}`" />

                            <Select v-else-if="draftFilter.control === 'select'" v-model="draftSelectValue">
                                <SelectTrigger>
                                    <SelectValue :placeholder="`Select ${draftFilter.label.toLowerCase()}`" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in draftFilter.selectOptions"
                                        :key="`pill-option-${draftFilter.key}-${option.value}`" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <Input v-else-if="draftRule === 'greater_than'"
                                :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                v-model="draftRangeFrom" placeholder="Value" />

                            <Input v-else-if="draftRule === 'less_than'"
                                :type="draftFilter.control === 'date-range' ? 'date' : 'number'" v-model="draftRangeTo"
                                placeholder="Value" />

                            <div v-else class="grid grid-cols-2 gap-2">
                                <Input :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                    v-model="draftRangeFrom" placeholder="From" />
                                <Input :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                    v-model="draftRangeTo" placeholder="To" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <Button variant="outline" @click="cancelDraftFilter">Cancel</Button>
                            <Button @click="applyDraftFilterAndClose">Apply Filter</Button>
                        </div>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu v-model:open="addFilterBuilderOpen">
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" :disabled="items.length === 0" @click="openAddFilterBuilder">
                        + Add filter
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="start" class="w-[700px] p-0">
                    <div class="grid min-h-[260px] grid-cols-[220px_1fr]">
                        <div class="border-r p-2">
                            <button v-for="filter in addFilterOptions" :key="`add-${filter.key}`" type="button"
                                class="w-full rounded-sm px-3 py-2 text-left text-sm transition hover:bg-muted"
                                :class="draftFilterKey === filter.key ? 'bg-muted font-medium' : ''"
                                @click="draftFilterKey = filter.key; syncDraftFromFilter(filter.key)">
                                {{ filter.label }}
                            </button>
                        </div>

                        <div class="space-y-4 p-4">
                            <div v-if="draftFilter" class="space-y-3">
                                <div class="space-y-2">
                                    <p class="text-xs text-muted-foreground">Rule</p>
                                    <Select v-model="draftRule">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="rule in availableDraftRules"
                                                :key="`add-rule-${draftFilter.key}-${rule}`" :value="rule">
                                                {{ ruleLabel(rule, draftFilter) }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div v-if="draftRule !== 'has_any_value'" class="space-y-2">
                                    <p class="text-xs text-muted-foreground">Value</p>
                                    <Input v-if="draftFilter.control === 'text'" v-model="draftTextValue"
                                        :placeholder="`Enter ${draftFilter.label.toLowerCase()}`" />

                                    <Select v-else-if="draftFilter.control === 'select'" v-model="draftSelectValue">
                                        <SelectTrigger>
                                            <SelectValue :placeholder="`Select ${draftFilter.label.toLowerCase()}`" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in draftFilter.selectOptions"
                                                :key="`add-option-${draftFilter.key}-${option.value}`"
                                                :value="option.value">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Input v-else-if="draftRule === 'greater_than'"
                                        :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                        v-model="draftRangeFrom" placeholder="Value" />

                                    <Input v-else-if="draftRule === 'less_than'"
                                        :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                        v-model="draftRangeTo" placeholder="Value" />

                                    <div v-else class="grid grid-cols-2 gap-2">
                                        <Input :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                            v-model="draftRangeFrom" placeholder="From" />
                                        <Input :type="draftFilter.control === 'date-range' ? 'date' : 'number'"
                                            v-model="draftRangeTo" placeholder="To" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <Button variant="outline" @click="cancelDraftFilter">Cancel</Button>
                                    <Button @click="applyDraftFilterAndClose">Apply Filter</Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <Button v-if="active.length > 0" variant="ghost" size="sm" @click="reset">
                Reset
            </Button>
        </div>
    </div>
</template>
