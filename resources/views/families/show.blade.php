@extends('layouts.app')

@section('title', $family->family_code)
@section('heading', 'Family '.$family->family_code)

@section('content')
    <p><a href="{{ route('families.index') }}">Back to families</a></p>

    <p><a href="{{ route('families.edit', $family) }}">Edit family</a></p>
    <p><a href="{{ route('families.students.create', $family) }}">Register student</a></p>

    <table>
        <tbody>
        <tr><th>Family code</th><td>{{ $family->family_code }}</td></tr>
        <tr><th>Address</th><td>{{ $family->address }}</td></tr>
        <tr><th>Home contact</th><td>{{ $family->home_contact_no }}</td></tr>
        <tr>
            <th>Combined billing</th>
            <td>{{ $family->combined_billing_enabled ? 'Yes' : 'No' }}</td>
        </tr>
        </tbody>
    </table>

    <h2>Guardians</h2>
    <table>
        <thead>
        <tr><th>Name</th><th>Relationship</th><th>Contact</th><th>Email</th></tr>
        </thead>
        <tbody>
        @forelse ($family->guardians as $guardian)
            <tr>
                <td>{{ $guardian->name }}</td>
                <td>{{ $guardian->relationship }}</td>
                <td>{{ $guardian->contact_no }}</td>
                <td>{{ $guardian->email }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No guardians yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Students</h2>
        <table>
        <thead>
        <tr><th>Name</th><th>Admission number</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($family->students as $student)
            <tr>
                <td>{{ $student->name }}</td>
                <td>{{ $student->admission_no }}</td>
                <td>{{ $student->status }}</td>
                <td>
                    <a href="{{ route('students.discounts.create', $student) }}">Discount</a>
                    <a href="{{ route('students.fee-subscriptions.create', $student) }}">Fee subscription</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No students yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
