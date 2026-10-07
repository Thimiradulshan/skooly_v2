@extends('layouts.app')

@section('title', $family->family_code)

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('families.index') }}">Families</a>
        <span class="breadcrumb-sep">/</span>
        <span>{{ $family->family_code }}</span>
    </div>

    <x-page-header :title="$family->family_code" subtitle="Family details, guardians, and students." eyebrow="Registration" />

    <div class="page-actions">
        <x-button-link :href="route('families.edit', $family)">Edit family</x-button-link>
        <x-button-link :href="route('families.students.create', $family)" variant="secondary">Register student</x-button-link>
        <x-button-link :href="route('families.guardians.create', $family)" variant="secondary">Add guardian</x-button-link>
        <x-button-link :href="route('families.payments.create', $family)" variant="secondary">Record payment</x-button-link>
    </div>

    <x-card title="Details">
        <dl class="kv">
            <div class="kv-row"><dt>Family code</dt><dd>{{ $family->family_code }}</dd></div>
            <div class="kv-row"><dt>Address</dt><dd>{{ $family->address }}</dd></div>
            <div class="kv-row"><dt>Home contact</dt><dd>{{ $family->home_contact_no }}</dd></div>
            <div class="kv-row">
                <dt>Combined billing</dt>
                <dd>{{ $family->combined_billing_enabled ? 'Yes' : 'No' }}</dd>
            </div>
        </dl>
    </x-card>

    <x-card title="Guardians">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Name</th><th>Relationship</th><th>Contact</th><th>Email</th><th class="actions">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($family->guardians as $guardian)
                    <tr>
                        <td>{{ $guardian->name }}</td>
                        <td>{{ $guardian->relationship }}</td>
                        <td>{{ $guardian->contact_no }}</td>
                        <td>{{ $guardian->email }}</td>
                        <td class="actions"><x-button-link :href="route('guardians.show', $guardian)" variant="quiet" size="small">View</x-button-link></td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="5">No guardians yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="Students">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr><th>Name</th><th>Admission number</th><th>Status</th><th class="actions">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($family->students as $student)
                    <tr>
                        <td><a href="{{ route('students.show', $student) }}">{{ $student->name }}</a></td>
                        <td>{{ $student->admission_no }}</td>
                        <td><x-status-badge :value="$student->status" /></td>
                        <td class="actions">
                            <x-button-link :href="route('students.discounts.create', $student)" variant="quiet" size="small">Discount</x-button-link>
                            <x-button-link :href="route('students.fee-subscriptions.create', $student)" variant="quiet" size="small">Fee subscription</x-button-link>
                            <x-button-link :href="route('students.show', $student)" variant="quiet" size="small">View</x-button-link>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty"><td colspan="4">No students yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
