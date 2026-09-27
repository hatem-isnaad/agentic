<section class="card" style="margin-bottom: 1.5rem; border-color: #bfdbfe; background: linear-gradient(135deg, #fff 0%, #eff6ff 100%);">
    <div class="card-body">
        <h2 style="margin:0 0 0.5rem;font-size:1.25rem;">{{ __('agentic::admin.guide.title') }}</h2>
        <p style="margin:0 0 1.25rem;color:#64748b;font-size:0.9rem;">{{ __('agentic::admin.guide.subtitle') }}</p>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;margin-bottom:1.25rem;">
            <h3 style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;">{{ __('agentic::admin.guide.how_title') }}</h3>
            <ul style="margin:0.75rem 0;padding-inline-start:1.25rem;font-size:0.9rem;line-height:1.6;">
                <li>{{ __('agentic::admin.guide.how_1') }}</li>
                <li>{{ __('agentic::admin.guide.how_2') }}</li>
                <li>{{ __('agentic::admin.guide.how_3') }}</li>
            </ul>
            <p style="margin:0;padding:0.75rem;background:#0f172a;color:#e2e8f0;border-radius:8px;font-size:0.8rem;text-align:center;font-weight:600;">{{ __('agentic::admin.guide.flow') }}</p>
        </div>

        <h3 style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;">{{ __('agentic::admin.guide.steps_title') }}</h3>
        <ol style="list-style:none;margin:0.75rem 0 0;padding:0;display:grid;gap:0.75rem;">
            @for ($i = 1; $i <= 6; $i++)
                <li style="display:flex;gap:1rem;padding:1rem;border:1px solid #e2e8f0;border-radius:12px;background:#fff;">
                    <span style="flex-shrink:0;width:2rem;height:2rem;border-radius:999px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;">{{ $i }}</span>
                    <div>
                        <strong>{{ __("agentic::admin.guide.step{$i}_title") }}</strong>
                        <p style="margin:0.35rem 0 0;font-size:0.9rem;color:#64748b;">{{ __("agentic::admin.guide.step{$i}_body") }}</p>
                    </div>
                </li>
            @endfor
        </ol>
    </div>
</section>
