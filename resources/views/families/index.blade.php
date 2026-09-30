@extends('layouts.app')

@section('title', 'Families')
@section('heading', 'Families')

@section('content')
    <p><a href="{{ route('families.create') }}">Create family</a></p>

    <table>
        <thead>
        <tr>
            <th>Family code</th>
            <th>Address</th>
            <th>Combined billing</th>
            <th>Guardians</th>
            <th>Students</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($families as $family)
            <tr>
                <td><a href="{{ route('families.show', $family) }}">{{ $family->family_code }}</a></td>
                <td>{{ $family->address }}</td>
                <td>{{ $family->combined_billing_enabled ? 'Yes' : 'No' }}</td>
                <td>{{ $family->guardians_count }}</td>
                <td>{{ $family->students_count }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No families yet.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
