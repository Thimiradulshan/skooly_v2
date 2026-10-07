@extends('layouts.app')

@section('title', 'Terms')

@section('content')
    <x-page-header title="Terms" subtitle="Define the teaching terms within each academic year." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('terms.create')">Create term</x-button-link></div>
    <x-list-search :action="route('terms.index')" label="Term or academic year name" :value="$search" :sort-options="['name' => 'Name', 'start_date' => 'Start date', 'end_date' => 'End date']" :sort="$sort" :direction="$direction" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Status</th><th>Academic year</th><th>Dates</th><th class="actions">Actions</th></tr></thead><tbody>@forelse ($terms as $term)<tr><td>{{ $term->name }}</td><td><x-status-badge :value="$term->is_archived ? 'archived' : 'active'" /></td><td>{{ $term->academicYear->name }}</td><td>{{ $term->start_date->toDateString() }} to {{ $term->end_date->toDateString() }}</td><td class="actions"><x-button-link :href="route('terms.show', $term)" variant="quiet" size="small">View</x-button-link></td></tr>@empty<tr class="table-empty"><td colspan="5"><span class="empty-state-title">No terms yet</span>Create a term after setting up an academic year.<div class="empty-actions"><x-button-link :href="route('terms.create')" size="small">Create term</x-button-link></div></td></tr>@endforelse</tbody></table></div>
    <x-pagination :paginator="$terms" />
    <p class="note">Archived terms remain visible here and in history, but cannot be selected for new configuration.</p>
@endsection
