@extends('layouts.main')

@section('content')
    <div class="container">
        <h1>Addon Category Details</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Name: {{ $addonCategory->name }}</h5>
                <p class="card-text">Slug: {{ $addonCategory->slug }}</p>
                <p class="card-text">Description: {{ $addonCategory->description }}</p>
                <p class="card-text">Image: {{ $addonCategory->image }}</p>
                <p class="card-text">Sort Order: {{ $addonCategory->sort_order }}</p>
                <p class="card-text">Status: {{ $addonCategory->status }}</p>
            </div>
        </div>
        <a href="{{ route('admin.addon_categories.index') }}" class="btn btn-primary">Back to List</a>
    </div>
@endsection