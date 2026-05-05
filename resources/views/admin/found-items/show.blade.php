@extends('layouts.admin')

@section('title', $item->name)

@section('content')
    @include('admin.partials.item-detail', ['item' => $item, 'type' => 'found'])
@endsection
