@extends('layouts.app')

@section('title', 'Academic years')

@section('content')
    <x-page-header title="Academic years" subtitle="Set the school years that organize terms, fees, and enrollments." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('academic-years.create')">Create academic year</x-button-link></div>
    <x-list-search :action="route('academic-years.index')" label="Academic year name" :value="$search" :sort-options="['name' => 'Name', 'start_date' => 'Start date', 'end_date' => 'End date']" :sort="$sort" :direction="$direction" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Dates</th><th>Terms</th><th class="actions">Actions</th></tr></thead><tbody>
    @forelse ($academicYears as $academicYear)
        <tr><td>{{ $academicYear->name }}</td><td>{{ $academicYear->start_date->toDateString() }} to {{ $academicYear->end_date->toDateString() }}</td><td>{{ $academicYear->terms_count }}</td><td class="actions"><x-button-link :href="route('academic-years.show', $academicYear)" variant="quiet" size="small">View</x-button-link></td></tr>
    @empty
        <tr class="table-empty"><td colspan="4"><span class="empty-state-title">No academic years yet</span>Create a school year before adding terms or year-specific fee structures.<div class="empty-actions"><x-button-link :href="route('academic-years.create')" size="small">Create academic year</x-button-link></div></td></tr>
    @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$academicYears" />
    <p class="note">Archiving and deletion are deferred because the schema has no lifecycle status and academic years are referenced by historical records.</p>
@endsection
