@extends('layouts.app')

@section('title', 'Edit Formulir IT Work Order')

@section('content')
    @include('form-it-wo.form', ['isEdit' => true])
@endsection