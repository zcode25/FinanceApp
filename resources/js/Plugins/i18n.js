export default {
    install(app) {
        app.config.globalProperties.__ = (key, replacements = {}) => {
            // Access Inertia page props
            // Note: In templates, we can access $page. 
            // In the setup() or global properties, we need to be careful.
            // Fortunately, Inertia updates the $page global property on navigation.

            const translations = app.config.globalProperties.$page.props.translations || {};
            let translation = translations[key] || key;

            Object.keys(replacements).forEach(replaceKey => {
                translation = translation.replace(`:${replaceKey}`, replacements[replaceKey]);
            });

            return translation;
        }
    }
}
