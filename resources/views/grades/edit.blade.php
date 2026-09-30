@extends('layouts.app')

@section('title', 'Edit '.$grade->name)

@section('content')
    <x-page-header :title="'Edit '.$grade->name" subtitle="Update this grade's name or promotion sequence." eyebrow="Academic setup" />
    <x-card><form method="POST" action="{{ route('grades.update', $grade) }}" data-loading>@csrf @method('PUT')
        @include('grades.partials.form', ['grade' => $grade])
        <div class="btn-row"><button type="submit" class="btn">Save grade</button><a class="btn btn-secondary" href="{{ route('grades.show', $grade) }}">Cancel</a></div>
    </form></x-card>
@endsection
