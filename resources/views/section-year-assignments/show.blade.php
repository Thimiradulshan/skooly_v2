@extends('layouts.app')

@section('title', 'Class in charge '.$sectionYearAssignment->id)

@section('content')
    <div class="breadcrumb"><a href="{{ route('section-year-assignments.index') }}">Class in charge</a><span class="breadcrumb-sep">/</span><span>Assignment {{ $sectionYearAssignment->id }}</span></div>
    <x-page-header :title="'Class in charge '.$sectionYearAssignment->id" subtitle="Stored yearly section responsibility. Editing and deletion are intentionally unavailable." eyebrow="Staff" />
    <div class="page-actions"><x-button-link :href="route('section-year-assignments.index')" variant="quiet">Back to class in charge</x-button-link></div>
    <x-card title="Assignment"><dl class="kv"><div class="kv-row"><dt>Academic year</dt><dd>{{ $sectionYearAssignment->academicYear->name }}</dd></div><div class="kv-row"><dt>Section</dt><dd>{{ $sectionYearAssignment->section->grade->name }} {{ $sectionYearAssignment->section->name }}</dd></div><div class="kv-row"><dt>Class in charge</dt><dd>{{ $sectionYearAssignment->classInCharge->name }}</dd></div></dl></x-card>
@endsection
