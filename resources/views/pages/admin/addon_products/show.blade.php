@extends('layouts.main')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Product Addon Details</h1>
            <div class="btn-group">
                <a href="{{ route('admin.product_addons.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ route('admin.product_addons.edit', $productAddon->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ $productAddon->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Slug:</strong>
                                <p class="text-muted">{{ $productAddon->slug }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Category:</strong>
                                <p>
                                    @if($productAddon->category)
                                        <span class="badge bg-info">{{ $productAddon->category->name }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Category</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Price:</strong>
                                <p class="text-success fs-5">₦{{ number_format($productAddon->price, 2) }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <p>
                                    <span class="badge {{ $productAddon->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($productAddon->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        @if($productAddon->description)
                            <div class="row">
                                <div class="col-12">
                                    <strong>Description:</strong>
                                    <p>{{ $productAddon->description }}</p>
                                </div>
                            </div>
                        @endif

                        @if($productAddon->image)
                            <div class="row">
                                <div class="col-12">
                                    <strong>Image:</strong>
                                    <p class="text-muted">{{ $productAddon->image }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>SKU:</strong>
                            <p class="text-muted">{{ $productAddon->sku ?? 'Not set' }}</p>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Sort Order:</strong>
                                <span class="badge bg-info">{{ $productAddon->sort_order ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Stock Status:</strong>
                            <p>
                                @if($productAddon->isInStock())
                                    <span class="badge bg-success">In Stock</span>
                                @else
                                    <span class="badge bg-danger">Out of Stock</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Timestamps</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Created:</strong>
                            <p class="text-muted small">{{ $productAddon->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="mb-0">
                            <strong>Last Updated:</strong>
                            <p class="text-muted small">{{ $productAddon->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.product_addons.edit', $productAddon->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Addon
                    </a>
                    <form action="{{ route('admin.product_addons.destroy', $productAddon->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this addon? This action cannot be undone.')">
                            <i class="fas fa-trash"></i> Delete Addon
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
