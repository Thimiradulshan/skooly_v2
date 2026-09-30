@extends('layouts.app')

@section('title', 'Families')

@section('content')
    <x-page-header title="Families"
                   subtitle="Every household, its guardians, and its students." />

    <div class="page-actions">
        <x-button-link :href="route('families.create')">Create family</x-button-link>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Family code</th>
                <th>Address</th>
                <th>Combined billing</th>
                <th class="num">Guardians</th>
                <th class="num">Students</th>
                <th class="actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($families as $family)
                <tr>
                    <td><a href="{{ route('families.show', $family) }}">{{ $family->family_code }}</a></td>
                    <td>{{ $family->address }}</td>
                    <td>{{ $family->combined_billing_enabled ? 'Yes' : 'No' }}</td>
                    <td class="num">{{ $family->guardians_count }}</td>
                    <td class="num">{{ $family->students_count }}</td>
                    <td class="actions">
                        <x-button-link :href="route('families.show', $family)" variant="quiet" size="small">View</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="6">No families yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
