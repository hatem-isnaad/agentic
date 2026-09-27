<dl class="detail-grid">
    @foreach ($rows as $row)
        <div class="detail-row">
            <dt>{{ $row[0] }}</dt>
            <dd>{{ $row[1] }}</dd>
        </div>
    @endforeach
</dl>
