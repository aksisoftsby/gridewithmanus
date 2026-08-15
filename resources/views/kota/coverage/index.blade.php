@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Coverage Kota</h1>
            <p class="text-sm text-gray-500">Kota-kota yang menjadi tanggung jawab pengelolaan manager</p>
        </div>
        @if ($isAdmin)
            <span class="px-3 py-1.5 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">
                <i class="fa-solid fa-shield-halved mr-1"></i> Mode ADMIN — dapat menambah/menghapus coverage
            </span>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-4 bg-green-50 border border-green-100 text-green-800 text-sm rounded-lg px-4 py-3">
            <i class="fa-solid fa-circle-check mr-1"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Form tambah coverage — ADMIN super only -->
    @if ($isAdmin)
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-8">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fa-solid fa-plus mr-1"></i> Tambah Coverage Kota</h3>
            <form method="POST" action="{{ route('kota.coverage.add') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Manager</label>
                    <select name="user_id" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">-- Pilih manager --</option>
                        @foreach ($managers ?? [] as $item)
                            <option value="{{ $managers->id }}">{{ $managers->full_name }} ({{ $managers->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Provinsi</label>
                    <select id="coverageProvinsi" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">-- Pilih provinsi --</option>
                        @foreach ($provinsis ?? [] as $item)
                            <option value="{{ $provinsis->id }}">{{ $provinsis->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kota/Kabupaten</label>
                    <select name="id_kota" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500" id="coverageKota">
                        <option value="">-- Pilih provinsi dulu --</option>
                    </select>
                </div>
                <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah
                </button>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        @if ($isAdmin) <th class="px-5 py-3">Manager</th> @endif
                        <th class="px-5 py-3">Provinsi</th>
                        <th class="px-5 py-3">Kota/Kabupaten</th>
                        <th class="px-5 py-3">Ditambahkan</th>
                        @if ($isAdmin) <th class="px-5 py-3">Aksi</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($coverage ?? [] as $coverage)
                        <tr class="hover:bg-gray-50">
                            @if ($isAdmin)
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-800">{{ $coverage->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $coverage->email }}</div>
                                </td>
                            @endif
                            <td class="px-5 py-3 text-gray-700">{{ $coverage->provinsi_nama }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $coverage->kota_nama }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $coverage->created_at ? \Carbon\Carbon::parse($coverage->created_at)->format('d M Y') : '-' }}</td>
                            @if ($isAdmin)
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('kota.coverage.remove', $coverage->coverage_id) }}" onsubmit="return confirm('Hapus coverage kota ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 4 : 3 }}" class="px-5 py-8 text-center text-gray-500">
                                Belum ada coverage kota.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($isAdmin)
@push('scripts')
<script>
(function () {
    var provinsiSelect = document.getElementById('coverageProvinsi');
    var kotaSelect = document.getElementById('coverageKota');
    var allKota = @json($allKota ?? []);
    provinsiSelect.addEventListener('change', function () {
        var provId = this.value;
        kotaSelect.innerHTML = '<option value="">-- Pilih kota/kabupaten --</option>';
        if (provId) {
            allKota.filter(function (k) { return String(k.provinsi_id) === String(provId); })
                .sort(function (a, b) { return a.nama.localeCompare(b.nama); })
                .forEach(function (k) {
                    var opt = document.createElement('option');
                    opt.value = k.id;
                    opt.textContent = k.nama;
                    kotaSelect.appendChild(opt);
                });
        }
    });
})();
</script>
@endpush
@endif
@endsection
