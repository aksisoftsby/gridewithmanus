@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12" id="api-docs-root">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl font-bold">
                <i class="fa-solid fa-code"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900" id="doc-api-title">Gride Superapp REST API Documentation</h1>
                <p class="text-sm text-gray-500" id="doc-api-base">Base URL: https://gride.web.id/api</p>
            </div>
        </div>
        <p class="text-gray-700 leading-relaxed" id="doc-api-desc">
            API ini dirancang untuk integrasi Mobile App (Customer, Driver, Merchant) serta sistem pihak ketiga. Seluruh endpoint mengembalikan respons dalam format JSON standar. Detail setiap endpoint dimuat secara dinamis dari file dokumentasi JSON.
        </p>
        <div class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-600">
            <p class="font-semibold text-gray-700 mb-1">Format Respons & Error:</p>
            <pre class="font-mono" id="doc-error-format"></pre>
        </div>
    </div>

    <div class="space-y-6" id="doc-endpoints"></div>

    <div class="text-center text-sm text-gray-400 py-8" id="doc-loading">
        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat dokumentasi API...
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
    const index = await fetch('{{ asset("api-docs/index.json") }}').then(r => r.json());
    document.getElementById('doc-api-title').textContent = index.api_name + ' Documentation (v' + index.version + ')';
    document.getElementById('doc-api-base').textContent = 'Base URL: ' + index.base_url + ' — ' + index.description;
    document.getElementById('doc-error-format').textContent = JSON.stringify(index.error_format.example, null, 2) + '\nKode error: ' + Object.entries(index.error_format.codes).map(([k, v]) => k + '=' + v).join(', ');

    const container = document.getElementById('doc-endpoints');
    for (const ep of index.endpoints) {
        const doc = await fetch('{{ asset("") }}' + ep.doc_file).then(r => r.json());
        const groupBadge = ep.group === 'Merchants' ? 'bg-emerald-100 text-emerald-800'
            : ep.group === 'Products' ? 'bg-blue-100 text-blue-800'
            : ep.group === 'Orders' ? 'bg-orange-100 text-orange-800'
            : 'bg-purple-100 text-purple-800';

        let paramsHtml = '';
        if (doc.query_parameters && doc.query_parameters.length) {
            paramsHtml += '<h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Query Parameters:</h4><ul class="list-disc list-inside text-sm text-gray-600 mb-4">';
            doc.query_parameters.forEach(p => {
                paramsHtml += '<li><code>' + p.name + '</code> (' + (p.required ? 'required' : 'optional') + ', ' + p.type + '): ' + p.description + '</li>';
            });
            paramsHtml += '</ul>';
        }
        if (doc.path_parameters && doc.path_parameters.length) {
            paramsHtml += '<h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Path Parameters:</h4><ul class="list-disc list-inside text-sm text-gray-600 mb-4">';
            doc.path_parameters.forEach(p => {
                paramsHtml += '<li><code>' + p.name + '</code> (' + (p.required ? 'required' : 'optional') + ', ' + p.type + '): ' + p.description + '</li>';
            });
            paramsHtml += '</ul>';
        }
        if (doc.response_fields && doc.response_fields.length) {
            paramsHtml += '<h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Response Fields:</h4>';
            paramsHtml += '<table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden mb-4"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left font-semibold">Field</th><th class="px-3 py-2 text-left font-semibold">Type</th><th class="px-3 py-2 text-left font-semibold">Description</th></tr></thead><tbody class="divide-y divide-gray-100">';
            doc.response_fields.forEach(f => {
                paramsHtml += '<tr><td class="px-3 py-1.5 font-mono">' + f.field + '</td><td class="px-3 py-1.5 text-gray-500">' + f.type + '</td><td class="px-3 py-1.5">' + f.description + '</td></tr>';
            });
            paramsHtml += '</tbody></table>';
        }

        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl shadow border border-gray-100 overflow-hidden';
        card.innerHTML =
            '<div class="bg-emerald-600 text-white px-6 py-3 flex flex-wrap justify-between items-center gap-2">' +
            '<span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">' + doc.endpoint + '</span>' +
            '<span class="px-2.5 py-1 text-xs font-semibold rounded-full ' + groupBadge + '">' + ep.group + '</span>' +
            '</div>' +
            '<div class="p-6">' +
            '<p class="text-sm text-gray-600 mb-4">' + doc.description + '</p>' +
            '<p class="text-xs text-gray-500 mb-2"><i class="fa-solid fa-key mr-1"></i>Autentikasi: ' + doc.authentication + '</p>' +
            paramsHtml +
            '<h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Contoh Response (JSON):</h4>' +
            '<pre class="bg-gray-900 text-emerald-400 p-4 rounded-xl text-xs overflow-x-auto font-mono">' + JSON.stringify(doc.example_response, null, 2) + '</pre>' +
            '<h4 class="text-xs font-bold text-gray-700 uppercase mb-2 mt-4">cURL:</h4>' +
            '<pre class="bg-gray-800 text-gray-300 p-3 rounded-xl text-xs overflow-x-auto font-mono">' + doc.example_curl + '</pre>' +
            '</div>';
        container.appendChild(card);
    }
    document.getElementById('doc-loading').style.display = 'none';
});
</script>
@endsection
