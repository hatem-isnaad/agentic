<?php

return [
    'app_title' => 'Agentic Admin',

    'nav' => [
        'dashboard' => 'Dashboard',
        'agents' => 'Agents',
        'skills' => 'Skills',
        'tools' => 'Tools',
        'knowledge' => 'Knowledge',
        'executions' => 'Executions',
        'conversations' => 'Conversations',
    ],

    'locale' => [
        'label' => 'Language',
        'en' => 'English',
        'ar' => 'Arabic',
        'switch' => 'Switch language',
    ],

    'actions' => [
        'save' => 'Save',
        'update' => 'Update',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
        'create' => 'Create',
        'cancel' => 'Cancel',
        'index_documents' => 'Index documents',
        'confirm_delete_agent' => 'Delete this agent?',
    ],

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'instructions' => 'Instructions',
        'status' => 'Status',
        'provider' => 'Provider',
        'model' => 'Model',
        'skills' => 'Skills (comma-separated slugs)',
        'tools' => 'Tools (comma-separated slugs)',
        'driver' => 'Driver',
    ],

    'empty' => [
        'agents' => 'No agents yet.',
        'skills' => 'No skills yet.',
        'tools' => 'No tools yet.',
        'knowledge' => 'No knowledge sources yet.',
        'executions' => 'No executions yet.',
        'conversations' => 'No conversations yet.',
    ],

    'table' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'status' => 'Status',
        'driver' => 'Driver',
        'agent' => 'Agent',
        'user' => 'User',
        'started' => 'Started',
        'updated' => 'Updated',
        'id' => 'ID',
    ],

    'dashboard' => [
        'title' => 'Agentic Dashboard',
        'heading' => 'Agentic Admin (placeholder)',
        'intro' => 'Replace these views with your host application dashboard UI.',
        'stats' => [
            'agents' => 'Agents',
            'skills' => 'Skills',
            'tools' => 'Tools',
            'knowledge_sources' => 'Knowledge sources',
            'executions' => 'Executions',
            'conversations' => 'Conversations',
        ],
    ],

    'agents' => [
        'title' => 'Agents',
        'create' => 'Create agent',
        'create_heading' => 'Create agent',
        'edit_heading' => 'Edit :name',
    ],

    'skills' => [
        'title' => 'Skills',
        'create' => 'Create skill',
        'create_heading' => 'Create skill',
        'edit_heading' => 'Edit :name',
    ],

    'tools' => [
        'title' => 'Tools',
        'create' => 'Create tool',
        'create_heading' => 'Create tool',
        'edit_heading' => 'Edit :name',
    ],

    'knowledge' => [
        'title' => 'Knowledge sources',
        'create' => 'Create source',
        'create_heading' => 'Create knowledge source',
        'edit_heading' => 'Edit :name',
    ],

    'executions' => [
        'title' => 'Executions',
        'show_title' => 'Execution',
    ],

    'conversations' => [
        'title' => 'Conversations',
        'show_title' => 'Conversation',
    ],

    'flash' => [
        'agent_created' => 'Agent created.',
        'agent_updated' => 'Agent updated.',
        'agent_deleted' => 'Agent deleted.',
        'skill_created' => 'Skill created.',
        'skill_updated' => 'Skill updated.',
        'skill_deleted' => 'Skill deleted.',
        'tool_created' => 'Tool created.',
        'tool_updated' => 'Tool updated.',
        'tool_deleted' => 'Tool deleted.',
        'knowledge_created' => 'Knowledge source created.',
        'knowledge_updated' => 'Knowledge source updated.',
        'knowledge_deleted' => 'Knowledge source deleted.',
        'knowledge_indexed' => 'Knowledge source indexed.',
        'locale_updated' => 'Language updated.',
    ],
];
