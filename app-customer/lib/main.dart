import 'package:flutter/material.dart';
import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;
import 'dart:async';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart' as ll;
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  runApp(const CustomerApp());
}

const String kApiBase = 'https://gride.web.id/api';

class CustomerApp extends StatelessWidget {
  const CustomerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gride',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: const MainShell(),
      debugShowCheckedModeBanner: false,
    );
  }
}

/// Session persistence for the logged-in customer.
class Session {
  static const String _key = 'gride_user';

  static Future<Map<String, dynamic>?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null) return null;
    try {
      return Map<String, dynamic>.from(jsonDecode(raw));
    } catch (_) {
      return null;
    }
  }

  static Future<void> save(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, jsonEncode(user));
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
  }

  static int? userIdOf(Map<String, dynamic>? user) =>
      user == null ? null : (user['id'] is int ? user['id'] as int : int.tryParse(user['id'].toString()));
}

/// Bottom-navigation shell: Home, Kirim, Antar, Akun.
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
        children: const [CustomerHome(), KirimPage(), AntarPage(), AkunPage()],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _page,
        onDestinationSelected: (i) => setState(() => _page = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.location_on_outlined), selectedIcon: Icon(Icons.location_on), label: 'Kirim'),
          NavigationDestination(icon: Icon(Icons.shopping_bag_outlined), selectedIcon: Icon(Icons.shopping_bag), label: 'Antar'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Akun'),
        ],
      ),
    );
  }
}

String formatRp(int value) =>
    'Rp ${value.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';

/// Home: promo, merchant list (FOOD/MART/SHOP), search & filter, resilient
/// against partial API failures (shows error card + retry button).
class CustomerHome extends StatefulWidget {
  const CustomerHome({super.key});

  @override
  State<CustomerHome> createState() => _CustomerHomeState();
}

class _CustomerHomeState extends State<CustomerHome> {
  List merchants = [];
  List promos = [];
  List news = [];
  bool isLoading = true;
  String? merchantError;
  String? promoError;
  String selectedType = '';
  String search = '';

  PageController? _newsPageCtrl;
  Timer? _newsTimer;
  int _newsIndex = 0;

  @override
  void initState() {
    super.initState();
    fetchData();
  }

  Future<void> fetchNews() async {
    try {
      final res = await http.get(Uri.parse('$kApiBase/news?limit=5'));
      if (res.statusCode == 200) {
        final list = (jsonDecode(res.body)['data'] as List).cast<Map<String, dynamic>>();
        if (mounted) {
          setState(() => news = list);
          _startNewsAutoSlide();
        }
      }
    } catch (_) {
      // News unavailable -> hide the section gracefully; existing page still works.
    }
  }

