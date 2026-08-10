declare module 'core/ajax' {
    export function call(requests: any[]): Promise<any[]>;
    export function callWithFiles(request: any, files: File[]): Promise<any>;
}