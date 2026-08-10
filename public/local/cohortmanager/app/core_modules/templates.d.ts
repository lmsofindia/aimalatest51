declare module 'core/templates' {
    const render: (templateName: string, context: any, themeName?: string) => Promise<{html: string, js: string}>;
    const renderForPromise: (templateName: string, context: any, themeName?: string) => Promise<{html: string, js: string}>;
    const prefetchTemplates: (templateNames: string[], themeName?: string) => Promise<void>;
    const renderPix: (key: string, component: string, title: string) => Promise<any>;
    const runTemplateJS: (source: string) => void;
    const replaceNodeContents: (element: any, newHTML: string, newJS: string) => any[];
    const replaceNode: (element: any, newHTML: string, newJS: string) => any[];
    const prependNodeContents: (element: any, html: string, js: string) => any[];
    const appendNodeContents: (element: any, html: string, js: string) => any[];
}