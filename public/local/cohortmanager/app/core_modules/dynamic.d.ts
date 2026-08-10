declare module 'core_table/dynamic' {
    export function refreshTableContent(tableRoot: HTMLElement, resetContent?: boolean): Promise<HTMLElement>;
    export function updateTable(tableRoot: HTMLElement, options?: {
        sortBy?: string;
        sortOrder?: number;
        filters?: Record<string, any>;
        firstInitial?: string;
        lastInitial?: string;
        pageNumber?: number;
        pageSize?: number;
        hiddenColumns?: string[];
    }, refreshContent?: boolean): Promise<HTMLElement>;
    export function setFilters(tableRoot: HTMLElement, filters: Record<string, any>, refreshContent?: boolean): Promise<HTMLElement>;
    export function getFilters(tableRoot: HTMLElement): Record<string, any>;
    export function setSortOrder(tableRoot: HTMLElement, sortBy: string, sortOrder: number, refreshContent?: boolean): Promise<HTMLElement>;
    export function setPageNumber(tableRoot: HTMLElement, pageNumber: number, refreshContent?: boolean): Promise<HTMLElement>;
    export function getPageNumber(tableRoot: HTMLElement): number;
    export function setPageSize(tableRoot: HTMLElement, pageSize: number, refreshContent?: boolean): Promise<HTMLElement>;
    export function getPageSize(tableRoot: HTMLElement): number;
    export function setFirstInitial(tableRoot: HTMLElement, firstInitial: string, refreshContent?: boolean): Promise<HTMLElement>;
    export function getFirstInitial(tableRoot: HTMLElement): string;
    export function setLastInitial(tableRoot: HTMLElement, lastInitial: string, refreshContent?: boolean): Promise<HTMLElement>;
    export function getLastInitial(tableRoot: HTMLElement): string;
    export function hideColumn(tableRoot: HTMLElement, columnToHide: string, refreshContent?: boolean): Promise<HTMLElement>;
    export function showColumn(tableRoot: HTMLElement, columnToShow: string, refreshContent?: boolean): Promise<HTMLElement>;
    export function resetTablePreferences(tableRoot: HTMLElement): Promise<HTMLElement>;
    export function init(): void;
    export function getTableFromId(tableRegionId: string): HTMLElement;
    export const Events: {
        tableContentRefreshed: CustomEvent;
    };
}