  void _startNewsAutoSlide() {
    _newsTimer?.cancel();
    if (news.length < 2) return;
    _newsTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (!mounted || _newsPageCtrl == null || !_newsPageCtrl!.hasClients) return;
      final idx = _newsPageCtrl!.page!.round();
      final next = (idx + 1) % news.length;
      _newsIndex = next;
      _newsPageCtrl!.animateToPage(
        next,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    });
  }

  @override
  void dispose() {
    _newsTimer?.cancel();
    _newsPageCtrl?.dispose();
    super.dispose();
  }

  Future<void> fetchData() async {
    setState(() {
      isLoading = true;
      merchantError = null;
      promoError = null;
    });
    fetchNews();
    final merchantUrl = selectedType.isEmpty
        ? '$kApiBase/merchants'
        : '$kApiBase/merchants?type=$selectedType';

    // Fetch promos independently of merchants so one failure never blanks the page.
    http.get(Uri.parse('$kApiBase/promos')).then((pRes) {
      if (pRes.statusCode == 200) {
        setState(() => promos = jsonDecode(pRes.body)['data']);
      } else {
        setState(() => promoError = 'Promo tidak dapat dimuat.');
      }
    }).catchError((Object _) {
      setState(() => promoError = 'Promo tidak dapat dimuat.');
    });

    try {
      final mRes = await http.get(Uri.parse(merchantUrl));
      if (mRes.statusCode == 200) {
        var list = (jsonDecode(mRes.body)['data'] as List).cast<Map<String, dynamic>>();
        if (search.isNotEmpty) {
          final q = search.toLowerCase();
          list = list
              .where((m) => (m['name'] ?? '').toString().toLowerCase().contains(q) ||
                  (m['description'] ?? '').toString().toLowerCase().contains(q) ||
                  (m['city'] ?? '').toString().toLowerCase().contains(q))
              .toList();
        }
        setState(() {
          merchants = list;
          isLoading = false;
        });
      } else {
        setState(() {
          isLoading = false;
          merchantError = 'Gagal memuat merchant (HTTP ${mRes.statusCode}).';
        });
      }
    } catch (e) {
      setState(() {
        isLoading = false;
        merchantError = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gride'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: RefreshIndicator(
        onRefresh: fetchData,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextField(
              decoration: InputDecoration(
                hintText: 'Cari makanan atau toko...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onChanged: (v) {
                search = v;
                fetchData();
              },
            ),
            const SizedBox(height: 16),
            Wrap(
              alignment: WrapAlignment.center,
              spacing: 8,
              children: [
                _buildCategoryChip('Semua', ''),
                _buildCategoryChip('Makanan', 'FOOD'),
                _buildCategoryChip('Toko', 'MART'),
                _buildCategoryChip('Belanja', 'SHOP'),
              ],
            ),
            const SizedBox(height: 20),
            const Text('Promo Spesial', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            if (promoError != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(promoError!, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ),
            SizedBox(
              height: 120,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: promos.isEmpty && promoError == null ? 1 : promos.length,
                itemBuilder: (context, index) {
                  if (promos.isEmpty) {
                    return const Center(child: Text('Belum ada promo', style: TextStyle(color: Colors.grey)));
                  }
                  final promo = promos[index];
                  return Container(
                    width: 250,
                    margin: const EdgeInsets.only(right: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Colors.teal, Colors.green]),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(promo['code'] ?? '',
                            style: const TextStyle(color: Colors.yellowAccent, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        Text(promo['title'] ?? '',
                            style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis),
                      ],
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 20),
            const Text('Daftar Merchant', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            if (merchantError != null)
              Card(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(merchantError!, style: TextStyle(color: Colors.red.shade800)),
                      const SizedBox(height: 8),
                      FilledButton(
                        onPressed: fetchData,
                        child: const Text('Coba lagi'),
                      ),
                    ],
                  ),
                ),
              ),
            if (isLoading) const Center(child: CircularProgressIndicator()) else if (!isLoading && merchants.isEmpty && merchantError == null)
              const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Tidak ada merchant ditemukan.'))),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: merchants.length,
              itemBuilder: (context, index) {
                final m = merchants[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    contentPadding: const EdgeInsets.all(12),
                    leading: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        m['logo_url'] ?? '',
                        width: 60,
                        height: 60,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(8)),
                          child: const Icon(Icons.store, color: Colors.teal),
                        ),
                        loadingBuilder: (context, child, progress) =>
                            progress == null ? child : const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                      ),
                    ),
                    title: Text(m['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('${m['type'] ?? ''} • ${m['city'] ?? ''}'),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.star, color: Colors.amber, size: 16),
                        Text(' ${(m['rating'] ?? '0').toString()}'),
                      ],
                    ),
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => MerchantMenuPage(merchant: Map<String, dynamic>.from(m.cast<String, dynamic>()))),
                      );
                    },
                  ),
                );
              },
            ),
            if (news.isNotEmpty) ...[
              const SizedBox(height: 28),
              const Text('Berita Terbaru', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              SizedBox(
                height: 170,
                child: PageView.builder(
                  controller: _newsPageCtrl = PageController(viewportFraction: 0.92),
                  itemCount: news.length,
                  onPageChanged: (i) => _newsIndex = i,
                  itemBuilder: (context, index) {
                    final n = news[index];
                    return GestureDetector(
                      onTap: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => NewsDetailPage(news: Map<String, dynamic>.from(n)),
                          ),
                        );
                      },
                      child: Container(
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          image: (n['featured_image'] ?? '').toString().isNotEmpty
                              ? DecorationImage(
                                  image: NetworkImage(n['featured_image'] as String),
                                  fit: BoxFit.cover,
                                )
                              : null,
                          gradient: (n['featured_image'] ?? '').toString().isEmpty
                              ? const LinearGradient(colors: [Colors.teal, Colors.green])
                              : LinearGradient(colors: [Colors.black.withOpacity(0.65), Colors.black.withOpacity(0.15)], begin: Alignment.bottomCenter, end: Alignment.topCenter),
                          color: (n['featured_image'] ?? '').toString().isEmpty ? null : Colors.black,
                          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 8, offset: const Offset(0, 3))],
                        ),
                        child: Stack(
                          children: [
                            Positioned(
                              left: 16,
                              right: 16,
                              bottom: 14,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (n['category_name'] != null)
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(color: Colors.teal.shade700, borderRadius: BorderRadius.circular(8)),
                                      child: Text('${n['category_name']}', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                                    ),
                                  const SizedBox(height: 6),
                                  Text(
                                    n['title'] ?? '',
                                    style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold),
                                    maxLines: 3,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(news.length, (i) {
                  final active = i == _newsIndex;
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: active ? 18 : 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: active ? Colors.teal : Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  );
                }),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryChip(String label, String type) {
    final isSelected = selectedType == type;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        setState(() => selectedType = type);
        fetchData();
      },
    );
  }
}
class KirimPage extends StatefulWidget {
  const KirimPage({super.key});

  @override
  State<KirimPage> createState() => _KirimPageState();
}

class _KirimPageState extends State<KirimPage> {
  final String baseUrl = kApiBase;
  final _formKey = GlobalKey<FormState>();

  // Alamat
  final _pickupController = TextEditingController();
  final _dropoffController = TextEditingController();
  final _recipientController = TextEditingController();
  final _phoneController = TextEditingController();
  final _noteController = TextEditingController();

  // Koordinat hasil geocoding / map picker
  double? _pickupLat;
  double? _pickupLng;
  double? _dropoffLat;
  double? _dropoffLng;

  // Map picker
  String? _pickMode; // 'pickup' | 'dropoff' saat memilih titik di peta
  final MapController _mapCtrl = MapController();
  double _pickLat = -7.2575; // default Kediri area (Alun-alun Kediri)
  double _pickLng = 112.0178;
  bool _pickUsingGps = false;
  String? _pickAddress;
  bool _pickReverse = false;

