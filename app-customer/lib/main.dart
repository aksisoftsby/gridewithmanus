import 'package:flutter/material.dart';
import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;

void main() {
  runApp(const CustomerApp());
}

class CustomerApp extends StatelessWidget {
  const CustomerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gride Customer',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: const MainShell(),
      debugShowCheckedModeBanner: false,
    );
  }
}

/// Bottom-navigation shell combining Home (Pesan) and Kirim (antar-jemput GPS).
class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _page = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _page,
        children: const [CustomerHome(), KirimPage()],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _page,
        onDestinationSelected: (i) => setState(() => _page = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.storefront), label: 'Pesan'),
          NavigationDestination(icon: Icon(Icons.location_on), label: 'Kirim'),
        ],
      ),
    );
  }
}

class CustomerHome extends StatefulWidget {
  const CustomerHome({super.key});

  @override
  State<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends State<CustomerHome> {
  List merchants = [];
  List promos = [];
  bool isLoading = true;
  String selectedType = '';

  final String baseUrl = 'https://gride.web.id/api';

  @override
  void initState() {
    super.initState();
    fetchData();
  }

  Future<void> fetchData() async {
    setState(() => isLoading = true);
    try {
      final merchantUrl = selectedType.isEmpty
          ? '$baseUrl/merchants'
          : '$baseUrl/merchants?type=$selectedType';

      final mRes = await http.get(Uri.parse(merchantUrl));
      final pRes = await http.get(Uri.parse('$baseUrl/promos'));

      if (mRes.statusCode == 200 && pRes.statusCode == 200) {
        setState(() {
          merchants = jsonDecode(mRes.body)['data'];
          promos = jsonDecode(pRes.body)['data'];
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gride Customer'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: fetchData,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TextField(
                    decoration: InputDecoration(
                      hintText: 'Cari makanan atau toko...',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildCategoryChip('Semua', ''),
                      _buildCategoryChip('FOOD', 'FOOD'),
                      _buildCategoryChip('MART', 'MART'),
                      _buildCategoryChip('SHOP', 'SHOP'),
                    ],
                  ),
                  const SizedBox(height: 20),
                  const Text('Promo Spesial',
                      style:
                          TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 120,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      itemCount: promos.length,
                      itemBuilder: (context, index) {
                        final promo = promos[index];
                        return Container(
                          width: 250,
                          margin: const EdgeInsets.only(right: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                                colors: [Colors.teal, Colors.green]),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(promo['code'],
                                  style: const TextStyle(
                                      color: Colors.yellowAccent,
                                      fontWeight: FontWeight.bold)),
                              const SizedBox(height: 4),
                              Text(promo['title'],
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Text('Daftar Merchant',
                      style:
                          TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 10),
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: merchants.length,
                    itemBuilder: (context, index) {
                      final m = merchants[index];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                        child: ListTile(
                          contentPadding: const EdgeInsets.all(12),
                          leading: Container(
                            width: 60,
                            height: 60,
                            decoration: BoxDecoration(
                              color: Colors.grey[200],
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Icon(Icons.store, color: Colors.teal),
                          ),
                          title: Text(m['name'],
                              style: const TextStyle(fontWeight: FontWeight.bold)),
                          subtitle: Text('${m['type']} • ${m['city']}'),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.star,
                                  color: Colors.amber, size: 16),
                              Text(' ${m['rating']}'),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildCategoryChip(String label, String type) {
    bool isSelected = selectedType == type;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        setState(() {
          selectedType = type;
        });
        fetchData();
      },
    );
  }
}

/// Gride Kirim: antar-jemput orang ke titik GPS tujuan.
/// Alur: cari alamat penjemputan & tujuan (geocoding Nominatim) -> estimasi
/// ongkos kirim (haversine, 10000 + 2500/km) -> kirim order ke server.
class KirimPage extends StatefulWidget {
  const KirimPage({super.key});

  @override
  State<KirimPage> createState() => _KirimPageState();
}

class _KirimPageState extends State<KirimPage> {
  final String baseUrl = 'https://gride.web.id/api';
  final _formKey = GlobalKey<FormState>();

  // Alamat
  final _pickupController = TextEditingController();
  final _dropoffController = TextEditingController();
  final _recipientController = TextEditingController();
  final _phoneController = TextEditingController();
  final _noteController = TextEditingController();

  // Koordinat hasil geocoding
  double? _pickupLat;
  double? _pickupLng;
  double? _dropoffLat;
  double? _dropoffLng;

  // Estimasi ongkos
  double? _distanceKm;
  int? _estimatedFee;

  bool _geocoding = false;
  bool _submitting = false;
  String? _error;
  String? _successOrder;

  @override
  void dispose() {
    _pickupController.dispose();
    _dropoffController.dispose();
    _recipientController.dispose();
    _phoneController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _geocode() async {
    final pickupText = _pickupController.text.trim();
    final dropoffText = _dropoffController.text.trim();
    if (pickupText.isEmpty || dropoffText.isEmpty) return;

    setState(() {
      _geocoding = true;
      _error = null;
      _distanceKm = null;
      _estimatedFee = null;
    });

    try {
      final headers = {'User-Agent': 'gride-customer-app/1.0'};
      final pickupRes = await http.get(
        Uri.https('nominatim.openstreetmap.org', '/search', {
          'q': pickupText,
          'format': 'json',
          'limit': '1',
          'countrycodes': 'id',
        }),
        headers: headers,
      );
      final dropoffRes = await http.get(
        Uri.https('nominatim.openstreetmap.org', '/search', {
          'q': dropoffText,
          'format': 'json',
          'limit': '1',
          'countrycodes': 'id',
        }),
        headers: headers,
      );

      final pickup = _parseFirstLocation(pickupRes.body, pickupText);
      final dropoff = _parseFirstLocation(dropoffRes.body, dropoffText);

      if (pickup == null || dropoff == null) {
        setState(() {
          _geocoding = false;
          _error = 'Alamat tidak ditemukan. Coba kata kunci lain atau isi koordinat manual.';
        });
        return;
      }

      setState(() {
        _pickupLat = pickup[0];
        _pickupLng = pickup[1];
        _dropoffLat = dropoff[0];
        _dropoffLng = dropoff[1];
        _geocoding = false;
      });

      // Haversine distance (km) & estimasi ongkos
      final d = haversineKm(_pickupLat!, _pickupLng!, _dropoffLat!, _dropoffLng!);
      final fee = 10000 + (d * 2500).round();
      setState(() {
        _distanceKm = d;
        _estimatedFee = ((fee / 100).round() * 100).clamp(10000, 10000000);
      });
    } catch (e) {
      setState(() {
        _geocoding = false;
        _error = 'Gagal mencari lokasi: $e';
      });
    }
  }

  List<double>? _parseFirstLocation(String body, String fallbackText) {
    try {
      final list = jsonDecode(body) as List;
      if (list.isEmpty) return null;
      return [
        double.parse(list[0]['lat'].toString()),
        double.parse(list[0]['lon'].toString()),
      ];
    } catch (_) {
      return null;
    }
  }

  double haversineKm(double lat1, double lon1, double lat2, double lon2) {
    const R = 6371.0;
    final dLat = _toRad(lat2 - lat1);
    final dLon = _toRad(lon2 - lon1);
    final a = sin(dLat / 2) * sin(dLat / 2) +
        cos(_toRad(lat1)) *
            cos(_toRad(lat2)) *
            sin(dLon / 2) *
            sin(dLon / 2);
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return R * c;
  }

  double _toRad(double deg) => deg * pi / 180.0;

  Future<void> _submitOrder() async {
    if (!_formKey.currentState!.validate()) return;
    if (_pickupLat == null || _dropoffLat == null) {
      _geocode();
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
      _successOrder = null;
    });

    try {
      final body = {
        'order_type': 'DELIVERY',
        'user_id': 1, // ID pengguna demo (sesuaikan dengan sistem login)
        'pickup_address': _pickupController.text.trim(),
        'pickup_lat': _pickupLat,
        'pickup_lng': _pickupLng,
        'dropoff_address': _dropoffController.text.trim(),
        'dropoff_lat': _dropoffLat,
        'dropoff_lng': _dropoffLng,
        'recipient_name': _recipientController.text.trim(),
        'recipient_phone': _phoneController.text.trim(),
        'note': _noteController.text.trim(),
      };

      final res = await http.post(
        Uri.parse('$baseUrl/orders'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(body),
      );

      final data = jsonDecode(res.body);

      if (res.statusCode == 201 ||
          (res.statusCode == 200 && data['status'] == 'success')) {
        setState(() {
          _submitting = false;
          _successOrder =
              'Pesanan dikirim! Nomor: ${data['data']?['order_number'] ?? '-'}\nOngkos kirim: Rp ${NumberFormatRp(data['data']?['delivery_fee'] ?? _estimatedFee)}\nJarak: ${_distanceKm!.toStringAsFixed(1)} km';
        });
      } else {
        setState(() {
          _submitting = false;
          _error = data['message'] ?? 'Gagal membuat pesanan.';
        });
      }
    } catch (e) {
      setState(() {
        _submitting = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gride Kirim'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text('Antar-jemput ke titik GPS tujuan',
                style: TextStyle(fontSize: 16, color: Colors.grey)),
            const SizedBox(height: 12),
            TextFormField(
              controller: _pickupController,
              decoration: const InputDecoration(
                labelText: 'Lokasi Penjemputan',
                hintText: 'e.g. Tunjungan Plaza, Surabaya',
                prefixIcon: Icon(Icons.place),
                border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Isi lokasi penjemputan' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _dropoffController,
              decoration: const InputDecoration(
                labelText: 'Alamat Tujuan',
                hintText: 'e.g. Bandara Juanda, Sidoarjo',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Isi alamat tujuan' : null,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _recipientController,
                    decoration: const InputDecoration(
                      labelText: 'Nama Penerima',
                      border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    controller: _phoneController,
                    decoration: const InputDecoration(
                      labelText: 'No. HP Penerima',
                      border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _noteController,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Catatan (opsional)',
                border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _geocoding ? null : _geocode,
                icon: _geocoding
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.map_search),
                label: Text(_geocoding ? 'Mencari lokasi...' : 'Cari Lokasi & Hitung Ongkos'),
              ),
            ),
            const SizedBox(height: 12),
            if (_distanceKm != null && _estimatedFee != null)
              Card(
                color: Colors.teal.shade50,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Estimasi Ongkos Kirim',
                          style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Colors.teal.shade800)),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Jarak'),
                          Text('${_distanceKm!.toStringAsFixed(1)} km',
                              style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Ongkos kirim (Rp 10.000 + Rp 2.500/km)'),
                          Text('Rp ${NumberFormatRp(_estimatedFee!)}',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Colors.teal)),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            if (_error != null)
              Card(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(_error!, style: TextStyle(color: Colors.red.shade800)),
                ),
              ),
            if (_successOrder != null)
              Card(
                color: Colors.green.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(_successOrder!,
                      style: TextStyle(color: Colors.green.shade800)),
                ),
              ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _submitting ? null : _submitOrder,
                icon: _submitting
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.directions_car),
                label: Text(_submitting ? 'Mengirim pesanan...' : 'Pesan Gride Kirim'),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

String NumberFormatRp(int value) => value.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]}.',
    );
