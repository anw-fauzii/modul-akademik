@extends('layouts.appui')

@section('title')
    <title>Yaumiyah Tahsin Guru T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('admin.t2q.partials._yaumiyah_workspace', ['mode' => 'tahsin'])
@endsection

