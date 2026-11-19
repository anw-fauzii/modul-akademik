@extends('layouts.appui')

@section('title')
    <title>Yaumiyah Tahfizh T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('admin.t2q.partials._yaumiyah_workspace', ['mode' => 'tahfizh'])
@endsection

