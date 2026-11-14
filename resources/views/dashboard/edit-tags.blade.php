@extends('layouts.app')

@section('title', 'Edit tags')

@section('content')
    <div class="card" style="max-width: 560px;">
        <h1 style="margin-top: 0;">Edit tags</h1>
        <p class="muted">
            Link <code>{{ $link->code }}</code>
            → {{ $link->original_url }}
        </p>

        <form method="POST" action="{{ route('dashboard.links.tags.update', $link) }}">
            @csrf
            @method('PUT')

            <label for="tags">Tags (comma-separated)</label>
            <input
                id="tags"
                class="field"
                type="text"
                name="tags"
                value="{{ old('tags', $link->tags->pluck('name')->implode(', ')) }}"
                placeholder="work, promo, docs"
            >

            @error('tags')
                <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn">Save tags</button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="margin-left: .5rem;">Cancel</a>
        </form>
    </div>
@endsection
