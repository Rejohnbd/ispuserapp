@extends('layouts.app')

@section('title', 'Setting')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        Setting
    </h4>
    @if(!$status)
    <div class="row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-2 text-center text-danger">Permission Denied</h3>
                </div>
                <div class="card-body p-5 bg-danger">
                    <h3 class="m-2 text-center text-white">Contact to Author</h3>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <form action="{{ route('setting') }}" method="post">
                @csrf
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">User Name</label>
                        <input type="text" disabled class="form-control" value="{{ Auth::user()->username }}" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Client Name</label>
                        <input type="text" disabled class="form-control" value="{{ Auth::user()->name }}" readonly>
                        @error('name')
                        <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Old Password</label>
                        <input type="text" name="old_password" class="form-control" value="{{ old('old_password') }}">
                        @error('old_password')
                        <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" value="{{ old('new_password') }}">
                        @error('new_password')
                        <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="pt-4 d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary me-sm-3 me-1">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection