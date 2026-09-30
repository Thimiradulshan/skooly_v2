@extends('layouts.app')

@section('title', $academicYear->name)

@section('content')
    <div class="breadcrumb"><a href="{{ route('academic-years.index') }}">Academic years</a><span class="breadcrumb-sep">/</span><span>{{ $academicYear->name }}</span></div>
    <x-page-header :title="$academicYear->name" subtitle="Academic year details and its configured terms." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('academic-years.edit', $academicYear)">Edit academic year</x-button-link><x-button-link :href="route('terms.create')" variant="secondary">Add term</x-button-link><x-button-link :href="route('academic-years.index')" variant="quiet">Back to academic years</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Start date</dt><dd>{{ $academicYear->start_date->toDateString() }}</dd></div><div class="kv-row"><dt>End date</dt><dd>{{ $academicYear->end_date->toDateString() }}</dd></div></dl></x-card>
    <x-card title="Terms"><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Dates</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($academicYear->terms as $term)<tr><td>{{ $term->name }}</td><td>{{ $term->start_date->toDateString() }} to {{ $term->end_date->toDateString() }}</td><td class="actions"><x-button-link :href="route('terms.show', $term)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="3"><span class="empty-state-title">No terms yet</span>Add the first term for this academic year.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
