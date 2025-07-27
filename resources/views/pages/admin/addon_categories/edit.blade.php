@extends('layouts.main')

@section('content')
    <div class="container">
        <h1>Edit Addon Category</h1>
        <form action="{{ route('admin.addon_categories.update', $addonCategory->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $addonCategory->name }}" required>
            </div>
            <div class="form-group">
                <label for="slug">Slug:</label>
                <input type="text" name="slug" id="slug" class="form-control" value="{{ $addonCategory->slug }}" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" id="description"
                    class="form-control">{{ $addonCategory->description }}</textarea>
            </div>
            <div class="form-group">
                <label for="image">Image:</label>
                <input type="file" name="image" id="image" class="form-control">
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order:</label>
                <input type="number" name="sort_order" id="sort_order" class="form-control"
                    value="{{ $addonCategory->sort_order }}">
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="active" {{ $addonCategory->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $addonCategory->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
@endsection