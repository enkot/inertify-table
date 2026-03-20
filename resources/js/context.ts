import { inject, provide, type InjectionKey } from "vue";
import type { UseHeadlessTableApi } from "./useHeadlessTable";

const HeadlessTableContextKey: InjectionKey<UseHeadlessTableApi> = Symbol(
  "HeadlessTableContext",
);

export function provideHeadlessTableContext(api: UseHeadlessTableApi): void {
  provide(HeadlessTableContextKey, api);
}

export function tryUseHeadlessTableContext(): UseHeadlessTableApi | null {
  return inject(HeadlessTableContextKey, null);
}

export function useHeadlessTableContext(): UseHeadlessTableApi {
  const context = tryUseHeadlessTableContext();

  if (!context) {
    throw new Error(
      "useHeadlessTableContext must be used inside <HeadlessTableProvider>.",
    );
  }

  return context;
}