  // Tarif dari server (GET /api/settings)
  double _costPerKm = 5000;
  int _baseFare = 10000;

  // Estimasi ongkos
  double? _distanceKm;
  int? _estimatedFee;

  bool _geocoding = false;
  bool _submitting = false;
  String? _error;
  String? _successOrder;

  int? _userId() {
    final u = _sessionUser;
    return u == null ? null : (u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString()));
  }

  Map<String, dynamic>? _sessionUser;

  @override
  void initState() {
    super.initState();
    Session.load().then((u) => setState(() => _sessionUser = u));
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/settings'));
      if (res.statusCode == 200) {
        final d = jsonDecode(res.body)['data'];
        final cost = double.tryParse(d['ride_cost_per_km'] ?? '5000') ?? 5000;
        final base = int.tryParse(d['ride_base_fare'] ?? '10000') ?? 10000;
        setState(() {
          _costPerKm = cost;
          _baseFare = base;
        });
        // Muat ulang estimasi bila koordinat sudah ada
        if (_pickupLat != null && _dropoffLat != null) _recalcEstimate();
        return;
      }
    } catch (_) {}
  }

  void _recalcEstimate() async {
    if (_pickupLat == null || _dropoffLat == null) return;
    final d = await _osrmRoadKm(_pickupLat!, _pickupLng!, _dropoffLat!, _dropoffLng!);
    // Km dibulatkan ke angka tertinggi (ceil), lalu dikalikan harga per km.
    final ceilKm = d.ceil();
    final fee = ceilKm * _costPerKm;
    setState(() {
      _distanceKm = d;
      _estimatedFee = fee.toInt().clamp(10000, 10000000);
    });
  }

  /// Jarak mengikuti jalan raya via OSRM (meter -> km), fallback haversine.
  Future<double> _osrmRoadKm(double lat1, double lon1, double lat2, double lon2) async {
    try {
      final res = await http.get(Uri.https('router.project-osrm.org',
          '/route/v1/driving/$lon1,$lat1;$lon2,$lat2', {'overview': 'false'}));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['code'] == 'Ok' && (data['routes'] as List).isNotEmpty) {
          final meters = (data['routes'][0]['legs'][0]['distance'] as num).toDouble();
          return meters / 1000.0;
        }
      }
    } catch (_) {}
    return haversineKm(lat1, lon1, lat2, lon2);
  }

  @override
  void dispose() {
    _pickupController.dispose();
    _dropoffController.dispose();
    _recipientController.dispose();
    _phoneController.dispose();
    _noteController.dispose();
    _mapCtrl.dispose();
    super.dispose();
  }

  void _openMapPicker(String mode) async {
    // Reset titik picker: mulai dari koordinat yang sudah ada (jika ada)
    if (mode == 'pickup' && _pickupLat != null) {
      _pickLat = _pickupLat!;
      _pickLng = _pickupLng!;
    } else if (mode == 'dropoff' && _dropoffLat != null) {
      _pickLat = _dropoffLat!;
      _pickLng = _dropoffLng!;
    }
    _pickAddress = null;
    _pickReverse = false;
    setState(() => _pickMode = mode);
  }

  Future<void> _useMyLocation() async {
    if (!mounted) return;
    setState(() => _pickUsingGps = true);
    try {
      bool enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        setState(() {
          _pickUsingGps = false;
          _pickAddress = 'Layanan lokasi tidak aktif. Geser titik manual.';
        });
        return;
      }
      LocationPermission perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm != LocationPermission.whileInUse && perm != LocationPermission.always) {
        setState(() {
          _pickUsingGps = false;
          _pickAddress = 'Izin lokasi ditolak. Geser titik manual.';
        });
        return;
      }
      final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      setState(() {
        _pickLat = pos.latitude;
        _pickLng = pos.longitude;
      });
      _mapCtrl.move(ll.LatLng(_pickLat, _pickLng), 15);
      await _reverseGeocode();
    } catch (e) {
      setState(() => _pickAddress = 'GPS gagal: $e. Geser titik manual.');
    } finally {
      if (mounted) setState(() => _pickUsingGps = false);
    }
  }

  Future<void> _reverseGeocode() async {
    setState(() => _pickReverse = true);
    try {
      final res = await http.get(Uri.https('nominatim.openstreetmap.org', '/reverse', {
        'format': 'json',
        'lat': _pickLat.toString(),
        'lon': _pickLng.toString(),
        'zoom': '18',
        'addressdetails': '1',
      }), headers: {'User-Agent': 'gride-customer-app/1.0'});
      if (res.statusCode == 200) {
        final d = jsonDecode(res.body);
        if (mounted) setState(() => _pickAddress = d['display_name'] ?? 'Titik dipilih');
      } else if (mounted) {
        setState(() => _pickAddress = 'Titik dipilih');
      }
    } catch (_) {
      if (mounted) setState(() => _pickAddress = 'Titik dipilih');
    } finally {
      if (mounted) setState(() => _pickReverse = false);
    }
  }

  void _confirmPoint() {
    final mode = _pickMode;
    final address = _pickAddress ?? 'Titik dipilih';
    if (mode == 'pickup') {
      _pickupController.text = address;
      setState(() {
        _pickupLat = _pickLat;
        _pickupLng = _pickLng;
      });
    } else {
      _dropoffController.text = address;
      setState(() {
        _dropoffLat = _pickLat;
        _dropoffLng = _pickLng;
      });
    }
    setState(() => _pickMode = null);
    _recalcEstimate();
  }

  void _cancelPick() {
    setState(() => _pickMode = null);
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

      _recalcEstimate();
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
        'user_id': _userId() ?? 3, // akun login (3 = akun demo)
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
          final invoice = data['data']?['invoice'];
          final lines = <String>['Pesanan dikirim! Nomor: ${data['data']?['order_number'] ?? '-'}'];
          if (invoice != null) {
            if (invoice['trip_distance_km'] != null) {
              lines.add('Jarak: ${double.tryParse(invoice['trip_distance_km'].toString())?.toStringAsFixed(1) ?? '-'} km');
            }
            lines.add('Tarif dasar: Rp ${formatRp((invoice['base_fare'] ?? 0).round())}');
            lines.add('Biaya perjalanan: Rp ${formatRp((invoice['trip_cost'] ?? 0).round())}');
            if ((invoice['admin_commission'] ?? 0) > 0) {
              lines.add('${invoice['admin_commission_label']}: Rp ${formatRp((invoice['admin_commission']).round())}');
            }
            lines.add('TOTAL: Rp ${formatRp((invoice['total'] ?? 0).round())}');
          } else {
            lines.add('Ongkos kirim: Rp ${formatRp((data['data']?['delivery_fee'] ?? _estimatedFee ?? 0).round())}');
            lines.add('Jarak: ${_distanceKm?.toStringAsFixed(1) ?? '-'} km');
          }
          _successOrder = lines.join('\n');
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
            if (_pickMode == null)
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: () => _openMapPicker('pickup'),
                  icon: const Icon(Icons.map, size: 18),
                  label: const Text('Pilih titik penjemputan dari peta'),
                ),
              ),
            const SizedBox(height: 6),
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
            if (_pickMode == null)
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: () => _openMapPicker('dropoff'),
                  icon: const Icon(Icons.map, size: 18),
                  label: const Text('Pilih titik tujuan dari peta'),
                ),
              ),
            const SizedBox(height: 6),
            if (_pickMode != null)
              Card(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                clipBehavior: Clip.antiAlias,
                child: Column(
                  children: [
                    Container(
                      height: 300,
                      color: Colors.grey.shade200,
                      child: FlutterMap(
                        mapController: _mapCtrl,
                        options: MapOptions(
                          initialCenter: ll.LatLng(_pickLat, _pickLng),
                          initialZoom: 14,
                          onTap: (_, point) {
                            setState(() {
                              _pickLat = point.latitude;
                              _pickLng = point.longitude;
                            });
                            _reverseGeocode();
                          },
                        ),
                        children: [
                          TileLayer(
                            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            userAgentPackageName: 'com.gride.app',
                            maxNativeZoom: 19,
                          ),
                          MarkerLayer(
                            markers: [
                              Marker(
                                point: ll.LatLng(_pickLat, _pickLng),
                                width: 44,
                                height: 44,
                                child: const Icon(Icons.location_pin, color: Colors.red, size: 44),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _pickMode == 'pickup' ? 'Titik Penjemputan' : 'Titik Tujuan',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _pickReverse
                                ? 'Mencari alamat...'
                                : (_pickAddress ?? 'Ketuk peta untuk memilih titik.'),
                            style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
                          ),
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 8,
                            children: [
                              FilledButton.tonalIcon(
                                onPressed: _pickUsingGps ? null : _useMyLocation,
                                icon: _pickUsingGps
                                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                                    : const Icon(Icons.my_location, size: 18),
                                label: const Text('GPS saya'),
                              ),
                              FilledButton.icon(
                                onPressed: _pickReverse || _pickAddress == null ? null : _confirmPoint,
                                icon: const Icon(Icons.check, size: 18),
                                label: const Text('Konfirmasi titik'),
                              ),
                              OutlinedButton(
                                onPressed: _cancelPick,
                                child: const Text('Batal'),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            const SizedBox(height: 6),
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
                    : const Icon(Icons.location_searching),
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
                          Text('Jarak jalan raya'),
                          Text('${_distanceKm!.toStringAsFixed(1)} km \u2192 dibulatkan ${_distanceKm!.ceil()} km',
                              style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('${_distanceKm!.ceil()} km \u00d7 Rp ${formatRp(_costPerKm.round())}/km'),
                          Text('Rp ${formatRp(_estimatedFee!)}',
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


/// Antar: pesan makanan & belanja. Pilih merchant -> lihat menu -> keranjang
/// -> kirim pesanan (POST /api/orders, order_type FOOD/MART).
class AntarPage extends StatefulWidget {
  const AntarPage({super.key});

  @override
  State<AntarPage> createState() => _AntarPageState();
}

class _AntarPageState extends State<AntarPage> {
  List merchants = [];
  bool isLoading = true;
  String? error;
  String selectedType = 'FOOD';
  String search = '';
  Map<String, dynamic>? _sessionUser;

  @override
  void initState() {
    super.initState();
    Session.load().then((u) {
      setState(() => _sessionUser = u);
      fetchData();
    });
  }

  Future<void> fetchData() async {
    setState(() {
      isLoading = true;
      error = null;
    });
    try {
      var url = '$kApiBase/merchants?type=$selectedType';
      if (search.isNotEmpty) url += '&search=$search';
      final res = await http.get(Uri.parse(url));
      if (res.statusCode == 200) {
        setState(() {
          merchants = jsonDecode(res.body)['data'];
          isLoading = false;
        });
      } else {
        setState(() {
          isLoading = false;
          error = 'Gagal memuat daftar merchant (HTTP ${res.statusCode}).';
        });
      }
    } catch (e) {
      setState(() {
        isLoading = false;
        error = 'Koneksi gagal: $e';
      });
    }
  }

  int? _userId() {
    final u = _sessionUser;
    return u == null ? null : (u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString()));
  }

  @override
  Widget build(BuildContext context) {
    final loggedIn = _sessionUser != null;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Antar - Pesan Makanan & Belanja'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: !loggedIn
          ? const Center(child: Padding(
              padding: EdgeInsets.all(32),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.login, size: 48, color: Colors.teal),
                  SizedBox(height: 16),
                  Text('Silakan daftar atau login dulu di tab Akun\nuntuk mulai memesan.', textAlign: TextAlign.center),
                ],
              ),
            ))
          : RefreshIndicator(
              onRefresh: fetchData,
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _buildTypeChip('Makanan', 'FOOD'),
                        _buildTypeChip('Toko', 'MART'),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                    child: TextField(
                      decoration: InputDecoration(
                        hintText: 'Cari merchant...',
                        prefixIcon: const Icon(Icons.search),
                        isDense: true,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onChanged: (v) {
                        search = v;
                        fetchData();
                      },
                    ),
                  ),
                  Expanded(
                    child: isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : error != null
                            ? Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(error!, style: TextStyle(color: Colors.red.shade800)),
                                    const SizedBox(height: 8),
                                    FilledButton(onPressed: fetchData, child: const Text('Coba lagi')),
                                  ],
                                ),
                              )
                            : merchants.isEmpty
                                ? const Center(child: Text('Tidak ada merchant ditemukan.'))
                                : ListView.builder(
                                    padding: const EdgeInsets.all(16),
                                    itemCount: merchants.length,
                                    itemBuilder: (context, index) {
                                      final m = merchants[index];
                                      return Card(
                                        margin: const EdgeInsets.only(bottom: 12),
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                        child: ListTile(
                                          contentPadding: const EdgeInsets.all(12),
                                          leading: ClipRRect(
                                            borderRadius: BorderRadius.circular(8),
                                            child: Image.network(
                                              m['logo_url'] ?? '',
                                              width: 60,
                                              height: 60,
                                              fit: BoxFit.cover,
                                              errorBuilder: (_, __, ___) => Container(
                                                width: 60,
                                                height: 60,
                                                decoration: BoxDecoration(
                                                    color: Colors.grey[200], borderRadius: BorderRadius.circular(8)),
                                                child: const Icon(Icons.store, color: Colors.teal),
                                              ),
                                              loadingBuilder: (context, child, progress) =>
                                                  progress == null
                                                      ? child
                                                      : const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                                            ),
                                          ),
                                          title: Text(m['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                                          subtitle: Text(m['city'] ?? ''),
                                          onTap: () => Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                                builder: (_) => OrderMerchantPage(
                                                      merchant: Map<String, dynamic>.from(m.cast<String, dynamic>()),
                                                      orderType: selectedType,
                                                      userId: _userId()!,
                                                    )),
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildTypeChip(String label, String type) {
    final isSelected = selectedType == type;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        setState(() => selectedType = type);
        fetchData();
      },
    );
  }
}

/// Detail merchant + menu + keranjang -> kirim pesanan FOOD/MART.
class OrderMerchantPage extends StatefulWidget {
  final Map<String, dynamic> merchant;
  final String orderType;
  final int userId;
  const OrderMerchantPage({super.key, required this.merchant, required this.orderType, required this.userId});

  @override
  State<OrderMerchantPage> createState() => _OrderMerchantPageState();
}

class _OrderMerchantPageState extends State<OrderMerchantPage> {
  List menu = [];
  final Map<int, int> _cart = {}; // menu_item id -> qty
  bool isLoading = true;
  String? error;
  String _address = '';
  bool _submitting = false;
  String? resultMsg;

  @override
  void initState() {
    super.initState();
    _loadMenu();
  }

  Future<void> _loadMenu() async {
    setState(() {
      isLoading = true;
      error = null;
    });
    try {
      final res = await http.get(Uri.parse('$kApiBase/merchants/${widget.merchant['id']}/menu'));
      if (res.statusCode == 200) {
        setState(() {
          menu = jsonDecode(res.body)['menu'];
          isLoading = false;
        });
      } else {
        setState(() {
          isLoading = false;
          error = 'Gagal memuat menu (HTTP ${res.statusCode}).';
        });
      }
    } catch (e) {
      setState(() {
        isLoading = false;
        error = 'Koneksi gagal: $e';
      });
    }
  }

  int _subtotal() {
    int total = 0;
    for (final m in menu) {
      final qty = _cart[m['id']] ?? 0;
      total += qty * ((double.tryParse(m['price']?.toString() ?? '0') ?? 0).round());
    }
    return total;
  }

  Future<void> _submitOrder() async {
    if (_address.trim().isEmpty) {
      setState(() => resultMsg = 'Isi alamat pengiriman terlebih dahulu.');
      return;
    }
    setState(() {
      _submitting = true;
      resultMsg = null;
    });
    final items = <Map<String, dynamic>>[];
    for (final m in menu) {
      final qty = _cart[m['id']] ?? 0;
      if (qty > 0) {
        items.add({
          'product_id': m['id'],
          'name': m['name'],
          'qty': qty,
          'price': (double.tryParse(m['price']?.toString() ?? '0') ?? 0).round(),
        });
      }
    }
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/orders'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'order_type': widget.orderType,
          'user_id': widget.userId,
          'merchant_id': widget.merchant['id'],
          'delivery_address': _address.trim(),
          'items': items,
        }),
      );
      final data = jsonDecode(res.body);
      if (res.statusCode == 201 || (res.statusCode == 200 && data['status'] == 'success')) {
        final invoice = data['data']?['invoice'];
        final lines = <String>['Pesanan dikirim! Nomor: ${data['data']?['order_number'] ?? '-'}'];
        if (invoice != null) {
          lines.add('${invoice['subtotal_label']}: ${formatRp((invoice['subtotal'] ?? 0).round())}');
          if ((invoice['delivery_fee'] ?? 0) > 0) {
            lines.add('Ongkos kirim: ${formatRp((invoice['delivery_fee'] ?? 0).round())}');
          }
          lines.add('TOTAL: ${formatRp((invoice['total'] ?? 0).round())}');
        }
        setState(() {
          _submitting = false;
          resultMsg = lines.join('\n');
          _cart.clear();
        });
      } else {
        setState(() {
          _submitting = false;
          resultMsg = data['message'] ?? 'Gagal membuat pesanan.';
        });
      }
    } catch (e) {
      setState(() {
        _submitting = false;
        resultMsg = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.merchant['name'] ?? 'Menu'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Expanded(
            child: isLoading
                ? const Center(child: CircularProgressIndicator())
                : error != null
                    ? Center(
                        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Text(error!, style: TextStyle(color: Colors.red.shade800)),
                          const SizedBox(height: 8),
                          FilledButton(onPressed: _loadMenu, child: const Text('Coba lagi')),
                        ]),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: menu.length,
                        itemBuilder: (context, index) {
                          final item = menu[index];
                          final qty = _cart[item['id']] ?? 0;
                          final price = (double.tryParse(item['price']?.toString() ?? '0') ?? 0).round();
                          return Card(
                            margin: const EdgeInsets.only(bottom: 10),
                            child: ListTile(
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              title: Text(item['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: Text(item['description'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                              trailing: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(formatRp(price), style: const TextStyle(color: Colors.teal)),
                                  const SizedBox(width: 10),
                                  IconButton(
                                    icon: const Icon(Icons.remove_circle_outline),
                                    onPressed: qty <= 0
                                        ? null
                                        : () => setState(() => _cart[item['id']] = qty - 1),
                                  ),
                                  Text('$qty', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                                  IconButton(
                                    icon: const Icon(Icons.add_circle, color: Colors.teal),
                                    onPressed: () => setState(() => _cart[item['id']] = qty + 1),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 6, offset: const Offset(0, -2))],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  maxLines: 2,
                  decoration: InputDecoration(
                    hintText: 'Alamat pengiriman / nama jalan...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    isDense: true,
                  ),
                  onChanged: (v) => _address = v,
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Keranjang: ${_cart.values.fold<int>(0, (a, b) => a + b)} item',
                        style: const TextStyle(fontWeight: FontWeight.bold)),
                    Text('Subtotal: ${formatRp(_subtotal())}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                  ],
                ),
                const SizedBox(height: 8),
                if (resultMsg != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Text(resultMsg!,
                        style: TextStyle(
                          color: (resultMsg!.contains('Pesanan') || resultMsg!.contains('Nomor'))
                              ? Colors.green.shade800
                              : Colors.red.shade800,
                        )),
                  ),
                FilledButton(
                  onPressed: _submitting ? null : _submitOrder,
                  child: _submitting
                      ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('Kirim Pesanan'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Akun: daftar / login / profil / riwayat pesanan.
class AkunPage extends StatefulWidget {
  const AkunPage({super.key});

  @override
  State<AkunPage> createState() => _AkunPageState();
}

class _AkunPageState extends State<AkunPage> {
  Map<String, dynamic>? _user;
  List _orders = [];
  bool showRegister = true; // true = form daftar, false = form login
  bool _busy = false;
  String? _msg;
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _refreshSession();
  }

  Future<void> _refreshSession() async {
    final u = await Session.load();
    setState(() => _user = u);
    if (u != null) _loadOrders();
  }

  Future<void> _loadOrders() async {
    if (_user == null) return;
    try {
      final res = await http.get(Uri.parse('$kApiBase/orders?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        setState(() => _orders = jsonDecode(res.body)['data']);
      }
    } catch (_) {}
  }

  Future<void> _register() async {
    if (_nameCtrl.text.trim().isEmpty ||
        _emailCtrl.text.trim().isEmpty ||
        _passCtrl.text.length < 6) {
      setState(() => _msg = 'Isi nama, email, dan password (min. 6 karakter).');
      return;
    }
    setState(() {
      _busy = true;
      _msg = null;
    });
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'full_name': _nameCtrl.text.trim(),
          'email': _emailCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim().isEmpty ? null : _phoneCtrl.text.trim(),
          'password': _passCtrl.text,
        }),
      );
      final data = jsonDecode(res.body);
      if (res.statusCode == 201) {
        setState(() {
          _busy = false;
          showRegister = false;
          _msg = '${data['message']} Silakan login.';
        });
      } else {
        setState(() {
          _busy = false;
          _msg = data['message'] ?? 'Gagal mendaftar.';
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _msg = 'Koneksi gagal: $e';
      });
    }
  }

  Future<void> _login() async {
    if (_emailCtrl.text.trim().isEmpty || _passCtrl.text.isEmpty) {
      setState(() => _msg = 'Isi email dan password.');
      return;
    }
    setState(() {
      _busy = true;
      _msg = null;
    });
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': _emailCtrl.text.trim(),
          'password': _passCtrl.text,
        }),
      );
      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == 'success') {
        await Session.save(data['data']);
        await _refreshSession();
        _passCtrl.clear();
        setState(() => _msg = 'Selamat datang, ${data['data']['full_name']}!');
      } else {
        setState(() {
          _busy = false;
          _msg = data['message'] ?? 'Gagal login.';
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _msg = 'Koneksi gagal: $e';
      });
    }
  }

  Future<void> _logout() async {
    await Session.clear();
    setState(() {
      _user = null;
      _orders = [];
      _msg = null;
    });
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_user == null ? (showRegister ? 'Daftar Akun' : 'Login') : 'Akun Saya'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: _user == null
          ? ListView(
              padding: const EdgeInsets.all(20),
              children: [
                SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'daftar', label: Text('Daftar')),
                    ButtonSegment(value: 'login', label: Text('Login')),
                  ],
                  selected: {showRegister ? 'daftar' : 'login'},
                  onSelectionChanged: (s) => setState(() {
                    showRegister = s.first == 'daftar';
                    _msg = null;
                  }),
                ),
                const SizedBox(height: 20),
                if (showRegister)
                  TextField(
                    controller: _nameCtrl,
                    decoration: const InputDecoration(
                      labelText: 'Nama lengkap',
                      border: OutlineInputBorder(),
                      prefixIcon: Icon(Icons.person),
                    ),
                  ),
                if (showRegister) const SizedBox(height: 12),
                TextField(
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email),
                  ),
                ),
                if (showRegister) const SizedBox(height: 12),
                if (showRegister)
                  TextField(
                    controller: _phoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'No. HP (opsional)',
                      border: OutlineInputBorder(),
                      prefixIcon: Icon(Icons.phone),
                    ),
                  ),
                const SizedBox(height: 12),
                TextField(
                  controller: _passCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password (min. 6 karakter)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.lock),
                  ),
                ),
                const SizedBox(height: 16),
                if (_msg != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Text(_msg!,
                        style: TextStyle(
                          color: (_msg!.contains('Selamat datang') || _msg!.contains('berhasil'))
                              ? Colors.green.shade800
                              : Colors.red.shade800,
                        )),
                  ),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _busy ? null : (showRegister ? _register : _login),
                    icon: _busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : (showRegister ? const Icon(Icons.person_add) : const Icon(Icons.login)),
                    label: Text(showRegister ? 'Daftar' : 'Login'),
                  ),
                ),
                const SizedBox(height: 20),
                const Text(
                  'Belum punya akun? Tekan tab Daftar, isi data, lalu Login.\nAkun demo: customer@superapp.com / password',
                  style: TextStyle(color: Colors.grey, fontSize: 13),
                ),
              ],
            )
          : RefreshIndicator(
              onRefresh: _loadOrders,
              child: ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  Card(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    child: Padding(
                      padding: const EdgeInsets.all(18),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const CircleAvatar(radius: 28, backgroundColor: Colors.teal, child: Icon(Icons.person, color: Colors.white, size: 32)),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(_user!['full_name'] ?? '', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                                    Text(_user!['email'] ?? '', style: TextStyle(color: Colors.grey.shade700)),
                                    if (_user!['phone'] != null) Text(_user!['phone'], style: TextStyle(color: Colors.grey.shade700)),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: OutlinedButton.icon(
                              onPressed: _logout,
                              icon: const Icon(Icons.logout),
                              label: const Text('Logout'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Riwayat Pesanan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 10),
                  if (_orders.isEmpty) const Text('Belum ada pesanan.', style: TextStyle(color: Colors.grey)),
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _orders.length,
                    itemBuilder: (context, index) {
                      final o = _orders[index];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                          title: Text(o['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                          subtitle: Text('${o['order_type'] ?? ''} • ${(o['total_amount'] ?? 0)} • ${o['status'] ?? ''}'),
                          trailing: Text(o['created_at']?.toString().substring(0, 10) ?? ''),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
    );
  }
}

/// MerchantMenuPage: tampilan menu merchant (dari tap Home). Mirip OrderMerchantPage
/// tetapi sederhana — hanya menampilkan menu + info merchant, dengan tombol pesan.
class MerchantMenuPage extends StatefulWidget {
  final Map<String, dynamic> merchant;
  const MerchantMenuPage({super.key, required this.merchant});

  @override
  State<MerchantMenuPage> createState() => _MerchantMenuPageState();
}

class _MerchantMenuPageState extends State<MerchantMenuPage> {
  List menu = [];
  bool isLoading = true;
  String? error;
  Map<String, dynamic>? _sessionUser;

  @override
  void initState() {
    super.initState();
    Session.load().then((u) => setState(() => _sessionUser = u));
    _loadMenu();
  }

  Future<void> _loadMenu() async {
    setState(() {
      isLoading = true;
      error = null;
    });
    try {
      final res = await http.get(Uri.parse('$kApiBase/merchants/${widget.merchant['id']}/menu'));
      if (res.statusCode == 200) {
        setState(() {
          menu = jsonDecode(res.body)['menu'];
          isLoading = false;
        });
      } else {
        setState(() {
          isLoading = false;
          error = 'Gagal memuat menu (HTTP ${res.statusCode}).';
        });
      }
    } catch (e) {
      setState(() {
        isLoading = false;
        error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.merchant['name'] ?? 'Menu'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            color: Colors.grey.shade50,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${widget.merchant['type'] ?? ''} • ${widget.merchant['city'] ?? ''}',
                    style: TextStyle(color: Colors.grey.shade700)),
                const SizedBox(height: 4),
                Text(widget.merchant['description'] ?? '', style: const TextStyle(fontSize: 13)),
              ],
            ),
          ),
          Expanded(
            child: isLoading
                ? const Center(child: CircularProgressIndicator())
                : error != null
                    ? Center(
                        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Text(error!, style: TextStyle(color: Colors.red.shade800)),
                          const SizedBox(height: 8),
                          FilledButton(onPressed: _loadMenu, child: const Text('Coba lagi')),
                        ]),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: menu.length,
                        itemBuilder: (context, index) {
                          final item = menu[index];
                          final price = (double.tryParse(item['price']?.toString() ?? '0') ?? 0).round();
                          return Card(
                            margin: const EdgeInsets.only(bottom: 10),
                            child: ListTile(
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              leading: Image.network(
                                item['image_url'] ?? '',
                                width: 56,
                                height: 56,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) =>
                                    const Icon(Icons.fastfood, color: Colors.teal, size: 36),
                              ),
                              title: Text(item['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                              subtitle: Text(item['description'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                              trailing: Text(formatRp(price), style: const TextStyle(color: Colors.teal, fontWeight: FontWeight.bold)),
                            ),
                          );
                        },
                      ),
          ),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Colors.white, boxShadow: [
              BoxShadow(color: Colors.black12, blurRadius: 6, offset: const Offset(0, -2)),
            ]),
            child: FilledButton.icon(
              onPressed: () {
                final u = _sessionUser;
                if (u == null) {
                  ScaffoldMessenger.of(context)
                      .showSnackBar(const SnackBar(content: Text('Silakan daftar/login dulu di tab Akun')));
                  return;
                }
                final id = u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString());
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => OrderMerchantPage(
                      merchant: widget.merchant,
                      orderType: widget.merchant['type'] == 'SHOP' ? 'SHOP' : 'FOOD',
                      userId: id ?? 3,
                    ),
                  ),
                );
              },
              icon: const Icon(Icons.shopping_cart),
              label: const Text('Pesan dari merchant ini'),
            ),
          ),
        ],
      ),
    );
  }
}

