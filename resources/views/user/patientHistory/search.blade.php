@extends('layouts.app')

@section('content')
   
        <search-patient-history-admin
            :user = "{{ json_encode(Auth::user()) }}"
            :products = "{{ \App\Product::orderBy('name')->get() }}"
            :doctors = "{{ \App\User::query()->hasRole(['admin', 'doctor'], 'or')->orderBy('name')->get() }}"
            :assistants = "{{ \App\User::query()->hasRole('assistant')->orderBy('name')->get() }}"
        ></search-patient-history-admin>
@endsection