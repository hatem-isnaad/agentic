import { Route, Routes } from 'react-router-dom';
import { AppShell } from './components/layout/AppShell';
import { AgentDetailPage } from './pages/AgentDetailPage';
import { AgentFormPage } from './pages/AgentFormPage';
import { ConversationDetailPage } from './pages/ConversationDetailPage';
import { ConversationsPage } from './pages/ConversationsPage';
import { DashboardPage } from './pages/DashboardPage';
import { EntityDetailPage } from './pages/EntityDetailPage';
import { KnowledgeSourceDetailPage } from './pages/KnowledgeSourceDetailPage';
import { KnowledgeSourceFormPage } from './pages/KnowledgeSourceFormPage';
import { MemoriesPage } from './pages/MemoriesPage';
import { McpServersPage } from './pages/McpServersPage';
import { ResourceListPage } from './pages/ResourceListPage';
import { SettingsPage } from './pages/SettingsPage';
import { SkillFormPage } from './pages/SkillFormPage';
import { ToolFormPage } from './pages/ToolFormPage';
import { WidgetSettingsFormPage } from './pages/WidgetSettingsFormPage';
import { WidgetEmbedTokensPage } from './pages/WidgetEmbedTokensPage';
import { WidgetSettingsListPage } from './pages/WidgetSettingsListPage';
import { WorkflowDetailPage } from './pages/WorkflowDetailPage';
import { WorkflowFormPage } from './pages/WorkflowFormPage';
import { DocsLayout } from './components/docs/DocsLayout';
import { DocChapterPage } from './pages/docs/DocChapterPage';
import { DocsIndexPage } from './pages/docs/DocsIndexPage';
import { JsonBuilderPage } from './pages/JsonBuilderPage';
import { SetupWizardPage } from './pages/SetupWizardPage';
import { CustomCodeToolsPage } from './pages/CustomCodeToolsPage';

const col = (key: string, labelKey: string, type?: 'status' | 'text') => ({ key, labelKey, type });

export default function App() {
    return (
        <Routes>
            <Route element={<AppShell />}>
                <Route index element={<DashboardPage />} />
                <Route path="setup" element={<SetupWizardPage />} />
                <Route path="docs" element={<DocsLayout />}>
                    <Route index element={<DocsIndexPage />} />
                    <Route path="json-builder" element={<JsonBuilderPage />} />
                    <Route path=":chapterSlug" element={<DocChapterPage />} />
                </Route>
                <Route path="settings" element={<SettingsPage />} />
                <Route path="memories" element={<MemoriesPage />} />
                <Route
                    path="agents"
                    element={
                        <ResourceListPage
                            titleKey="agents.title"
                            createKey="agents.create"
                            emptyKey="empty.agents"
                            apiPath="/agents"
                            createPath="/agents/new"
                            resourceBase="agents"
                            columns={[col('name', 'table.name'), col('slug', 'table.slug'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="agents/new" element={<AgentFormPage />} />
                <Route path="agents/:slug" element={<AgentDetailPage />} />
                <Route path="agents/:slug/edit" element={<AgentFormPage />} />
                <Route
                    path="skills"
                    element={
                        <ResourceListPage
                            titleKey="skills.title"
                            createKey="skills.create"
                            emptyKey="empty.skills"
                            apiPath="/skills"
                            createPath="/skills/new"
                            resourceBase="skills"
                            columns={[col('name', 'table.name'), col('slug', 'table.slug'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="skills/new" element={<SkillFormPage />} />
                <Route path="skills/:slug" element={<EntityDetailPage apiBase="/skills" resourceBase="skills" />} />
                <Route path="skills/:slug/edit" element={<SkillFormPage />} />
                <Route
                    path="tools"
                    element={
                        <ResourceListPage
                            titleKey="tools.title"
                            createKey="tools.create"
                            emptyKey="empty.tools"
                            apiPath="/tools"
                            createPath="/tools/new"
                            resourceBase="tools"
                            columns={[col('name', 'table.name'), col('slug', 'table.slug'), col('driver', 'table.driver'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="custom-code-tools" element={<CustomCodeToolsPage />} />
                <Route path="tools/new" element={<ToolFormPage />} />
                <Route path="tools/:slug" element={<EntityDetailPage apiBase="/tools" resourceBase="tools" />} />
                <Route path="tools/:slug/edit" element={<ToolFormPage />} />
                <Route path="mcp-servers" element={<McpServersPage />} />
                <Route
                    path="knowledge-sources"
                    element={
                        <ResourceListPage
                            titleKey="knowledge.title"
                            createKey="knowledge.create"
                            emptyKey="empty.knowledge"
                            apiPath="/knowledge-sources"
                            createPath="/knowledge-sources/new"
                            resourceBase="knowledge-sources"
                            columns={[col('name', 'table.name'), col('slug', 'table.slug'), col('driver', 'table.driver'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="knowledge-sources/new" element={<KnowledgeSourceFormPage />} />
                <Route path="knowledge-sources/:slug" element={<KnowledgeSourceDetailPage />} />
                <Route path="knowledge-sources/:slug/edit" element={<KnowledgeSourceFormPage />} />
                <Route
                    path="workflows"
                    element={
                        <ResourceListPage
                            titleKey="workflows.title"
                            createKey="workflows.create"
                            emptyKey="empty.workflows"
                            apiPath="/workflows"
                            createPath="/workflows/new"
                            resourceBase="workflows"
                            columns={[col('name', 'table.name'), col('slug', 'table.slug'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="workflows/new" element={<WorkflowFormPage />} />
                <Route path="workflows/:slug" element={<WorkflowDetailPage />} />
                <Route path="workflows/:slug/edit" element={<WorkflowFormPage />} />
                <Route
                    path="workflow-runs"
                    element={
                        <ResourceListPage
                            titleKey="workflow_runs.title"
                            createKey="workflows.create"
                            emptyKey="empty.workflow_runs"
                            apiPath="/workflow-runs"
                            resourceBase="workflow-runs"
                            slugKey="uuid"
                            columns={[col('uuid', 'table.uuid'), col('workflow_slug', 'table.workflow'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="workflow-runs/:uuid" element={<EntityDetailPage apiBase="/workflow-runs" resourceBase="workflow-runs" titleKey="workflow_runs.show_heading" nameField="uuid" />} />
                <Route
                    path="executions"
                    element={
                        <ResourceListPage
                            titleKey="executions.title"
                            createKey="agents.create"
                            emptyKey="empty.executions"
                            apiPath="/executions"
                            resourceBase="executions"
                            slugKey="id"
                            columns={[col('id', 'table.id'), col('agent_slug', 'table.agent'), col('status', 'table.status', 'status')]}
                        />
                    }
                />
                <Route path="executions/:id" element={<EntityDetailPage apiBase="/executions" resourceBase="executions" titleKey="executions.show_title" nameField="id" />} />
                <Route path="conversations" element={<ConversationsPage />} />
                <Route path="conversations/:id" element={<ConversationDetailPage />} />
                <Route path="widget-embed-tokens" element={<WidgetEmbedTokensPage />} />
                <Route path="widget-settings" element={<WidgetSettingsListPage />} />
                <Route path="widget-settings/:agentSlug/edit" element={<WidgetSettingsFormPage />} />
            </Route>
        </Routes>
    );
}
