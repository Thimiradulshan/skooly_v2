@extends('layouts.app')

@section('title', 'Dues dashboard')
@section('heading', 'Dues dashboard')

@section('content')
    <form method="GET" action="{{ route('dues-dashboard.index') }}">
        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id">
            <option value="">-- all --</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}"
                    {{ (int) request('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                    {{ $academicYear->name }}
                </option>
            @endforeach
        </select>

        <label for="grade_id">Grade</label>
        <select id="grade_id" name="grade_id">
            <option value="">-- all --</option>
            @foreach ($grades as $grade)
                <option value="{{ $grade->id }}" {{ (int) request('grade_id') === $grade->id ? 'selected' : '' }}>
                    {{ $grade->name }}
                </option>
            @endforeach
        </select>

        <label for="section_id">Section</label>
        <select id="section_id" name="section_id">
            <option value="">-- all --</option>
            @foreach ($sections as $section)
                <option value="{{ $section->id }}" {{ (int) request('section_id') === $section->id ? 'selected' : '' }}>
                    {{ $section->grade?->name }} - {{ $section->name }}
                </option>
            @endforeach
        </select>

        <label for="fee_category_id">Fee category</label>
        <select id="fee_category_id" name="fee_category_id">
            <option value="">-- all --</option>
            @foreach ($feeCategories as $feeCategory)
                <option value="{{ $feeCategory->id }}"
                    {{ (int) request('fee_category_id') === $feeCategory->id ? 'selected' : '' }}>
                    {{ $feeCategory->name }}
                </option>
            @endforeach
        </select>

        <label for="family_id">Family</label>
        <select id="family_id" name="family_id">
            <option value="">-- all --</option>
            @foreach ($families as $family)
                <option value="{{ $family->id }}" {{ (int) request('family_id') === $family->id ? 'selected' : '' }}>
                    {{ $family->family_code }}
                </option>
            @endforeach
        </select>

        <label for="due_date_from">Due from</label>
        <input type="date" id="due_date_from" name="due_date_from" value="{{ request('due_date_from') }}">

        <label for="due_date_to">Due to</label>
        <input type="date" id="due_date_to" name="due_date_to" value="{{ request('due_date_to') }}">

        <p><button type="submit">Apply filters</button></p>
    </form>

    <h2>Summary</h2>
    <table>
        <tbody>
        <tr><th>Total due</th><td>{{ $report['summary']['total_net_amount'] }}</td></tr>
        <tr><th>Total collected</th><td>{{ $report['summary']['total_paid_amount'] }}</td></tr>
        <tr><th>Outstanding balance</th><td>{{ $report['summary']['total_balance_amount'] }}</td></tr>
        <tr><th>Due items</th><td>{{ $report['summary']['due_item_count'] }}</td></tr>
        <tr><th>Unpaid</th><td>{{ $report['summary']['unpaid_count'] }}</td></tr>
        <tr><th>Partially paid</th><td>{{ $report['summary']['partially_paid_count'] }}</td></tr>
        <tr><th>Paid</th><td>{{ $report['summary']['paid_count'] }}</td></tr>
        </tbody>
    </table>

    <h2>By fee category</h2>
    <table>
        <thead>
        <tr><th>Category</th><th>Net</th><th>Paid</th><th>Balance</th><th>Items</th></tr>
        </thead>
        <tbody>
        @forelse ($report['by_fee_category'] as $row)
            <tr>
                <td>{{ $row['fee_category_name'] }}</td>
                <td>{{ $row['total_net_amount'] }}</td>
                <td>{{ $row['total_paid_amount'] }}</td>
                <td>{{ $row['total_balance_amount'] }}</td>
                <td>{{ $row['due_item_count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Family balances</h2>
    <table>
        <thead>
        <tr><th>Family</th><th>Net</th><th>Paid</th><th>Balance</th><th>Items</th></tr>
        </thead>
        <tbody>
        @forelse ($report['family_balances'] as $row)
            <tr>
                <td>{{ $row['family_code'] }}</td>
                <td>{{ $row['total_net_amount'] }}</td>
                <td>{{ $row['total_paid_amount'] }}</td>
                <td>{{ $row['total_balance_amount'] }}</td>
                <td>{{ $row['due_item_count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Student balances</h2>
    <table>
        <thead>
        <tr><th>Student</th><th>Admission no</th><th>Family</th><th>Balance</th><th>Items</th></tr>
        </thead>
        <tbody>
        @forelse ($report['student_balances'] as $row)
            <tr>
                <td>{{ $row['student_name'] }}</td>
                <td>{{ $row['admission_no'] }}</td>
                <td>{{ $row['family_code'] }}</td>
                <td>{{ $row['total_balance_amount'] }}</td>
                <td>{{ $row['due_item_count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Outstanding due items</h2>
    <table>
        <thead>
        <tr>
            <th>Student</th><th>Category</th><th>Description</th>
            <th>Due date</th><th>Net</th><th>Paid</th><th>Balance</th><th>Status</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($report['outstanding_due_items'] as $row)
            <tr>
                <td>{{ $row['student_name'] }}</td>
                <td>{{ $row['fee_category_name'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['due_date'] }}</td>
                <td>{{ $row['net_amount'] }}</td>
                <td>{{ $row['paid_amount'] }}</td>
                <td>{{ $row['balance_amount'] }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No outstanding due items.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
