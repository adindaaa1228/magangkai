@extends('layouts.app')

@section('title', 'Edit Formulir IT Work Order Completion')

@section('content')
    @include('form-it-woc.form', ['isEdit' => true])
@endsection