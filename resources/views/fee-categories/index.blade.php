@extends('layouts.app')

@section('title', 'Fee categories')

@section('content')
    <x-page-header title="Fee categories"
                   subtitle="What can be charged, and how each behaves." />

    <div class="page-actions">
        <x-button-link :href="route('fee-categories.create')">Create fee category</x-button-link>
        <x-button-link :href="route('fee-structures.index')" variant="secondary">Fee structures</x-button-link>
        <x-button-link :href="route('fee-structures.create')" variant="secondary">Create fee structure</x-button-link>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Name</th><th>Recurring</th><th>Opt-in</th><th class="actions">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($feeCategories as $feeCategory)
                <tr>
                    <td>{{ $feeCategory->name }}</td>
                    <td>{{ $feeCategory->is_recurring ? 'Yes' : 'No' }}</td>
                    <td>{{ $feeCategory->is_opt_in ? 'Yes' : 'No' }}</td>
                    <td class="actions">
                        <x-button-link :href="route('fee-categories.edit', $feeCategory)" variant="quiet" size="small">Edit</x-button-link>
                    </td>
                </tr>
            @empty
                <tr class="table-empty">
                    <td colspan="4">
                        <span class="empty-state-title">No fee categories yet</span>
                        Define what can be charged before adding fee structures.
                        <div class="empty-actions">
                            <x-button-link :href="route('fee-categories.create')" size="small">Create fee category</x-button-link>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="note">Opt-in categories, such as transport, are only charged to students who have an active subscription.</p>
@endsection
