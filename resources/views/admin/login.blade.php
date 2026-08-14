@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-16 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-pink-100 text-pink-700 rounded-full mb-3">
            <i class="fa-solid fa-lock text-xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Admin Login</h2>
        <p class="text-sm text-gray-500">RideSip Superapp — Control & Management Dashboard</p>
    </div>

    <form action="{{ route('admin.login') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
            <input type="email" name="email" value="admin@superapp.com" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
            <input type="password" name="password" value="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-pink-700 hover:bg-pink-800 text-white font-semibold py-2.5 rounded-lg shadow transition">
            Login to Admin Panel
        </button>
    </form>

    <div class="mt-6 text-center bg-gray-50 p-3 rounded-lg text-xs text-gray-600">
        <p><strong>Default Credentials:</strong></p>
        <p>Email: admin@superapp.com | Password: password</p>
    </div>
</div>
@endsection
