@extends('layouts.appui')

@section('title')
    <title>Matriks Tahfizh Guru T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('admin.t2q.partials._yaumiyah_matriks', ['mode' => 'tahfizh'])
@endsection

