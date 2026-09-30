@extends('layouts.app')

@section('title', 'Dues dashboard')

@section('content')
    <x-page-header title="Dues dashboard"
                   subtitle="Total due, collected, and outstanding balances from stored snapshots." />

    <div class="page-actions">
        <x-button-link :href="route('due-generation.recurring.create')" variant="secondary">Recurring due generation</x-button-link>
        <x-button-link :href="route('payment-reminders.index')" variant="secondary">Payment reminders</x-button-link>
        <x-button-link :href="route('admin.dashboard')" variant="secondary">Dashboard</x-button-link>
    </div>

    <x-card title="Filters">
        <form method="GET" action="{{ route('dues-dashboard.index') }}">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="academic_year_id">Academic year</label>
                    <select class="form-control" id="academic_year_id" name="academic_year_id">
                        <option value="">-- all --</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ (int) request('academic_year_id') === $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-help">Required when filtering by grade or section.</span>
                </div>

                <div class="form-field">
                    <label class="form-label" for="grade_id">Grade</label>
                    <select class="form-control" id="grade_id" name="grade_id">
                        <option value="">-- all --</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" {{ (int) request('grade_id') === $grade->id ? 'selected' : '' }}>
                                {{ $grade->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="section_id">Section</label>
                    <select class="form-control" id="section_id" name="section_id">
                        <option value="">-- all --</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" {{ (int) request('section_id') === $section->id ? 'selected' : '' }}>
                                {{ $section->grade?->name }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="fee_category_id">Fee category</label>
                    <select class="form-control" id="fee_category_id" name="fee_category_id">
                        <option value="">-- all --</option>
                        @foreach ($feeCategories as $feeCategory)
                            <option value="{{ $feeCategory->id }}" {{ (int) request('fee_category_id') === $feeCategory->id ? 'selected' : '' }}>
                                {{ $feeCategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="family_id">Family</label>
                    <select class="form-control" id="family_id" name="family_id">
                        <option value="">-- all --</option>
                        @foreach ($families as $family)
                            <option value="{{ $family->id }}" {{ (int) request('family_id') === $family->id ? 'selected' : '' }}>
                                {{ $family->family_code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="due_date_from">Due from</label>
                    <input class="form-control" type="date" id="due_date_from" name="due_date_from" value="{{ request('due_date_from') }}">
                </div>

                <div class="form-field">
                    <label class="form-label" for="due_date_to">Due to</label>
                    <input class="form-control" type="date" id="due_date_to" name="due_date_to" value="{{ request('due_date_to') }}">
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn">Apply filters</button>
            </div>
        </form>
    </x-card>

    <h2 class="section-heading">Summary</h2>
    <div class="card-grid">
        <div class="metric"><span class="metric-label">Total due</span><span class="metric-value">{{ $report['summary']['total_net_amount'] }}</span></div>
        <div class="metric"><span class="metric-label">Total collected</span><span class="metric-value">{{ $report['summary']['total_paid_amount'] }}</span></div>
        <div class="metric"><span class="metric-label">Outstanding balance</span><span class="metric-value">{{ $report['summary']['total_balance_amount'] }}</span></div>
        <div class="metric"><span class="metric-label">Due items</span><span class="metric-value">{{ $report['summary']['due_item_count'] }}</span></div>
        <div class="metric">
            <span class="metric-label">Status</span>
            <span class="metric-status">
                <x-status-badge value="unpaid" /> {{ $report['summary']['unpaid_count'] }}
                &nbsp;<x-status-badge value="partially_paid" /> {{ $report['summary']['partially_paid_count'] }}
                &nbsp;<x-status-badge value="paid" /> {{ $report['summary']['paid_count'] }}
            </span>
        </div>
    </div>

    <h2 class="section-heading">By fee category</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Category</th><th class="num">Net</th><th class="num">Paid</th><th class="num">Balance</th><th class="num">Items</th></tr></thead>
            <tbody>
            @forelse ($report['by_fee_category'] as $row)
                <tr>
                    <td>{{ $row['fee_category_name'] }}</td>
                    <td class="num">{{ $row['total_net_amount'] }}</td>
                    <td class="num">{{ $row['total_paid_amount'] }}</td>
                    <td class="num">{{ $row['total_balance_amount'] }}</td>
                    <td class="num">{{ $row['due_item_count'] }}</td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="5">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="section-heading">Family balances</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Family</th><th class="num">Net</th><th class="num">Paid</th><th class="num">Balance</th><th class="num">Items</th></tr></thead>
            <tbody>
            @forelse ($report['family_balances'] as $row)
                <tr>
                    <td>{{ $row['family_code'] }}</td>
                    <td class="num">{{ $row['total_net_amount'] }}</td>
                    <td class="num">{{ $row['total_paid_amount'] }}</td>
                    <td class="num">{{ $row['total_balance_amount'] }}</td>
                    <td class="num">{{ $row['due_item_count'] }}</td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="5">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="section-heading">Student balances</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Student</th><th>Admission no.</th><th>Family</th><th class="num">Balance</th><th class="num">Items</th></tr></thead>
            <tbody>
            @forelse ($report['student_balances'] as $row)
                <tr>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['admission_no'] }}</td>
                    <td>{{ $row['family_code'] }}</td>
                    <td class="num">{{ $row['total_balance_amount'] }}</td>
                    <td class="num">{{ $row['due_item_count'] }}</td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="5">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="section-heading">Outstanding due items</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Student</th><th>Category</th><th>Description</th>
                <th>Due date</th><th class="num">Net</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($report['outstanding_due_items'] as $row)
                <tr>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['fee_category_name'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td class="num">{{ $row['net_amount'] }}</td>
                    <td class="num">{{ $row['paid_amount'] }}</td>
                    <td class="num">{{ $row['balance_amount'] }}</td>
                    <td><x-status-badge :value="$row['status']" /></td>
                </tr>
            @empty
                <tr class="table-empty"><td colspan="8">No outstanding due items.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
