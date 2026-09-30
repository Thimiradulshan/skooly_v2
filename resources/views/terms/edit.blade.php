@extends('layouts.app')

@section('title', 'Edit '.$term->name)

@section('content')
    <x-page-header :title="'Edit '.$term->name" subtitle="Update this term's academic year or dates." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('terms.update', $term) }}" data-loading>@csrf @method('PUT')
        @include('terms.partials.form', ['term' => $term])
        <div class="btn-row"><button type="submit" class="btn">Save term</button><a class="btn btn-secondary" href="{{ route('terms.show', $term) }}">Cancel</a></div>
    </form></x-card>
@endsection
