@extends('layouts.app')
@section('title', $guardian->name)
@section('content')
    <div class="breadcrumb"><a href="{{ route('families.show', $guardian->family) }}">{{ $guardian->family->family_code }}</a><span class="breadcrumb-sep">/</span><span>{{ $guardian->name }}</span></div>
    <x-page-header :title="$guardian->name" subtitle="Guardian details and explicitly visible students." eyebrow="Registration" />
    <div class="page-actions"><x-button-link :href="route('guardians.edit', $guardian)">Edit guardian</x-button-link><x-button-link :href="route('families.show', $guardian->family)" variant="quiet">Back to family</x-button-link></div>
    <x-card title="Details"><dl class="kv"><div class="kv-row"><dt>Family</dt><dd>{{ $guardian->family->family_code }}</dd></div><div class="kv-row"><dt>Relationship</dt><dd>{{ $guardian->relationship }}</dd></div><div class="kv-row"><dt>Contact</dt><dd>{{ $guardian->contact_no }}</dd></div><div class="kv-row"><dt>Email</dt><dd>{{ $guardian->email }}</dd></div><div class="kv-row"><dt>NIC</dt><dd>{{ $guardian->nic }}</dd></div></dl></x-card>
    <x-card title="Explicitly linked students"><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Admission number</th></tr></thead><tbody>@forelse ($guardian->students as $student)<tr><td><a href="{{ route('students.show', $student) }}">{{ $student->name }}</a></td><td>{{ $student->admission_no }}</td></tr>@empty<tr class="table-empty"><td colspan="2">No students are linked. This guardian has no student visibility.</td></tr>@endforelse</tbody></table></div></x-card>
@endsection
