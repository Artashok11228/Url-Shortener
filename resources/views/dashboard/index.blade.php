@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="card">
        <h1 style="margin-top: 0;">Your links</h1>
        <p class="muted">Click counts and tags for links you can manage.</p>

        @if ($links->isEmpty())
            <p>No links yet. Create some via the API, then refresh this page.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Destination</th>
                        <th>Clicks</th>
                        <th>Tags</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($links as $link)
                        <tr>
                            <td><code>{{ $link->code }}</code></td>
                            <td style="max-width: 280px; word-break: break-all;">{{ $link->original_url }}</td>
                            <td>{{ $link->clicks_count }}</td>
                            <td>
                                @forelse ($link->tags as $tag)
                                    <span class="tag">{{ $tag->name }}</span>
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                <a href="{{ route('dashboard.links.tags.edit', $link) }}">Edit tags</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination">
                {{ $links->links() }}
            </div>
        @endif
    </div>
@endsection
