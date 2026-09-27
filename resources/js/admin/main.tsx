import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import { AdminConfigProvider } from './lib/config';
import { AdminModeProvider } from './lib/adminMode';
import { I18nProvider } from './lib/i18n';
import './styles.css';

const root = document.getElementById('agentic-admin-root');

if (root) {
    createRoot(root).render(
        <StrictMode>
            <AdminConfigProvider>
                <I18nProvider>
                    <AdminModeProvider>
                        <BrowserRouter basename={window.__AGENTIC_ADMIN__.webPrefix}>
                            <App />
                        </BrowserRouter>
                    </AdminModeProvider>
                </I18nProvider>
            </AdminConfigProvider>
        </StrictMode>,
    );
}
