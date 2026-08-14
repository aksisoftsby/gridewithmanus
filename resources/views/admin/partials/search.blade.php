{{-- Shared admin search bar. Usage: @include('admin.partials.search', ['route' => 'admin.users.index']) --}}
<div class="bg-white rounded-xl shadow border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
    <form action="{{ route($route) }}" method="GET" class="flex-grow flex gap-2">
        <div class="relative flex-grow">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ $search ?? request()->query('search') }}" placeholder="{{ $placeholder ?? 'Cari...' }}" class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-600 focus:border-transparent">
        </div>
        <button type="submit" class="bg-pink-700 hover:bg-pink-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Cari</button>
        @if(!empty($search ?? request()->query('search')))
            <a href="{{ route($route) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Reset</a>
        @endif
    </form>
    @if(isset($createRoute) && $createRoute)
        <a href="{{ route($createRoute) }}" class="bg-pink-700 hover:bg-pink-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">
            <i class="fa-solid fa-plus mr-1"></i> Tambah
        </a>
    @endif
</div>
