import { usePage } from '@inertiajs/vue3';

export function __(key, replacements = {}) {
    const page = usePage();
    const translations = page.props.translations || {};
    let translation = translations[key] || key;

    Object.keys(replacements).forEach(replaceKey => {
        translation = translation.replace(`:${replaceKey}`, replacements[replaceKey]);
    });

    return translation;
}

export default {
    install(app) {
        app.config.globalProperties.__ = (key, replacements = {}) => {
            const translations = app.config.globalProperties.$page.props.translations || {};
            let translation = translations[key] || key;

            Object.keys(replacements).forEach(replaceKey => {
                translation = translation.replace(`:${replaceKey}`, replacements[replaceKey]);
            });

            return translation;
        }
    }
}
