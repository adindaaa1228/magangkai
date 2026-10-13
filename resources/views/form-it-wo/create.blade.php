@extends('layouts.app')

@section('title', 'Buat Formulir IT Work Order')

@section('content')
    @include('form-it-wo.form', ['isEdit' => false])
@endsection