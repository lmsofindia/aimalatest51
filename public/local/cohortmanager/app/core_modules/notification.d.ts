declare module 'core/notification' {
    export function exception(error: any): void;
    export function deleteCancel(
        title: string | Promise<string>,
        question: string | Promise<string>,
        deleteLabel: string | Promise<string>,
        deleteCallback: () => void,
        cancelCallback?: () => void,
        options?: { triggerElement?: HTMLElement }
    ): Promise<any>;
    export function addNotification(notification: Object);
}