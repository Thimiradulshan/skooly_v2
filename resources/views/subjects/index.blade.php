@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
    <x-page-header title="Subjects" subtitle="Manage the subjects available for teacher qualifications and assignments." eyebrow="Academic setup" />
    <div class="page-actions"><x-button-link :href="route('subjects.create')">Create subject</x-button-link></div>
    <x-list-search :action="route('subjects.index')" label="Subject code or name" :value="$search" />
    <div class="table-wrap"><table class="table"><thead><tr><th>Code</th><th>Name</th><th class="num">Qualified teachers</th><th class="num">Assignments</th><th class="actions">Actions</th></tr></thead><tbody>
    @forelse ($subjects as $subject)
        <tr><td>{{ $subject->code }}</td><td>{{ $subject->name }}</td><td class="num">{{ $subject->qualified_teachers_count }}</td><td class="num">{{ $subject->teacher_assignments_count }}</td><td class="actions"><x-button-link :href="route('subjects.show', $subject)" variant="quiet" size="small">View</x-button-link></td></tr>
    @empty
        <tr class="table-empty"><td colspan="5"><span class="empty-state-title">No subjects yet</span>Create a subject before qualifying teachers or assigning lessons.</td></tr>
    @endforelse
    </tbody></table></div>
    <x-pagination :paginator="$subjects" />
@endsection