/// News detail page opened from the home news carousel.
class NewsDetailPage extends StatelessWidget {
  final Map<String, dynamic> news;

  const NewsDetailPage({super.key, required this.news});

  @override
  Widget build(BuildContext context) {
    final imageUrl = (news['featured_image'] ?? '').toString();
    final publishedAt = news['published_at']?.toString() ?? '';
    String dateText = publishedAt;
    if (publishedAt.contains(' ')) dateText = publishedAt.split(' ').first;
    return Scaffold(
      backgroundColor: Colors.white,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: imageUrl.isNotEmpty ? 260 : 120,
            pinned: true,
            backgroundColor: Colors.teal,
            foregroundColor: Colors.white,
            flexibleSpace: imageUrl.isNotEmpty
                ? FlexibleSpaceBar(
                    background: Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(color: Colors.teal.shade300),
                    ),
                  )
                : const FlexibleSpaceBar(background: SizedBox.shrink()),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (news['category_name'] != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.teal.shade50,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${news['category_name']}',
                            style: TextStyle(color: Colors.teal.shade800, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ),
                      const Spacer(),
                      if (dateText.isNotEmpty)
                        Text(dateText, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    news['title'] ?? '',
                    style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, height: 1.25),
                  ),
                  const SizedBox(height: 14),
                  if ((news['excerpt'] ?? '').toString().isNotEmpty)
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(color: Colors.teal.shade50, borderRadius: BorderRadius.circular(12)),
                      child: Text(
                        '${news['excerpt']}',
                        style: TextStyle(color: Colors.teal.shade800, fontSize: 14, fontStyle: FontStyle.italic),
                      ),
                    ),
                  const SizedBox(height: 18),
                  Text(
                    (news['content'] ?? '').toString(),
                    style: const TextStyle(fontSize: 16, height: 1.6),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
