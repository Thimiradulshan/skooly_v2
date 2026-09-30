@extends('layouts.app')

@section('title', $term->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('terms.index') }}">Terms</a><span class="breadcrumb-sep">/</span><span>{{ $term->name }}</span></div>
    <x-page-header :title="$term->name" subtitle="Term details." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('terms.edit', $term)">Edit term</x-button-link><x-button-link :href="route('terms.index')" variant="quiet">Back to terms</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Academic year</dt><dd><a href="{{ route('academic-years.show', $term->academicYear) }}">{{ $term->academicYear->name }}</a></dd></div><div class="kv-row"><dt>Start date</dt><dd>{{ $term->start_date->toDateString() }}</dd></div><div class="kv-row"><dt>End date</dt><dd>{{ $term->end_date->toDateString() }}</dd></div></dl></x-card>
@endsection
