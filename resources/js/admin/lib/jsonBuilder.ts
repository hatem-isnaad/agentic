export type JsonBuilderKind = 'http_tool' | 'code_tool' | 'agent' | 'skill' | 'widget_message';

export type SchemaField = { name: string; type: string; required: boolean; description?: string };

export function buildInputSchema(fields: SchemaField[]): Record<string, unknown> {
    const properties: Record<string, unknown> = {};
    const required: string[] = [];

    for (const field of fields) {
        if (!field.name.trim()) continue;
        properties[field.name.trim()] = {
            type: field.type || 'string',
            ...(field.description ? { description: field.description } : {}),
        };
        if (field.required) {
            required.push(field.name.trim());
        }
    }

    return {
        type: 'object',
        properties,
        ...(required.length > 0 ? { required } : {}),
    };
}

export function buildHttpToolPayload(input: {
    name: string;
    slug: string;
    method: string;
    url: string;
    fields: SchemaField[];
}): Record<string, unknown> {
    return {
        name: input.name,
        slug: input.slug,
        driver: 'http',
        status: 'published',
        publish: true,
        definition: {
            method: input.method,
            url: input.url,
            input_schema: buildInputSchema(input.fields),
        },
    };
}

export function buildCodeToolPayload(input: {
    name: string;
    slug: string;
    handler: string;
    fields: SchemaField[];
}): Record<string, unknown> {
    return {
        name: input.name,
        slug: input.slug,
        driver: 'code',
        status: 'published',
        publish: true,
        definition: {
            handler: input.handler,
            input_schema: buildInputSchema(input.fields),
        },
    };
}

export function buildAgentPayload(input: {
    name: string;
    slug: string;
    instructions: string;
    provider: string;
    model: string;
    skills: string[];
    tools: string[];
    knowledge: string[];
}): Record<string, unknown> {
    return {
        name: input.name,
        slug: input.slug,
        status: 'published',
        instructions: input.instructions,
        provider: input.provider,
        model: input.model,
        skills: input.skills,
        tools: input.tools,
        knowledge: input.knowledge,
        permissions: input.tools,
    };
}

export function buildSkillPayload(input: {
    name: string;
    slug: string;
    instructions: string;
    tools: string[];
    knowledge: string[];
}): Record<string, unknown> {
    return {
        name: input.name,
        slug: input.slug,
        status: 'published',
        instructions: input.instructions,
        tools: input.tools,
        knowledge: input.knowledge,
    };
}

export function parseSlugList(raw: string): string[] {
    return raw
        .split(/[\n,]/)
        .map((s) => s.trim())
        .filter(Boolean);
}
