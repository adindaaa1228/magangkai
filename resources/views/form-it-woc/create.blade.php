@extends('layouts.app')

@section('title', 'Buat Formulir IT Work Order Completion')

@section('content')
    @include('form-it-woc.form', ['isEdit' => false])
@endsection