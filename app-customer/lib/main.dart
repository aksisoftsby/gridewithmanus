import 'package:flutter/material.dart';
import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;
import 'dart:async';
import 'package:flutter/services.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart' as ll;
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  runApp(const CustomerApp());
}

const String kApiBase = 'https://gride.web.id/api';

// Purple-gold branding shared by home, form, and Iklan Gratis screens.
const Color kPurpleMain = Color(0xFF4B1D7E);
const Color kPurpleCard = Color(0xFF5C2A96);
const Color kGold = Color(0xFFE8B84B);
const Color kGoldBright = Color(0xFFF7D27E);

/// Global key exposing MainShell state so home tiles can switch bottom tabs.
final GlobalKey<_MainShellState> _shellStateKey = GlobalKey<_MainShellState>();

class CustomerApp extends StatelessWidget {
  const CustomerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gride',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF4B1D7E)),
        useMaterial3: true,
      ),
      home: MainShell(key: _shellStateKey),
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
  /// Switch bottom tab programmatically (used by home service grid).
  void jumpTo(int page) => setState(() => _page = page);
  int _page = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _page,
        children: const [CustomerHome(), KirimPage(), AntarPage(), AkunPage()],
      ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(colors: [Color(0xFF3A1566), Color(0xFF2A0E4E)]),
          boxShadow: [BoxShadow(color: Color(0xFFE8B84B), blurRadius: 14, offset: Offset(0, -2))],
        ),
        child: NavigationBar(
          height: 72,
          backgroundColor: Colors.transparent,
          indicatorColor: Colors.transparent,
          selectedIndex: _page,
          onDestinationSelected: (i) => setState(() => _page = i),
          destinations: const [
            NavigationDestination(icon: Icon(Icons.headset_mic_outlined, color: Color(0xFFE8B84B)), selectedIcon: Icon(Icons.headset_mic, color: Color(0xFFE8B84B)), label: 'Admin'),
            NavigationDestination(icon: Icon(Icons.grid_view, color: Color(0xFFE8B84B)), selectedIcon: Icon(Icons.grid_view, color: Color(0xFFE8B84B)), label: 'Menu'),
            NavigationDestination(icon: Icon(Icons.person_outline, color: Color(0xFFE8B84B)), selectedIcon: Icon(Icons.person, color: Color(0xFFE8B84B)), label: 'Profil'),
          ],
        ),
      ),
    );
  }
}

String formatRp(int value) =>
    'Rp ${value.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';

/// Biaya layanan (admin fee) — terlihat di bottom sheet pesanan.
const int kAdminFee = 2000;

/// Helper to perform HTTP GET with auto-retry up to 3 times on failure.
Future<http.Response> httpGetWithRetry(String url, {int maxRetries = 3}) async {
  int attempt = 0;
  while (true) {
    attempt++;
    try {
      final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 10));
      // Consider 5xx errors as server drop/temporary failure that warrants retry.
      if (response.statusCode >= 500 && attempt < maxRetries) {
        await Future.delayed(Duration(seconds: 1 * attempt)); // Exponential backoff-ish
        continue;
      }
      return response;
    } catch (e) {
      if (attempt >= maxRetries) rethrow;
      await Future.delayed(Duration(seconds: 1 * attempt));
    }
  }
}

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
  Map<String, dynamic>? _sessionUser;
  int _walletBalance = 0;
  bool _walletLoading = true;

  PageController? _newsPageCtrl;
  Timer? _newsTimer;
  int _newsIndex = 0;

  static const Color kDeepPurple = Color(0xFF2A0E4E);
  static const Color kPurple = kPurpleMain;

  @override
  void initState() {
    super.initState();
    _loadWallet();
    fetchData();
  }

  Future<void> _loadWallet() async {
    final user = await Session.load();
    if (!mounted) return;
    setState(() => _sessionUser = user);
    if (user == null) {
      setState(() => _walletLoading = false);
      return;
    }
    final uid = Session.userIdOf(user);
    if (uid == null) {
      setState(() => _walletLoading = false);
      return;
    }
    try {
      final res = await httpGetWithRetry('$kApiBase/wallets?user_id=$uid');
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body)['data'];
        if (list is List && list.isNotEmpty) {
          final bal = num.tryParse(list.first['balance'].toString()) ?? 0;
          if (mounted) setState(() => _walletBalance = bal.toInt());
        }
      }
    } catch (_) {
      // Wallet unavailable -> show Rp 0 gracefully.
    } finally {
      if (mounted) setState(() => _walletLoading = false);
    }
  }

  Future<void> fetchNews() async {
    try {
      final res = await httpGetWithRetry('$kApiBase/news?limit=5');
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
    httpGetWithRetry('$kApiBase/promos').then((pRes) {
      if (pRes.statusCode == 200) {
        setState(() => promos = jsonDecode(pRes.body)['data']);
      } else {
        setState(() => promoError = 'Promo tidak dapat dimuat.');
      }
    }).catchError((Object _) {
      setState(() => promoError = 'Promo tidak dapat dimuat.');
    });

    try {
      final mRes = await httpGetWithRetry(merchantUrl);
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
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [kDeepPurple, kPurple, Color(0xFF6A35B8)],
            stops: [0.0, 0.45, 1.0],
          ),
        ),
        child: RefreshIndicator(
          onRefresh: fetchData,
          color: kGold,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Header: logo + profile
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Image.asset('assets/gride_logo.png', height: 84, fit: BoxFit.contain),
                  GestureDetector(
                    onTap: () => _shellStateKey.currentState?.jumpTo(3),
                    child: Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(color: kGold, width: 2),
                        color: kGold.withOpacity(0.15),
                      ),
                      child: Icon(Icons.person, color: kGold, size: 28),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // GrSaldo wallet card
              Container(
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [Color(0xFF5C2A96), Color(0xFF3D1570)]),
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.35), blurRadius: 18, offset: const Offset(0, 8))],
                  border: Border.all(color: kGold.withOpacity(0.35), width: 1),
                ),
                padding: const EdgeInsets.all(20),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Row(children: [Icon(Icons.account_balance_wallet, color: kGoldBright, size: 18), SizedBox(width: 8), Text('GrSaldo', style: TextStyle(color: Color(0xFFF7D27E), fontWeight: FontWeight.bold, fontSize: 14))]),
                          const SizedBox(height: 8),
                          Text(formatRp(_walletLoading ? 0 : _walletBalance),
                              style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w900)),
                          const SizedBox(height: 10),
                          const Text('Tap for explore', style: TextStyle(color: Color(0xFFCFC3EE), fontSize: 12)),
                        ],
                      ),
                    ),
                    Row(
                      children: [
                        _walletActionButton(Icons.keyboard_arrow_up, 'Tarik'),
                        const SizedBox(width: 12),
                        _walletActionButton(Icons.add, 'Top Up'),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 28),

              // Layanan favorit Anda
              const Text('Layanan favorit Anda', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              _buildServicesGrid(),
              const SizedBox(height: 28),

              // Search bar
              Container(
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                child: TextField(
                  style: const TextStyle(color: Color(0xFF2A0E4E)),
                  decoration: InputDecoration(
                    hintText: 'Cari makanan atau toko...',
                    hintStyle: TextStyle(color: Colors.deepPurple.shade200),
                    prefixIcon: Icon(Icons.search, color: kGold),
                    filled: true,
                    fillColor: Colors.white,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                    contentPadding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  onChanged: (v) {
                    search = v;
                    fetchData();
                  },
                ),
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
            const Text('Promo Spesial', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
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
                    return const Center(child: Text('Belum ada promo', style: TextStyle(color: Color(0xFFCFC3EE))));
                  }
                  final promo = promos[index];
                  return Container(
                    width: 250,
                    margin: const EdgeInsets.only(right: 12),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFFE8B84B), Color(0xFFB8862C)]),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [BoxShadow(color: kGold.withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(promo['code'] ?? '',
                            style: const TextStyle(color: Color(0xFF2A0E4E), fontWeight: FontWeight.w900)),
                        const SizedBox(height: 4),
                        Text(promo['title'] ?? '',
                            style: const TextStyle(color: Color(0xFF2A0E4E), fontSize: 14, fontWeight: FontWeight.bold),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis),
                      ],
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 20),
            const Text('Daftar Merchant', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
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
                  color: const Color(0xFF3D1570),
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
                          decoration: BoxDecoration(color: kPurpleCard, borderRadius: BorderRadius.circular(8), border: Border.all(color: kGold.withOpacity(0.4))),
                          child: const Icon(Icons.store, color: kGold),
                        ),
                        loadingBuilder: (context, child, progress) =>
                            progress == null ? child : const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                      ),
                    ),
                    title: Text(m['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                    subtitle: Text('${m['type'] ?? ''} • ${m['city'] ?? ''}', style: const TextStyle(color: Color(0xFFCFC3EE))),
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
                          image: DecorationImage(
                            image: NetworkImage((n['featured_image'] ?? '').toString().isNotEmpty ? n['featured_image'] : 'https://placehold.co/600x400/4B1D7E/E8B84B?text=Gride+News'),
                            fit: BoxFit.cover,
                          ),
                          gradient: (n['featured_image'] ?? '').toString().isEmpty
                              ? const LinearGradient(colors: [Color(0xFF5C2A96), Color(0xFFB8862C)])
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
                                      decoration: BoxDecoration(color: kGold, borderRadius: BorderRadius.circular(8)),
                                      child: Text('${n['category_name']}', style: const TextStyle(color: Color(0xFF2A0E4E), fontSize: 11, fontWeight: FontWeight.bold)),
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
                      color: active ? kGold : Colors.white24,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  );
                }),
              ),
            ],
          ],
        ),
      ),
    ),
    );
  }

  Widget _buildCategoryChip(String label, String type) {
    final isSelected = selectedType == type;
    return ChoiceChip(
      label: Text(label, style: TextStyle(color: isSelected ? const Color(0xFF2A0E4E) : Colors.white)),
      selected: isSelected,
      selectedColor: kGold,
      backgroundColor: kPurpleCard.withOpacity(0.6),
      side: BorderSide(color: kGold.withOpacity(0.6)),
      onSelected: (selected) {
        setState(() => selectedType = type);
        fetchData();
      },
    );
  }

  /// Wallet action buttons (Tarik / Top Up) inside the GrSaldo card.
  Widget _walletActionButton(IconData icon, String label) {
    return GestureDetector(
      onTap: () {
        final msg = label == 'Tarik'
            ? 'Fitur pencairan saldo akan segera hadir.'
            : 'Fitur top up saldo akan segera hadir.';
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: kGold.withOpacity(0.18),
              shape: BoxShape.circle,
              border: Border.all(color: kGold, width: 1.5),
            ),
            child: Icon(icon, color: kGoldBright, size: 26),
          ),
          const SizedBox(height: 6),
          Text(label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  /// Grid layanan favorit: Motor/Mobil menuju menu Kirim (point picker GPS).
  /// Back dari Kirim tidak exit aplikasi, melainkan kembali ke home awal.
  Widget _buildServicesGrid() {
    const services = <_ServiceItem>[
      _ServiceItem('Motor', Icons.motorcycle),
      _ServiceItem('Mobil', Icons.directions_car),
      _ServiceItem('Food', Icons.fastfood),
      _ServiceItem('Mart', Icons.storefront),
      _ServiceItem('Ambulance', Icons.local_hospital),
      _ServiceItem('Sayur Buah', Icons.eco),
      _ServiceItem('Bengkel Panggilan', Icons.build),
      _ServiceItem('Send', Icons.local_shipping),
      _ServiceItem('PPOB', Icons.public),
      _ServiceItem('Aneka Jasa', Icons.handyman),
      _ServiceItem('Agro', Icons.grass),
      _ServiceItem('Iklan Gratis', Icons.campaign),
    ];
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 4, mainAxisSpacing: 18, crossAxisSpacing: 12, childAspectRatio: 0.85),
      itemCount: services.length,
      itemBuilder: (context, index) {
        final s = services[index];
        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () => _openService(s),
          child: Column(
            children: [
              Container(
                width: 62,
                height: 62,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: kPurpleCard,
                  border: Border.all(color: kGold, width: 1.5),
                  boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.25), blurRadius: 6, offset: const Offset(0, 3))],
                ),
                child: Icon(s.icon, color: kGoldBright, size: 30),
              ),
              const SizedBox(height: 8),
              Text(s.label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold), textAlign: TextAlign.center, maxLines: 2),
            ],
          ),
        );
      },
    );
  }

  void _openService(_ServiceItem s) {
    switch (s.label) {
      case 'Motor':
      case 'Mobil':
      case 'Send':
        // Kirim (antar orang/paket) dengan point picker.
        _shellStateKey.currentState?.jumpTo(1);
        break;
      case 'Iklan Gratis':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const IklanGratisPage()));
        break;
      case 'Food':
      case 'Mart':
      case 'Sayur Buah':
        _shellStateKey.currentState?.jumpTo(2);
        break;
      default:
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${s.label} akan segera hadir di Gride SuperApp.')),
        );
    }
  }
}

/// Simple typed entry for the home services grid.
class _ServiceItem {
  final String label;
  final IconData icon;
  const _ServiceItem(this.label, this.icon);
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
  String _paymentMethod = 'CASH';

  bool _geocoding = false;
  bool _submitting = false;
  // Rute jalan raya untuk preview peta (garis rute + ETA)
  List<ll.LatLng> _routePoints = [];
  int? _routeDurationSec;
  bool _routeLoading = false;
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
      _geocoding = false;
    });
  }

  /// Jarak mengikuti jalan raya via OSRM (meter -> km), fallback haversine.
  /// overview=full supaya geometry rute lengkap bisa digambar di peta.
  Future<double> _osrmRoadKm(double lat1, double lon1, double lat2, double lon2) async {
    try {
      final res = await http.get(Uri.https('router.project-osrm.org',
          '/route/v1/driving/$lon1,$lat1;$lon2,$lat2', {'overview': 'full', 'geometries': 'geojson'}));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['code'] == 'Ok' && (data['routes'] as List).isNotEmpty) {
          final route = data['routes'][0];
          final meters = (route['legs'][0]['distance'] as num).toDouble();
          // Ambil geometry rute untuk digambar di peta (OSRM: [lon, lat] -> [lat, lon])
          final geom = route['geometry'];
          List<ll.LatLng> pts = [];
          if (geom != null && geom['coordinates'] != null) {
            pts = (geom['coordinates'] as List)
                .map((c) => ll.LatLng((c[1] as num).toDouble(), (c[0] as num).toDouble()))
                .toList();
          }
          final durationSec = (route['legs'][0]['duration'] as num).toInt();
          if (mounted) {
            setState(() {
              _routePoints = pts;
              _routeDurationSec = durationSec;
              _routeLoading = false;
            });
            // Geser peta supaya seluruh rute terlihat
            if (pts.length >= 2) {
              try {
                _mapCtrl.fitCamera(CameraFit.coordinates(coordinates: pts));
              } catch (_) {}
            }
          }
          return meters / 1000.0;
        }
      }
    } catch (_) {}
    if (mounted) {
      setState(() {
        _routePoints = [];
        _routeDurationSec = null;
        _routeLoading = false;
      });
    }
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

      setState(() => _routeLoading = true);
      _recalcEstimate();
    } catch (e) {
      setState(() {
        _geocoding = false;
        _routeLoading = false;
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
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          // Back dari Kirim tidak exit aplikasi, kembali ke home awal.
          _shellStateKey.currentState?.jumpTo(0);
        }
      },
      child: Scaffold(
      appBar: AppBar(
        title: const Text('GR-Kirim'),
        backgroundColor: kPurpleMain,
        foregroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () {
            _shellStateKey.currentState?.jumpTo(0);
            Navigator.of(context).pop();
          },
        ),
      ),
      body: Stack(
      children: [
        // ===== Peta full-screen dengan garis rute merah =====
        FlutterMap(
          mapController: _mapCtrl,
          options: MapOptions(
            initialCenter: ll.LatLng(
                _pickMode != null ? _pickLat : (_pickupLat ?? -7.2575),
                _pickMode != null ? _pickLng : (_pickupLng ?? 112.0178)),
            initialZoom: 13,
            onTap: (tapPos, point) {
              if (_pickMode == null) return; // mode peta aktif: ketuk untuk pilih titik
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
            // Garis rute merah mengikuti jalan raya
            if (_routePoints.length >= 2)
              PolylineLayer(
                polylines: [
                  Polyline(
                    points: _routePoints,
                    strokeWidth: 4.5,
                    color: Colors.redAccent.shade700,
                  ),
                ],
              ),
            MarkerLayer(
              markers: [
                if (_pickupLat != null)
                  Marker(
                    point: ll.LatLng(_pickupLat!, _pickupLng!),
                    width: 44,
                    height: 44,
                    child: Container(
                      decoration: const BoxDecoration(
                          color: Color(0xFF1E88E5), shape: BoxShape.circle,
                          boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 4)]),
                      child: const Icon(Icons.my_location, color: Colors.white, size: 26),
                    ),
                  ),
                if (_dropoffLat != null)
                  Marker(
                    point: ll.LatLng(_dropoffLat!, _dropoffLng!),
                    width: 44,
                    height: 44,
                    child: const Icon(Icons.location_on, color: Color(0xFFD32F2F), size: 42),
                  ),
                if (_pickMode != null)
                  Marker(
                    point: ll.LatLng(_pickLat, _pickLng),
                    width: 44,
                    height: 44,
                    child: Icon(Icons.location_pin,
                        color: _pickMode == 'pickup' ? Colors.green : Colors.deepPurple, size: 44),
                  ),
              ],
            ),
          ],
        ),

        // ===== Tombol saat memilih titik di peta =====
        if (_pickMode != null)
          Positioned(
            left: 16,
            right: 16,
            bottom: 190,
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 8, offset: const Offset(0, 3))],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 16,
                            backgroundColor: _pickMode == 'pickup' ? Colors.green : Colors.deepPurple,
                            child: const Icon(Icons.location_on, color: Colors.white, size: 18),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _pickMode == 'pickup' ? 'Titik Penjemputan' : 'Titik Tujuan',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _pickReverse
                            ? 'Mencari alamat...'
                            : (_pickAddress ?? 'Ketuk pada peta untuk memilih titik.'),
                        style: TextStyle(color: Colors.grey.shade700, fontSize: 12.5),
                        maxLines: 2,
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: FilledButton.tonalIcon(
                              onPressed: _pickUsingGps ? null : _useMyLocation,
                              icon: _pickUsingGps
                                  ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                                  : const Icon(Icons.my_location, size: 16),
                              label: const Text('GPS saya'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _cancelPick,
                              icon: const Icon(Icons.close, size: 16),
                              label: const Text('Batal'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: _pickReverse || _pickAddress == null ? null : _confirmPoint,
                              icon: const Icon(Icons.check, size: 16),
                              label: const Text('Pilih'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

        // ===== Kartu alamat melayang (atas) =====
        if (_pickMode == null)
          Positioned(
            top: 14,
            left: 16,
            right: 16,
            child: Container(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 10, offset: const Offset(0, 4))],
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Ikon pickup (biru) + connector + ikon tujuan (merah)
                  SizedBox(
                    width: 28,
                    child: Column(
                      children: [
                        Container(
                          width: 26,
                          height: 26,
                          decoration: const BoxDecoration(color: Color(0xFF1E88E5), shape: BoxShape.circle),
                          child: const Icon(Icons.near_me, color: Colors.white, size: 15),
                        ),
                        Expanded(
                          child: Container(
                            width: 2,
                            margin: const EdgeInsets.symmetric(vertical: 3),
                            color: Colors.grey.shade400,
                          ),
                        ),
                        Container(
                          width: 26,
                          height: 26,
                          decoration: const BoxDecoration(color: Color(0xFFD32F2F), shape: BoxShape.circle),
                          child: const Icon(Icons.location_on, color: Colors.white, size: 16),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  // Alamat
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        GestureDetector(
                          onTap: () => _openMapPicker('pickup'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: const BoxDecoration(
                              border: Border(bottom: BorderSide(color: Color(0xFFE5E7EB))),
                            ),
                            child: Text(
                              _pickupController.text.isEmpty ? 'Pilih lokasi penjemputan' : _pickupController.text,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w600,
                                color: _pickupController.text.isEmpty ? Colors.grey.shade600 : Colors.black87,
                              ),
                            ),
                          ),
                        ),
                        GestureDetector(
                          onTap: () => _openMapPicker('dropoff'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            child: Text(
                              _dropoffController.text.isEmpty ? 'Pilih lokasi tujuan' : _dropoffController.text,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.w600,
                                color: _dropoffController.text.isEmpty ? Colors.grey.shade600 : Colors.black87,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (_pickupLat != null || _dropoffLat != null)
                    IconButton(
                      icon: const Icon(Icons.close, color: Color(0xFF6B7280), size: 20),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      onPressed: () {
                        setState(() {
                          _pickupController.clear();
                          _dropoffController.clear();
                          _pickupLat = null;
                          _pickupLng = null;
                          _dropoffLat = null;
                          _dropoffLng = null;
                          _distanceKm = null;
                          _estimatedFee = null;
                          _routePoints = [];
                          _routeDurationSec = null;
                          _error = null;
                          _successOrder = null;
                        });
                      },
                    ),
                ],
              ),
            ),
          ),

        // ===== Bottom sheet: Payment / Price / Discount / Admin Fee / Total / ORDER =====
        Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          child: Container(
            constraints: const BoxConstraints(maxHeight: 470),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.only(
                  topLeft: Radius.circular(24), topRight: Radius.circular(24)),
              boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 12, offset: Offset(0, -4))],
            ),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 16),
              children: [
                // Drag handle
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(3)),
                  ),
                ),
                const SizedBox(height: 12),
                // Form singkat: penerima (nama + HP) + catatan
                Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _recipientController,
                              decoration: InputDecoration(
                                labelText: 'Nama Penerima',
                                labelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                floatingLabelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                                isDense: true,
                                contentPadding: const EdgeInsets.only(left: 12, right: 12, top: 18, bottom: 8),
                                filled: true,
                                fillColor: Colors.grey.shade50,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                                    borderSide: BorderSide.none),
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: TextFormField(
                              controller: _phoneController,
                              decoration: InputDecoration(
                                labelText: 'No. HP',
                                labelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                                floatingLabelBehavior: FloatingLabelBehavior.always,
                                floatingLabelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                                isDense: true,
                                contentPadding: const EdgeInsets.only(left: 12, right: 12, top: 18, bottom: 8),
                                filled: true,
                                fillColor: Colors.grey.shade50,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                                    borderSide: BorderSide.none),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _noteController,
                        decoration: InputDecoration(
                          labelText: 'Catatan (opsional)',
                          labelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                          floatingLabelBehavior: FloatingLabelBehavior.always,
                          floatingLabelStyle: const TextStyle(fontSize: 12, color: Colors.black87),
                          isDense: true,
                          contentPadding: const EdgeInsets.only(left: 12, right: 12, top: 18, bottom: 8),
                          filled: true,
                          fillColor: Colors.grey.shade50,
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                // Payment | Price — hanya tampil setelah rute dihitung
                if (_distanceKm != null && _estimatedFee != null) ...[
                Row(
                  children: [
                    const Text('Payment', style: TextStyle(fontSize: 14, color: Color(0xFF4B5563))),
                    const Spacer(),
                    Text('Price', style: TextStyle(fontSize: 14, color: Colors.grey.shade600)),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        height: 46,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _paymentMethod,
                            isExpanded: true,
                            icon: const Icon(Icons.keyboard_arrow_down),
                            items: const [
                              DropdownMenuItem(value: 'CASH', child: Text('Cash')),
                              DropdownMenuItem(value: 'GRSALDO', child: Text('GrSaldo')),
                            ],
                            onChanged: (v) => setState(() => _paymentMethod = v ?? 'CASH'),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          _estimatedFee != null ? formatRp(_estimatedFee! + kAdminFee) : formatRp(_baseFare + kAdminFee),
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF4B1D7E)),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _distanceKm != null ? '${_distanceKm!.toStringAsFixed(1)} Km' : '',
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Divider(color: Colors.grey.shade200),
                const SizedBox(height: 6),
                // Discount row
                Row(
                  children: [
                    Icon(Icons.confirmation_number_outlined,
                        color: Colors.purple.shade400, size: 19),
                    const SizedBox(width: 8),
                    const Text('Input Discount Code', style: TextStyle(fontWeight: FontWeight.w600)),
                    const Spacer(),
                    Text('Discount Code', style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
                  ],
                ),
                const SizedBox(height: 10),
                // Admin Fee
                Row(
                  children: [
                    Text('Admin Fee', style: TextStyle(color: Colors.grey.shade700, fontSize: 14)),
                    const Spacer(),
                    Text(formatRp(kAdminFee), style: const TextStyle(fontSize: 14)),
                  ],
                ),
                const SizedBox(height: 10),
                // Total
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('Total price', style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
                        Text(
                          _estimatedFee != null
                              ? formatRp(_estimatedFee! + kAdminFee)
                              : formatRp(_baseFare + kAdminFee),
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ],
                ),
                ],
                const SizedBox(height: 14),
                if (_error != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(10)),
                    child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 12.5)),
                  ),
                if (_successOrder != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)),
                    child: Text(_successOrder!, style: TextStyle(color: Colors.green.shade800, fontSize: 12.5)),
                  ),
                // Chat + ORDER
                Row(
                  children: [
                    Container(
                      width: 54,
                      height: 54,
                      decoration: BoxDecoration(
                        color: kPurpleMain,
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: IconButton(
                        icon: const Icon(Icons.chat_bubble_outline, color: Colors.white),
                        onPressed: () {
                          ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Chat dengan driver akan hadir sebentar lagi.')));
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: SizedBox(
                        height: 54,
                        child: FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: kPurpleMain,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          onPressed: _submitting ? null : _submitOrder,
                          child: _submitting
                              ? const SizedBox(
                                  width: 18, height: 18,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Text('ORDER', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
    ),
    ));
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
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          // Back dari Antar tidak exit aplikasi, kembali ke home awal.
          _shellStateKey.currentState?.jumpTo(0);
        }
      },
      child: Scaffold(
      appBar: AppBar(
        title: const Text('Antar - Pesan Makanan & Belanja'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () {
            _shellStateKey.currentState?.jumpTo(0);
            Navigator.of(context).pop();
          },
        ),
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
                                              (m['logo_url'] ?? '').toString().isNotEmpty ? m['logo_url'] : 'https://placehold.co/200x200/0d9488/ffffff?text=Gride',
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
    ));
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
  String _paymentMethod = 'CASH';
  bool _submitting = false;
  String? resultMsg;

  // Peta tujuan pengiriman (OSM gratis)
  final MapController _mapCtrl = MapController();
  double? _dropoffLat;
  double? _dropoffLng;
  bool _geocodingAddress = false;

  @override
  void initState() {
    super.initState();
    _loadMenu();
  }

  Future<void> _geocodeAddress() async {
    if (_address.trim().isEmpty) return;
    setState(() => _geocodingAddress = true);
    try {
      final res = await http.get(
        Uri.https('nominatim.openstreetmap.org', '/search', {
          'q': _address.trim(),
          'format': 'json',
          'limit': '1',
          'countrycodes': 'id',
        }),
        headers: {'User-Agent': 'gride-customer-app/1.0'},
      );
      final list = jsonDecode(res.body) as List;
      if (mounted && list.isNotEmpty) {
        final lat = double.parse(list[0]['lat'].toString());
        final lon = double.parse(list[0]['lon'].toString());
        setState(() {
          _dropoffLat = lat;
          _dropoffLng = lon;
        });
        _mapCtrl.move(ll.LatLng(lat, lon), 16);
      }
    } catch (_) {}
    if (mounted) setState(() => _geocodingAddress = false);
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
        backgroundColor: kPurpleMain,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: Stack(
        children: [
          Column(
            children: [
              // ===== Peta tujuan pengiriman (OSM gratis) =====
              Container(
                height: 260,
                clipBehavior: Clip.antiAlias,
                child: FlutterMap(
                  mapController: _mapCtrl,
                  options: MapOptions(
                    initialCenter: ll.LatLng(
                        _dropoffLat ?? (double.tryParse((widget.merchant['lat'] ?? '-7.8013').toString()) ?? -7.8013),
                        _dropoffLng ?? (double.tryParse((widget.merchant['lng'] ?? '112.0117').toString()) ?? 112.0117)),
                    initialZoom: 14,
                  ),
                  children: [
                    TileLayer(
                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.gride.app',
                      maxNativeZoom: 19,
                    ),
                    if (_dropoffLat != null)
                      MarkerLayer(
                        markers: [
                          Marker(
                            point: ll.LatLng(_dropoffLat!, _dropoffLng!),
                            width: 44,
                            height: 44,
                            child: const Icon(Icons.location_on, color: Color(0xFFD32F2F), size: 42),
                          ),
                        ],
                      ),
                  ],
                ),
              ),
              // ===== Daftar menu =====
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
                        : menu.isEmpty
                            ? const Center(child: Text('Menu kosong.'))
                            : ListView.builder(
                                padding: const EdgeInsets.fromLTRB(16, 12, 16, 300),
                                itemCount: menu.length,
                                itemBuilder: (context, index) {
                                  final item = menu[index];
                                  final qty = _cart[item['id']] ?? 0;
                                  final price = (double.tryParse(item['price']?.toString() ?? '0') ?? 0).round();
                                  return Card(
                                    margin: const EdgeInsets.only(bottom: 10),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                    child: ListTile(
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                      title: Text(item['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                                      subtitle: Text(item['description'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                                      trailing: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(formatRp(price), style: const TextStyle(color: kPurpleMain)),
                                          const SizedBox(width: 10),
                                          IconButton(
                                            icon: const Icon(Icons.remove_circle_outline),
                                            onPressed: qty <= 0
                                                ? null
                                                : () => setState(() => _cart[item['id']] = qty - 1),
                                          ),
                                          Text('$qty', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                                          IconButton(
                                            icon: const Icon(Icons.add_circle, color: kPurpleMain),
                                            onPressed: () => setState(() => _cart[item['id']] = qty + 1),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
              ),
            ],
          ),
          // ===== Bottom sheet: Payment / Price / Discount / Admin Fee / Total / ORDER =====
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              constraints: const BoxConstraints(maxHeight: 420),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(24), topRight: Radius.circular(24)),
                boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 12, offset: Offset(0, -4))],
              ),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 16),
                children: [
                  Center(
                    child: Container(
                      width: 44,
                      height: 5,
                      decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(3)),
                    ),
                  ),
                  const SizedBox(height: 10),
                  // Alamat pengiriman + tombol cari di peta
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: TextField(
                          controller: TextEditingController(text: _address),
                          maxLines: 2,
                          decoration: InputDecoration(
                            labelText: 'Alamat pengiriman',
                            hintText: 'e.g. Jl. Dharmawangsa, Kediri',
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            filled: true,
                            fillColor: Colors.grey.shade50,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none),
                            suffixIcon: _geocodingAddress
                                ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                                : null,
                          ),
                          onChanged: (v) {
                            _address = v;
                            if (_dropoffLat == null) return;
                            // Alamat berubah, hapus marker dulu; pencarian ulang via tombol
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        onPressed: _geocodingAddress ? null : _geocodeAddress,
                        icon: const Icon(Icons.search, color: kPurpleMain),
                        tooltip: 'Cari di peta',
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Payment | Price
                  Row(
                    children: [
                      const Text('Payment', style: TextStyle(fontSize: 14, color: Color(0xFF4B5563))),
                      const Spacer(),
                      Text('Price', style: TextStyle(fontSize: 14, color: Colors.grey.shade600)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          height: 46,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _paymentMethod,
                              isExpanded: true,
                              icon: const Icon(Icons.keyboard_arrow_down),
                              items: const [
                                DropdownMenuItem(value: 'CASH', child: Text('Cash')),
                                DropdownMenuItem(value: 'GRSALDO', child: Text('GrSaldo')),
                              ],
                              onChanged: (v) => setState(() => _paymentMethod = v ?? 'CASH'),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            formatRp(_subtotal() + kAdminFee),
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF4B1D7E)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Subtotal: ${formatRp(_subtotal())}',
                            style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Divider(color: Colors.grey.shade200),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Icon(Icons.confirmation_number_outlined,
                          color: Colors.purple.shade400, size: 19),
                      const SizedBox(width: 8),
                      const Text('Input Discount Code', style: TextStyle(fontWeight: FontWeight.w600)),
                      const Spacer(),
                      Text('Discount Code', style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Text('Admin Fee', style: TextStyle(color: Colors.grey.shade700, fontSize: 14)),
                      const Spacer(),
                      Text(formatRp(kAdminFee), style: const TextStyle(fontSize: 14)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('Total price', style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
                          Text(
                            formatRp(_subtotal() + kAdminFee),
                            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (resultMsg != null)
                    Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: (resultMsg!.contains('Pesanan') || resultMsg!.contains('Nomor'))
                            ? Colors.green.shade50
                            : Colors.red.shade50,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(resultMsg!,
                          style: TextStyle(
                            color: (resultMsg!.contains('Pesanan') || resultMsg!.contains('Nomor'))
                                ? Colors.green.shade800
                                : Colors.red.shade800,
                            fontSize: 12.5)),
                    ),
                  Row(
                    children: [
                      Container(
                        width: 54,
                        height: 54,
                        decoration: BoxDecoration(
                          color: kPurpleMain,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: IconButton(
                          icon: const Icon(Icons.chat_bubble_outline, color: Colors.white),
                          onPressed: () {
                            ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Chat dengan merchant akan hadir sebentar lagi.')));
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: SizedBox(
                          height: 54,
                          child: FilledButton(
                            style: FilledButton.styleFrom(
                              backgroundColor: kPurpleMain,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            ),
                            onPressed: _submitting ? null : _submitOrder,
                            child: _submitting
                                ? const SizedBox(
                                    width: 18, height: 18,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : const Text('ORDER', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
                          ),
                        ),
                      ),
                    ],
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

/// Akun: daftar / login / profil / riwayat pesanan.
class AkunPage extends StatefulWidget {
  const AkunPage({super.key});

  @override
  State<AkunPage> createState() => _AkunPageState();
}

class _AkunPageState extends State<AkunPage> {
  Map<String, dynamic>? _user;
  List _orders = [];
  Map<String, dynamic>? _wallet; // saldo GrSaldo
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
    if (u != null) {
      _loadOrders();
      _loadWallet();
    }
  }

  Future<void> _loadWallet() async {
    if (_user == null) return;
    try {
      final res = await http.get(Uri.parse('$kApiBase/wallets?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body)['data'] as List?;
        setState(() => _wallet = (list != null && list.isNotEmpty) ? Map<String, dynamic>.from(list.first as Map) : null);
      }
    } catch (_) {}
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
                  // GrSaldo wallet card
                  GestureDetector(
                    onTap: () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletPage()));
                    },
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF4B1D7E), Color(0xFF7B4DBF)],
                          begin: Alignment.topLeft, end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [BoxShadow(color: kPurpleMain.withOpacity(0.35), blurRadius: 12, offset: const Offset(0, 6))],
                      ),
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(children: const [
                            Icon(Icons.account_balance_wallet, color: Color(0xFFD9B24A), size: 22),
                            SizedBox(width: 10),
                            Text('GrSaldo', style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w700, letterSpacing: 0.5)),
                            Spacer(),
                            Icon(Icons.chevron_right, color: Colors.white54),
                          ]),
                          const SizedBox(height: 10),
                          Text(formatRp((int.tryParse((_wallet?['balance'] ?? '0').toString()) ?? 0)),
                              style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 4),
                          const Text('Saldo GrSaldo • ketuk untuk Top Up, Tarik Dana & Riwayat',
                              style: TextStyle(color: Colors.white70, fontSize: 12)),
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
                                (item['image_url'] ?? '').toString().isNotEmpty ? item['image_url'] : 'https://placehold.co/200x200/0d9488/ffffff?text=Produk',
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
            flexibleSpace: FlexibleSpaceBar(
              background: Image.network(
                imageUrl.isNotEmpty ? imageUrl : 'https://placehold.co/600x400/0d9488/ffffff?text=Gride+News',
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(color: Colors.teal.shade300),
              ),
            ),
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

/// Iklan Gratis: list iklan aktif, filter kategori, tambah & edit iklan sendiri.
/// Posting via POST /api/iklan-gratis, edit PUT /api/iklan-gratis/{id}.
/// Wajib login; expired 1-12 bulan (max 1 tahun); max 10 foto per iklan.
class IklanGratisPage extends StatefulWidget {
  const IklanGratisPage({super.key});

  @override
  State<IklanGratisPage> createState() => _IklanGratisPageState();
}

class _IklanGratisPageState extends State<IklanGratisPage> {
  final String baseUrl = '$kApiBase/iklan-gratis';
  List<Map<String, dynamic>> _iklan = [];
  List<Map<String, dynamic>> _categories = [];
  bool _loading = true;
  String? _error;
  String? _selectedCategory;
  String _search = '';
  int _page = 1;
  bool _hasMore = true;
  bool _moreLoading = false;
  Map<String, dynamic>? _sessionUser;
  int? _userId() {
    final u = _sessionUser;
    return u == null ? null : (u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString()));
  }

  @override
  void initState() {
    super.initState();
    Session.load().then((u) {
      setState(() => _sessionUser = u);
      _loadCategories();
      _loadIklan(append: false);
    });
  }

  Future<void> _loadCategories() async {
    try {
      final res = await http.get(Uri.parse('$kApiBase/iklan-gratis/categories'));
      if (res.statusCode == 200) {
        final list = (jsonDecode(res.body)['data'] as List).cast<Map<String, dynamic>>();
        setState(() => _categories = list);
      }
    } catch (_) {}
  }

  Future<void> _loadIklan({bool append = true}) async {
    if (_moreLoading) return;
    if (!append) {
      _page = 1;
      _hasMore = true;
      setState(() {
        _loading = true;
        _error = null;
        _iklan = [];
      });
    } else {
      setState(() => _moreLoading = true);
    }
    try {
      var url = '$baseUrl?page=$_page';
      if (_selectedCategory != null && _selectedCategory!.isNotEmpty) {
        url += '&category=$_selectedCategory';
      }
      if (_search.trim().isNotEmpty) {
        url += '&search=${Uri.encodeComponent(_search.trim())}';
      }
      final res = await http.get(Uri.parse(url));
      if (res.statusCode == 200) {
        final body = jsonDecode(res.body);
        final data = body['data'];
        // Handle paginator shapes: {data:{items:[...]}} atau {data:{data:[...]}}
        List<dynamic> rawItems = [];
        if (data is Map && data['items'] is List) {
          rawItems = data['items'] as List;
        } else if (data is Map && data['data'] is List) {
          rawItems = data['data'] as List;
        } else if (data is List) {
          rawItems = data;
        }
        final list = rawItems.map((e) => Map<String, dynamic>.from(e as Map)).toList();
        int lastPage = 1;
        if (data is Map) {
          final lp = data['last_page'];
          lastPage = lp is int ? lp : (int.tryParse(lp.toString()) ?? 1);
          if (lastPage <= 0) lastPage = 1;
        }
        setState(() {
          if (append) {
            _iklan.addAll(list);
          } else {
            _iklan = list;
          }
          _hasMore = _page < lastPage;
          _loading = false;
          _moreLoading = false;
          _error = null;
        });
      } else {
        setState(() {
          _loading = false;
          _moreLoading = false;
          _error = 'Gagal memuat iklan (HTTP ${res.statusCode}).';
        });
      }
    } catch (e) {
      setState(() {
        _loading = false;
        _moreLoading = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  String? _formatHarga(dynamic harga) {
    if (harga == null) return null;
    final v = double.tryParse(harga.toString());
    if (v == null || v <= 0) return null;
    return formatRp(v.round());
  }

  String _formatExpired(String? expired) {
    if (expired == null || expired.isEmpty) return '';
    String date = expired;
    if (date.contains(' ')) date = date.split(' ').first;
    return 's/d $date';
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          // Back dari Iklan Gratis kembali ke home awal, tidak exit aplikasi.
          _shellStateKey.currentState?.jumpTo(0);
        }
      },
      child: Scaffold(
      appBar: AppBar(
        title: const Text('Iklan Gratis'),
        backgroundColor: kPurpleMain,
        foregroundColor: kGoldBright,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios),
          onPressed: () {
            _shellStateKey.currentState?.jumpTo(0);
            Navigator.of(context).pop();
          },
        ),
      ),
      body: _loading && _iklan.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : _error != null && _iklan.isEmpty
              ? Center(
                  child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                    Text(_error!, style: TextStyle(color: Colors.red.shade800), textAlign: TextAlign.center),
                    const SizedBox(height: 10),
                    FilledButton(onPressed: () => _loadIklan(append: false), child: const Text('Coba lagi')),
                  ]),
                )
              : Column(
                  children: [
                    // Search + kategori
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              decoration: InputDecoration(
                                hintText: 'Cari iklan...',
                                prefixIcon: const Icon(Icons.search, color: kGold),
                                isDense: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              ),
                              onSubmitted: (v) {
                                setState(() => _search = v);
                                _loadIklan(append: false);
                              },
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            decoration: BoxDecoration(color: kPurpleCard, borderRadius: BorderRadius.circular(12)),
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                dropdownColor: kPurpleMain,
                                value: _selectedCategory?.isEmpty == true ? null : _selectedCategory,
                                hint: const Text('Kategori', style: TextStyle(color: kGoldBright)),
                                icon: const Icon(Icons.filter_list, color: kGoldBright),
                                onChanged: (v) {
                                  setState(() => _selectedCategory = v);
                                  _loadIklan(append: false);
                                },
                                items: [
                                  const DropdownMenuItem<String>(value: null, child: Text('Semua', style: TextStyle(color: kGoldBright))),
                                  ..._categories.map((c) => DropdownMenuItem<String>(
                                        value: c['name']?.toString(),
                                        child: Text(c['name']?.toString() ?? '',
                                            style: const TextStyle(color: kGoldBright), overflow: TextOverflow.ellipsis),
                                      )),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Tombol tambah iklan (wajib login)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                      child: SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _sessionUser == null
                              ? () {
                                  _shellStateKey.currentState?.jumpTo(2);
                                }
                              : () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                      builder: (_) => IklanFormPage(
                                            iklan: null,
                                            categories: _categories,
                                            onSaved: () => _loadIklan(append: false),
                                          )),
                                ),
                          icon: const Icon(Icons.add),
                          style: FilledButton.styleFrom(backgroundColor: kPurpleMain, foregroundColor: kGoldBright),
                          label: Text(_sessionUser == null ? 'Login untuk Pasang Iklan' : 'Pasang Iklan Gratis'),
                        ),
                      ),
                    ),
                    // List iklan
                    Expanded(
                      child: NotificationListener<ScrollNotification>(
                        onNotification: (notif) {
                          if (notif is ScrollEndNotification &&
                              notif.metrics.pixels >= notif.metrics.maxScrollExtent - 120 &&
                              !_moreLoading &&
                              _hasMore) {
                            _page += 1;
                            _loadIklan(append: true);
                          }
                          return false;
                        },
                        child: _iklan.isEmpty
                            ? const Center(child: Text('Belum ada iklan. Jadilah yang pertama pasang iklan!'))
                            : ListView.builder(
                                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                                itemCount: _iklan.length + (_hasMore ? 1 : 0),
                                itemBuilder: (context, index) {
                                  if (index >= _iklan.length) {
                                    return const Padding(
                                      padding: EdgeInsets.symmetric(vertical: 16),
                                      child: Center(child: CircularProgressIndicator()),
                                    );
                                  }
                                  final item = _iklan[index];
                                  final photos = (item['photos'] is List ? item['photos'] as List : []);
                                  final String? firstPhoto =
                                      photos.isNotEmpty ? photos.first.toString() : (item['photo_url']?.toString());
                                  final harga = _formatHarga(item['price']);
                                  final myIklan = _userId() != null &&
                                      (item['user_id'].toString() == _userId().toString());
                                  return GestureDetector(
                                    onTap: () => Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                          builder: (_) => IklanDetailPage(
                                                iklan: item,
                                                categories: _categories,
                                                onEditOwn: () => Navigator.push(
                                                  context,
                                                  MaterialPageRoute(
                                                      builder: (_) => IklanFormPage(
                                                            iklan: item,
                                                            categories: _categories,
                                                            onSaved: () {
                                                              Navigator.of(context).pop();
                                                              _loadIklan(append: false);
                                                            },
                                                          )),
                                                ),
                                              )),
                                    ),
                                    child: Card(
                                      margin: const EdgeInsets.only(bottom: 12),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                      elevation: 2,
                                      child: Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          ClipRRect(
                                            borderRadius: const BorderRadius.only(
                                                topLeft: Radius.circular(14), bottomLeft: Radius.circular(14)),
                                            child: firstPhoto != null && firstPhoto.isNotEmpty
                                                ? Image.network(firstPhoto,
                                                    width: 110, height: 110, fit: BoxFit.cover,
                                                    errorBuilder: (_, __, ___) => Container(
                                                        width: 110, height: 110, color: kPurpleCard,
                                                        child: const Icon(Icons.broken_image, color: kGoldBright)))
                                                : Container(
                                                    width: 110, height: 110, color: kPurpleCard,
                                                    child: const Icon(Icons.campaign, color: kGoldBright)),
                                          ),
                                          Expanded(
                                            child: Padding(
                                              padding: const EdgeInsets.all(12),
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(item['title']?.toString() ?? '',
                                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                                                      maxLines: 2, overflow: TextOverflow.ellipsis),
                                                  const SizedBox(height: 4),
                                                  Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                                    decoration: BoxDecoration(
                                                      color: kPurpleCard,
                                                      borderRadius: BorderRadius.circular(8),
                                                    ),
                                                    child: Text(item['category_name']?.toString() ?? '',
                                                        style: TextStyle(color: kGoldBright, fontSize: 11)),
                                                  ),
                                                  const SizedBox(height: 6),
                                                  Text((item['description'] ?? '').toString(),
                                                      maxLines: 2,
                                                      overflow: TextOverflow.ellipsis,
                                                      style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                                                  const SizedBox(height: 6),
                                                  Row(
                                                    children: [
                                                      if (harga != null)
                                                        Text(harga,
                                                            style: TextStyle(
                                                                color: kPurpleMain, fontWeight: FontWeight.bold)),
                                                      const Spacer(),
                                                      Text(_formatExpired(item['expired_date']?.toString()),
                                                          style: TextStyle(color: Colors.grey.shade500, fontSize: 11)),
                                                    ],
                                                  ),
                                                  if (myIklan) const SizedBox(height: 6),
                                                  if (myIklan)
                                                    TextButton.icon(
                                                      onPressed: () => Navigator.push(
                                                        context,
                                                        MaterialPageRoute(
                                                            builder: (_) => IklanFormPage(
                                                                  iklan: item,
                                                                  categories: _categories,
                                                                  onSaved: () => _loadIklan(append: false),
                                                                )),
                                                      ),
                                                      icon: const Icon(Icons.edit, size: 16),
                                                      label: const Text('Edit iklan saya', style: TextStyle(fontSize: 12)),
                                                    ),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                },
                              ),
                      ),
                    ),
                  ],
                ),
    ));
  }
}

/// Detail satu iklan: foto gallery, deskripsi, kontak penjual.
class IklanDetailPage extends StatefulWidget {
  final Map<String, dynamic> iklan;
  final List<Map<String, dynamic>> categories;
  final VoidCallback onEditOwn;
  const IklanDetailPage({super.key, required this.iklan, required this.categories, required this.onEditOwn});

  @override
  State<IklanDetailPage> createState() => _IklanDetailPageState();
}

class _IklanDetailPageState extends State<IklanDetailPage> {
  Map<String, dynamic>? _sessionUser;

  @override
  void initState() {
    super.initState();
    Session.load().then((u) => setState(() => _sessionUser = u));
  }

  int? _userId() {
    final u = _sessionUser;
    return u == null ? null : (u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString()));
  }

  @override
  Widget build(BuildContext context) {
    final i = widget.iklan;
    final photos = (i['photos'] is List ? (i['photos'] as List) : []).map((p) => p.toString()).toList();
    final String? firstPhoto =
        photos.isNotEmpty ? photos.first : (i['photo_url']?.toString());
    final String? harga = (() {
      final v = double.tryParse(i['price']?.toString() ?? '');
      return v == null || v <= 0 ? null : formatRp(v.round());
    })();
    String? expired = i['expired_date']?.toString();
    if (expired != null && expired.contains(' ')) expired = expired.split(' ').first;

    return Scaffold(
      backgroundColor: Colors.white,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: firstPhoto != null && firstPhoto.isNotEmpty ? 260 : 140,
            pinned: true,
            backgroundColor: kPurpleMain,
            foregroundColor: kGoldBright,
            flexibleSpace: FlexibleSpaceBar(
              background: firstPhoto != null && firstPhoto.isNotEmpty
                  ? Image.network(firstPhoto, fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(color: kPurpleCard,
                          child: const Icon(Icons.broken_image, color: kGoldBright, size: 48)))
                  : Container(color: kPurpleCard,
                      child: const Icon(Icons.campaign, color: kGoldBright, size: 48)),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: kPurpleCard, borderRadius: BorderRadius.circular(8)),
                        child: Text(i['category_name']?.toString() ?? '',
                            style: TextStyle(color: kGoldBright, fontSize: 12, fontWeight: FontWeight.bold)),
                      ),
                      const Spacer(),
                      if (expired != null) Text('Berlaku s/d $expired',
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(i['title']?.toString() ?? '',
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, height: 1.3)),
                  const SizedBox(height: 12),
                  if (harga != null)
                    Text(harga, style: TextStyle(color: kPurpleMain, fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  Text((i['description'] ?? '').toString(), style: const TextStyle(fontSize: 15, height: 1.6)),
                  const SizedBox(height: 18),
                  if (photos.length > 1) ...[
                    const Text('Foto lain', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    SizedBox(
                      height: 100,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: photos.length - 1,
                        separatorBuilder: (_, __) => const SizedBox(width: 8),
                        itemBuilder: (context, idx) => ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.network(photos[idx + 1], fit: BoxFit.cover, width: 100, height: 100,
                              errorBuilder: (_, __, ___) => Container(width: 100, height: 100, color: kPurpleCard,
                                  child: const Icon(Icons.broken_image, color: kGoldBright))),
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),
                  ],
                  Row(
                    children: [
                      const Icon(Icons.phone, color: kGold, size: 18),
                      const SizedBox(width: 8),
                      Text('Kontak: ${i['phone'] ?? '-'}', style: const TextStyle(fontSize: 14)),
                    ],
                  ),
                  const SizedBox(height: 20),
                  if (_userId() != null && i['user_id'].toString() == _userId().toString())
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: widget.onEditOwn,
                        icon: const Icon(Icons.edit),
                        label: const Text('Edit iklan saya'),
                        style: FilledButton.styleFrom(backgroundColor: kPurpleMain, foregroundColor: kGoldBright),
                      ),
                    ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Form tambah / edit iklan (wajib login).
/// Foto max 10; expired 1-12 bulan (maksimal 1 tahun posting).
class IklanFormPage extends StatefulWidget {
  final Map<String, dynamic>? iklan; // null = tambah baru
  final List<Map<String, dynamic>> categories;
  final VoidCallback onSaved;
  const IklanFormPage({super.key, this.iklan, required this.categories, required this.onSaved});

  @override
  State<IklanFormPage> createState() => _IklanFormPageState();
}

class _IklanFormPageState extends State<IklanFormPage> {
  final String baseUrl = '$kApiBase/iklan-gratis';
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  String? _categoryId;
  int _expiredMonths = 3;
  bool _submitting = false;
  String? _msg;
  final List<String> _photoUrls = [];
  Map<String, dynamic>? _sessionUser;

  bool get _isEdit => widget.iklan != null;

  int? _userId() {
    final u = _sessionUser;
    return u == null ? null : (u['id'] is int ? u['id'] as int : int.tryParse(u['id'].toString()));
  }

  @override
  void initState() {
    super.initState();
    Session.load().then((u) => setState(() => _sessionUser = u));
    if (_isEdit) {
      final i = widget.iklan!;
      _titleCtrl.text = i['title']?.toString() ?? '';
      _descCtrl.text = i['description']?.toString() ?? '';
      _priceCtrl.text = i['price']?.toString() ?? '';
      _phoneCtrl.text = i['phone']?.toString() ?? '';
      final cat = (widget.categories.firstWhere(
              (c) => c['id'].toString() == i['category_id']?.toString(),
              orElse: () => <String, dynamic>{})['id']);
      _categoryId = cat?.toString();
      _photoUrls.addAll((i['photos'] is List ? (i['photos'] as List) : []).map((p) => p.toString()));
    } else if (widget.categories.isNotEmpty) {
      _categoryId = widget.categories.first['id'].toString();
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _priceCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_titleCtrl.text.trim().isEmpty) {
      setState(() => _msg = 'Judul iklan wajib diisi.');
      return;
    }
    if (_descCtrl.text.trim().isEmpty) {
      setState(() => _msg = 'Deskripsi iklan wajib diisi.');
      return;
    }
    if (_categoryId == null) {
      setState(() => _msg = 'Pilih kategori iklan.');
      return;
    }
    final Map<String, dynamic> payload = {
      'title': _titleCtrl.text.trim(),
      'category_id': int.tryParse(_categoryId!) ?? 1,
      'description': _descCtrl.text.trim(),
      'price': _priceCtrl.text.trim().isEmpty ? null : _priceCtrl.text.trim(),
      'phone': _phoneCtrl.text.trim(),
      'expired_months': _expiredMonths,
      if (_photoUrls.isNotEmpty) 'photos': _photoUrls,
    };
    if (_isEdit) {
      payload['photos'] = _photoUrls;
    }
    setState(() {
      _submitting = true;
      _msg = null;
    });
    try {
      final uri = _isEdit ? Uri.parse('$baseUrl/${widget.iklan!['id']}') : Uri.parse(baseUrl);
      final res = await http
          .put(uri,
              headers: const {'Content-Type': 'application/json'},
              body: jsonEncode(payload))
          .timeout(const Duration(seconds: 30));
      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == 'success') {
        widget.onSaved();
      } else {
        setState(() {
          _submitting = false;
          _msg = data['message'] ?? 'Gagal menyimpan iklan.';
        });
      }
    } catch (e) {
      setState(() {
        _submitting = false;
        _msg = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final editing = _isEdit;
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(editing ? 'Edit Iklan Saya' : 'Pasang Iklan Gratis'),
        backgroundColor: kPurpleMain,
        foregroundColor: kGoldBright,
      ),
      body: _sessionUser == null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.lock, size: 48, color: kGold),
                    const SizedBox(height: 16),
                    const Text('Wajib login untuk memasang atau mengedit iklan.\nSilakan daftar/login di tab Akun.',
                        textAlign: TextAlign.center),
                    const SizedBox(height: 16),
                    FilledButton(
                        onPressed: () {
                          _shellStateKey.currentState?.jumpTo(2);
                          Navigator.of(context).pop();
                        },
                        child: const Text('Login / Daftar')),
                  ],
                ),
              ),
            )
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                TextField(
                  controller: _titleCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Judul iklan',
                    hintText: 'Misal: Jual Motor Honda Vario 2019',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.title),
                  ),
                ),
                const SizedBox(height: 14),
                DropdownButtonFormField<String>(
                  value: _categoryId,
                  decoration: const InputDecoration(
                    labelText: 'Kategori',
                    border: OutlineInputBorder(),
                  ),
                  items: widget.categories.map((c) {
                    return DropdownMenuItem(value: c['id'].toString(), child: Text(c['name']?.toString() ?? ''));
                  }).toList(),
                  onChanged: (v) => setState(() => _categoryId = v),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _descCtrl,
                  maxLines: 5,
                  decoration: const InputDecoration(
                    labelText: 'Deskripsi',
                    hintText: 'Jelaskan kondisi, lokasi, dan hal penting lainnya...',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _priceCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Harga (Rp, kosongkan jika nego)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.attach_money),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _phoneCtrl,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Nomor WhatsApp / HP',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.phone),
                  ),
                ),
                const SizedBox(height: 14),
                if (!editing) ...[
                  DropdownButtonFormField<int>(
                    value: _expiredMonths,
                    decoration: const InputDecoration(
                      labelText: 'Masa tayang iklan (1 - 12 bulan)',
                      border: OutlineInputBorder(),
                    ),
                    items: List.generate(12, (i) => i + 1)
                        .map((m) => DropdownMenuItem(value: m, child: Text('$m bulan')))
                        .toList(),
                    onChanged: (v) => setState(() => _expiredMonths = v ?? 3),
                  ),
                  const SizedBox(height: 14),
                ],
                if (editing)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Foto (masukkan URL gambar, maks 10)', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      ..._photoUrls.asMap().entries.map((e) => Row(
                            children: [
                              Expanded(child: Text(e.value, overflow: TextOverflow.ellipsis)),
                              IconButton(
                                icon: const Icon(Icons.delete, color: Colors.red),
                                onPressed: () => setState(() => _photoUrls.removeAt(e.key)),
                              ),
                            ],
                          )),
                      if (_photoUrls.length < 10)
                        TextButton.icon(
                          onPressed: () async {
                            final ctrl = TextEditingController();
                            final ok = await showDialog<bool>(
                              context: context,
                              builder: (ctx) => AlertDialog(
                                title: const Text('Tambah URL foto'),
                                content: TextField(
                                  controller: ctrl,
                                  keyboardType: TextInputType.url,
                                  decoration: const InputDecoration(hintText: 'https://...', border: OutlineInputBorder()),
                                ),
                                actions: [
                                  TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
                                  FilledButton(
                                      onPressed: () => Navigator.pop(ctx, true), child: const Text('Tambah')),
                                ],
                              ),
                            );
                            if (ok == true && ctrl.text.trim().isNotEmpty && _photoUrls.length < 10) {
                              setState(() => _photoUrls.add(ctrl.text.trim()));
                            }
                          },
                          icon: const Icon(Icons.add_photo_alternate),
                          label: Text('Tambah foto (${_photoUrls.length}/10)'),
                        ),
                      const SizedBox(height: 10),
                    ],
                  ),
                if (_msg != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Text(_msg!, style: TextStyle(color: Colors.red.shade800)),
                  ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _submitting ? null : _submit,
                    icon: _submitting
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.send),
                    label: Text(_submitting ? 'Menyimpan...' : (editing ? 'Perbarui Iklan' : 'Pasang Iklan (Gratis)')),
                  ),
                ),
              ],
            ),
    );
  }
}

/// =========================================================================
/// MODUL WALLET (GrSaldo) — saldo, top up, tarik dana, riwayat, detail,
/// kelola rekening bank, dan PIN wallet (6 digit, rate-limit server-side).
/// API: /api/wallets, /api/wallet/transactions, /api/wallet/topup,
/// /api/wallet/withdraw, /api/wallet/rekening, /api/wallet/pin/set & /verify.
/// =========================================================================

class WalletPage extends StatefulWidget {
  const WalletPage({super.key});
  @override
  State<WalletPage> createState() => _WalletPageState();
}

class _WalletPageState extends State<WalletPage> {
  Map<String, dynamic>? _user;
  Map<String, dynamic>? _wallet;
  List _transactions = [];
  bool _busy = false;
  bool _hideBalance = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  Future<void> _loadAll() async {
    _user = await Session.load();
    if (_user == null) return;
    await _loadWallet();
    await _loadTransactions();
  }

  Future<void> _loadWallet() async {
    try {
      final res = await http.get(Uri.parse('$kApiBase/wallets?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body)['data'] as List?;
        setState(() => _wallet = (list != null && list.isNotEmpty) ? Map<String, dynamic>.from(list.first as Map) : null);
      }
    } catch (e) {
      setState(() => _error = 'Koneksi gagal: $e');
    }
  }

  Future<void> _loadTransactions() async {
    try {
      final res = await http.get(Uri.parse('$kApiBase/wallet/transactions?user_id=${_user!['id']}&page=1'));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        final list = data is Map ? (data['items'] as List?) ?? [] : (data as List? ?? []);
        setState(() => _transactions = list.take(10).toList());
      }
    } catch (_) {}
  }

  int _balanceOf() => int.tryParse((_wallet?['balance'] ?? '0').toString()) ?? 0;

  IconData _iconFor(String type) {
    return {'TOPUP': Icons.add_circle_outline, 'WITHDRAW': Icons.remove_circle_outline, 'PAYMENT': Icons.shopping_bag_outlined, 'REFUND': Icons.replay_circle_filled, 'BONUS': Icons.card_giftcard}[type] ?? Icons.swap_horiz;
  }

  Color _colorFor(String type) {
    return {'TOPUP': Colors.green.shade700, 'WITHDRAW': kPurpleMain, 'PAYMENT': Colors.blue.shade800, 'REFUND': Colors.teal.shade700, 'BONUS': Colors.orange.shade800}[type] ?? Colors.grey.shade700;
  }

  String _labelFor(String type) {
    return {'TOPUP': 'Top Up', 'WITHDRAW': 'Tarik Dana', 'PAYMENT': 'Pembayaran', 'REFUND': 'Refund', 'BONUS': 'Bonus'}[type] ?? type;
  }

  Future<void> _openWithPinCheck(String route) async {
    // Setiap aksi keuangan wajib verifikasi PIN wallet di backend.
    final uid = _user!['id'];
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/pin/verify'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'user_id': uid, 'pin': 'check'}),
      );
      final body = jsonDecode(res.body);
      final pinSet = body['data'] != null && (body['data'] as Map)['pin_set'] == true;
      if (!mounted) return;
      if (!pinSet) {
        _showPinRequired();
        return;
      }
      _showPinDialog(route);
    } catch (_) {
      _showPinDialog(route);
    }
  }

  void _showPinRequired() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('PIN Wallet Belum Dibuat'),
        content: const Text('Untuk keamanan, buat PIN 6 digit terlebih dahulu sebelum menggunakan top up & tarik dana.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Nanti')),
          FilledButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletPinPage()));
            },
            child: const Text('Buat PIN'),
          ),
        ],
      ),
    );
  }

  void _showPinDialog(String route) {
    showDialog(
      context: context,
      builder: (ctx) => WalletPinDialog(
        uid: _user!['id'],
        onVerified: () {
          Navigator.pop(ctx);
          if (route == 'topup') {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletTopUpPage())).then((_) => _loadAll());
          } else if (route == 'withdraw') {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletWithdrawPage())).then((_) => _loadAll());
          }
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final balance = _balanceOf();
    return Scaffold(
      appBar: AppBar(title: const Text('GrSaldo'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: _user == null
          ? _loginPrompt()
          : RefreshIndicator(
              onRefresh: _loadAll,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Saldo utama + hide/show
                  Container(
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFF4B1D7E), Color(0xFF7B4DBF)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [BoxShadow(color: kPurpleMain.withOpacity(0.35), blurRadius: 14, offset: const Offset(0, 7))],
                    ),
                    padding: const EdgeInsets.all(22),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Saldo GrSaldo', style: TextStyle(color: Colors.white70, fontSize: 14)),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Text(_hideBalance ? 'Rp ••••••' : formatRp(balance),
                                      style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.bold)),
                                  const SizedBox(width: 12),
                                  GestureDetector(
                                    onTap: () => setState(() => _hideBalance = !_hideBalance),
                                    child: Icon(_hideBalance ? Icons.visibility_off : Icons.visibility, color: Colors.white70, size: 22),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              const Text('Pembayaran aman dengan PIN wallet 6 digit', style: TextStyle(color: Colors.white54, fontSize: 11)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Quick actions
                  Row(
                    children: [
                      Expanded(child: _quickAction(Icons.add_circle_outline, 'Top Up', () => _openWithPinCheck('topup'))),
                      const SizedBox(width: 10),
                      Expanded(child: _quickAction(Icons.remove_circle_outline, 'Tarik Dana', () => _openWithPinCheck('withdraw'))),
                      const SizedBox(width: 10),
                      Expanded(child: _quickAction(Icons.history, 'Riwayat', () => _openHistory())),
                      const SizedBox(width: 10),
                      Expanded(child: _quickAction(Icons.key, 'PIN', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletPinPage())).then((_) => _loadAll()))),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(children: [
                    Expanded(child: _quickAction(Icons.account_balance, 'Rekening', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletRekeningPage())).then((_) => _loadAll()))),
                  ]),
                  const SizedBox(height: 18),
                  Row(
                    children: const [Text('Transaksi Terakhir', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)), Spacer()],
                  ),
                  const SizedBox(height: 8),
                  if (_transactions.isEmpty) const Text('Belum ada transaksi.', style: TextStyle(color: Colors.grey)),
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _transactions.length,
                    itemBuilder: (context, i) {
                      final t = _transactions[i] as Map;
                      final amount = int.tryParse((t['amount'] ?? '0').toString()) ?? 0;
                      final positive = t['type'] == 'TOPUP' || t['type'] == 'REFUND' || t['type'] == 'BONUS';
                      return Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          leading: CircleAvatar(radius: 22, backgroundColor: _colorFor(t['type'] ?? '').withOpacity(0.12),
                              child: Icon(_iconFor(t['type'] ?? ''), color: _colorFor(t['type'] ?? ''))),
                          title: Text(_labelFor(t['type'] ?? ''), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                          subtitle: Text('${t['status'] ?? ''} • ${(t['created_at']?.toString() ?? '').substring(0, 16)}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                          trailing: Text(
                            '${positive ? '+' : '-'}${formatRp(amount)}',
                            style: TextStyle(color: positive ? Colors.green.shade700 : kPurpleMain, fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => WalletTransactionDetailPage(uid: _user!['id'], transaction: t))).then((_) => _loadAll()),
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 6),
                  if (_transactions.isNotEmpty)
                    TextButton.icon(
                      onPressed: _openHistory,
                      icon: const Icon(Icons.history, size: 16),
                      label: const Text('Lihat Semua Riwayat'),
                    ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
    );
  }

  Widget _loginPrompt() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.account_balance_wallet_outlined, size: 56, color: Colors.grey),
            const SizedBox(height: 16),
            const Text('Login dulu untuk melihat GrSaldo kamu', textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () {
                _shellStateKey.currentState?.jumpTo(2);
                Navigator.pop(context);
              },
              child: const Text('Login / Daftar'),
            ),
          ],
        ),
      ),
    );
  }

  void _openHistory() {
    Navigator.push(context, MaterialPageRoute(builder: (_) => WalletHistoryPage(uid: _user!['id']))).then((_) => _loadAll());
  }

  Widget _quickAction(IconData icon, String label, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 6),
        decoration: BoxDecoration(
          color: kPurpleCard.withOpacity(0.55),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: kPurpleCard.withOpacity(0.6)),
        ),
        child: Column(children: [Icon(icon, color: kPurpleMain, size: 26), const SizedBox(height: 6), Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600))]),
      ),
    );
  }
}

/// Dialog verifikasi PIN wallet sebelum aksi keuangan.
class WalletPinDialog extends StatefulWidget {
  final int uid;
  final VoidCallback onVerified;
  const WalletPinDialog({super.key, required this.uid, required this.onVerified});
  @override
  State<WalletPinDialog> createState() => _WalletPinDialogState();
}

class _WalletPinDialogState extends State<WalletPinDialog> {
  final List<String> _digits = [];
  bool _busy = false;
  String? _error;

  void _append(String d) {
    if (_busy || _digits.length >= 6) return;
    setState(() => _digits.add(d));
    if (_digits.length == 6) _verify();
  }

  Future<void> _verify() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/pin/verify'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'user_id': widget.uid, 'pin': _digits.join()}),
      );
      final body = jsonDecode(res.body);
      if (!mounted) return;
      if (res.statusCode == 200 && body['data']?['valid'] == true) {
        widget.onVerified();
      } else {
        setState(() {
          _busy = false;
          _error = body['message'] ?? 'PIN salah.';
          _digits.clear();
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _error = 'Koneksi gagal: $e';
        _digits.clear();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Verifikasi PIN', style: TextStyle(fontWeight: FontWeight.bold)),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('Masukkan PIN 6 digit wallet kamu', style: TextStyle(color: Colors.grey, fontSize: 13)),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(6, (i) {
              final filled = i < _digits.length;
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: 13, height: 13,
                decoration: BoxDecoration(shape: BoxShape.circle, color: filled ? kPurpleMain : Colors.grey.shade300),
              );
            }),
          ),
          if (_error != null)
            Padding(padding: const EdgeInsets.only(top: 10), child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 12))),
          const SizedBox(height: 12),
          for (var row in ['123', '456', '789', '⌫0'])
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 2),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: row.split('').map((c) {
                  return Container(
                    margin: const EdgeInsets.symmetric(horizontal: 5),
                    child: IconButton(
                      style: IconButton.styleFrom(minimumSize: const Size(56, 56), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)), backgroundColor: Colors.grey.shade200),
                      onPressed: _busy ? null : () {
                        if (c == '⌫') {
                          if (_digits.isNotEmpty) setState(() { _digits.removeLast(); _error = null; });
                        } else {
                          _append(c);
                        }
                      },
                      icon: c == '⌫' ? const Icon(Icons.backspace_outlined, color: Colors.black87) : Text(c, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600)),
                    ),
                  );
                }).toList(),
              ),
            ),
        ],
      ),
    );
  }
}

/// Riwayat transaksi dengan filter tipe & rentang tanggal.
class WalletHistoryPage extends StatefulWidget {
  final int uid;
  const WalletHistoryPage({super.key, required this.uid});
  @override
  State<WalletHistoryPage> createState() => _WalletHistoryPageState();
}

class _WalletHistoryPageState extends State<WalletHistoryPage> {
  List _transactions = [];
  bool _busy = false;
  String _filter = 'ALL';
  String? _from;
  String? _to;
  int _page = 1;
  int _lastPage = 1;
  int _total = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _busy = true);
    try {
      final q = {'user_id': '${widget.uid}', 'type': _filter, 'page': '$_page'};
      if (_from != null) q['from'] = _from!;
      if (_to != null) q['to'] = _to!;
      final res = await httpGetWithRetry('$kApiBase/wallet/transactions?${Uri(queryParameters: q).query}');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        final list = data is Map ? (data['items'] as List?) ?? [] : (data as List? ?? []);
        setState(() {
          _transactions = (_page == 1 ? [] : _transactions)..addAll(list);
          _page = data is Map ? (data['current_page'] ?? 1) : _page;
          _lastPage = data is Map ? (data['last_page'] ?? 1) : 1;
          _total = data is Map ? (data['total'] ?? 0) : list.length;
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _pickDate(bool isFrom) async {
    final d = await showDatePicker(context: context, initialDate: DateTime.now(), firstDate: DateTime(2020), lastDate: DateTime(2040));
    if (d != null) {
      final s = '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
      setState(() {
        if (isFrom) _from = s; else _to = s;
        _page = 1;
      });
      _load();
    }
  }

  IconData _iconFor(String type) {
    return {'TOPUP': Icons.add_circle_outline, 'WITHDRAW': Icons.remove_circle_outline, 'PAYMENT': Icons.shopping_bag_outlined, 'REFUND': Icons.replay_circle_filled, 'BONUS': Icons.card_giftcard}[type] ?? Icons.swap_horiz;
  }

  Color _colorFor(String type) {
    return {'TOPUP': Colors.green.shade700, 'WITHDRAW': kPurpleMain, 'PAYMENT': Colors.blue.shade800, 'REFUND': Colors.teal.shade700, 'BONUS': Colors.orange.shade800}[type] ?? Colors.grey.shade700;
  }

  String _labelFor(String type) {
    return {'TOPUP': 'Top Up', 'WITHDRAW': 'Tarik Dana', 'PAYMENT': 'Pembayaran', 'REFUND': 'Refund', 'BONUS': 'Bonus'}[type] ?? type;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Riwayat Transaksi'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Column(children: [
              SizedBox(
                height: 34,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: ['ALL', 'TOPUP', 'WITHDRAW', 'PAYMENT', 'REFUND', 'BONUS'].map((f) {
                    final active = _filter == f;
                    return GestureDetector(
                      onTap: () {
                        setState(() { _filter = f; _page = 1; _transactions = []; });
                        _load();
                      },
                      child: Container(
                        margin: const EdgeInsets.only(right: 8),
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        decoration: BoxDecoration(
                          color: active ? kPurpleMain : Colors.white,
                          border: Border.all(color: kPurpleMain.withOpacity(0.5)),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        alignment: Alignment.center,
                        child: Text(_labelFor(f), style: TextStyle(color: active ? Colors.white : kPurpleMain, fontSize: 12, fontWeight: FontWeight.w600)),
                      ),
                    );
                  }).toList(),
                ),
              ),
              const SizedBox(height: 8),
              Row(children: [
                Expanded(child: OutlinedButton.icon(onPressed: () => _pickDate(true), icon: const Icon(Icons.date_range, size: 16), label: Text(_from ?? 'Dari tanggal', style: const TextStyle(fontSize: 12)))),
                const SizedBox(width: 8),
                Expanded(child: OutlinedButton.icon(onPressed: () => _pickDate(false), icon: const Icon(Icons.date_range, size: 16), label: Text(_to ?? 'Sampai tanggal', style: const TextStyle(fontSize: 12)))),
                const SizedBox(width: 8),
                IconButton(icon: const Icon(Icons.refresh, size: 18), onPressed: () {
                  setState(() { _page = 1; _transactions = []; });
                  _load();
                }),
              ]),
            ]),
          ),
          const Divider(height: 1),
          Expanded(
            child: _transactions.isEmpty && !_busy
                ? Center(child: Text('Tidak ada transaksi${_filter != 'ALL' ? ' untuk filter ini' : ''}.', style: const TextStyle(color: Colors.grey)))
                : ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: _transactions.length + (_page < _lastPage ? 1 : 0),
                    itemBuilder: (context, i) {
                      if (i == _transactions.length) {
                        return Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Center(child: _busy ? const CircularProgressIndicator() : FilledButton(onPressed: () => _load(), child: const Text('Muat Lebih Banyak'))),
                        );
                      }
                      final t = _transactions[i] as Map;
                      final amount = int.tryParse((t['amount'] ?? '0').toString()) ?? 0;
                      final positive = t['type'] == 'TOPUP' || t['type'] == 'REFUND' || t['type'] == 'BONUS';
                      return Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          leading: CircleAvatar(radius: 22, backgroundColor: _colorFor(t['type'] ?? '').withOpacity(0.12), child: Icon(_iconFor(t['type'] ?? ''), color: _colorFor(t['type'] ?? ''))),
                          title: Text(_labelFor(t['type'] ?? ''), style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                          subtitle: Text('${t['status'] ?? ''} • ${(t['created_at']?.toString() ?? '').substring(0, 16)}', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                          trailing: Text('${positive ? '+' : '-'}${formatRp(amount)}', style: TextStyle(color: positive ? Colors.green.shade700 : kPurpleMain, fontWeight: FontWeight.bold, fontSize: 14)),
                          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => WalletTransactionDetailPage(uid: widget.uid, transaction: t))),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

/// Detail satu transaksi.
class WalletTransactionDetailPage extends StatelessWidget {
  final int uid;
  final Map transaction;
  const WalletTransactionDetailPage({super.key, required this.uid, required this.transaction});

  IconData _iconFor(String type) {
    return {'TOPUP': Icons.add_circle_outline, 'WITHDRAW': Icons.remove_circle_outline, 'PAYMENT': Icons.shopping_bag_outlined, 'REFUND': Icons.replay_circle_filled, 'BONUS': Icons.card_giftcard}[type] ?? Icons.swap_horiz;
  }

  String _labelFor(String type) {
    return {'TOPUP': 'Top Up', 'WITHDRAW': 'Tarik Dana', 'PAYMENT': 'Pembayaran', 'REFUND': 'Refund', 'BONUS': 'Bonus'}[type] ?? type;
  }

  @override
  Widget build(BuildContext context) {
    final t = transaction;
    final amount = int.tryParse((t['amount'] ?? '0').toString()) ?? 0;
    final positive = t['type'] == 'TOPUP' || t['type'] == 'REFUND' || t['type'] == 'BONUS';
    final failed = (t['status'] ?? '').toUpperCase() == 'FAILED';
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Transaksi'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const SizedBox(height: 8),
          CircleAvatar(radius: 34, backgroundColor: _iconColor(t['type'] ?? '').withOpacity(0.12), child: Icon(_iconFor(t['type'] ?? ''), color: _iconColor(t['type'] ?? ''), size: 34)),
          const SizedBox(height: 12),
          Text('${positive ? '+' : '-'}${formatRp(amount)}', style: TextStyle(fontSize: 30, fontWeight: FontWeight.bold, color: positive ? Colors.green.shade700 : kPurpleMain)),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
            decoration: BoxDecoration(
              color: failed ? Colors.red.shade50 : Colors.green.shade50,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: failed ? Colors.red.shade300 : Colors.green.shade300),
            ),
            child: Text((t['status'] ?? '').toUpperCase(), style: TextStyle(color: failed ? Colors.red.shade800 : Colors.green.shade800, fontWeight: FontWeight.w700, fontSize: 13)),
          ),
          const SizedBox(height: 22),
          _row('Jenis', _labelFor(t['type'] ?? '')),
          _row('Nominal', formatRp(amount)),
          _row('Saldo Sebelum', formatRp(int.tryParse((t['balance_before'] ?? '0').toString()) ?? 0)),
          _row('Saldo Setelah', formatRp(int.tryParse((t['balance_after'] ?? '0').toString()) ?? 0)),
          if (t['method'] != null) _row('Metode', (t['method'] ?? '').replaceAll('_', ' ')),
          if (t['reference_no'] != null) _rowWithCopy('Nomor Referensi', t['reference_no'].toString(), context),
          if (t['created_at'] != null) _row('Waktu', t['created_at'].toString().substring(0, 19)),
          if (t['description'] != null) _row('Keterangan', t['description'].toString()),
          if (failed && t['failure_reason'] != null)
            Container(
              margin: const EdgeInsets.only(top: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.red.shade200)),
              child: Text('Alasan gagal: ${t['failure_reason']}', style: TextStyle(color: Colors.red.shade800, fontSize: 13)),
            ),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => _WalletHelpPage(uid: uid, transaction: t)));
              },
              icon: const Icon(Icons.help_outline),
              label: const Text('Butuh Bantuan?'),
            ),
          ),
        ],
      ),
    );
  }

  Color _iconColor(String type) {
    return {'TOPUP': Colors.green.shade700, 'WITHDRAW': kPurpleMain, 'PAYMENT': Colors.blue.shade800, 'REFUND': Colors.teal.shade700, 'BONUS': Colors.orange.shade800}[type] ?? Colors.grey.shade700;
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(children: [Expanded(child: Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13))), Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14))]),
    );
  }

  Widget _rowWithCopy(String label, String value, BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(children: [
        Expanded(child: Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13))),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
        const SizedBox(width: 6),
        GestureDetector(
          onTap: () => Clipboard.setData(ClipboardData(text: value)).then((_) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor disalin'), duration: Duration(seconds: 1)));
          }),
          child: const Icon(Icons.copy, size: 16, color: Colors.grey),
        ),
      ]),
    );
  }
}

/// Halaman bantuan / laporan issue transaksi.
class _WalletHelpPage extends StatelessWidget {
  final int uid;
  final Map transaction;
  const _WalletHelpPage({required this.uid, required this.transaction});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Butuh Bantuan?'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text('Hubungi customer service Gride untuk kendala transaksi ini.', style: TextStyle(color: Colors.grey)),
          const SizedBox(height: 14),
          _row('Nomor Referensi', (transaction['reference_no'] ?? '-').toString()),
          _row('Status', (transaction['status'] ?? '-').toString()),
          const SizedBox(height: 16),
          const Text('Cara menghubungi:', style: TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          ListTile(leading: const Icon(Icons.chat_bubble_outline), title: const Text('Chat Customer Service'), subtitle: const Text('Menu Admin > Chat di aplikasi')),
          ListTile(leading: const Icon(Icons.phone_outlined), title: const Text('Telepon CS Gride'), subtitle: const Text('0800-1-GRIDE (0800-1-47433)')),
          ListTile(leading: const Icon(Icons.email_outlined), title: const Text('Email'), subtitle: const Text('cs@gride.web.id')),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Row(children: [Text('$label: ', style: const TextStyle(color: Colors.grey)), Text(value, style: const TextStyle(fontWeight: FontWeight.w600))]));
  }
}

/// Top Up: nominal + metode + konfirmasi. Status halaman langsung sukses (simulasi pembayaran manual).
class WalletTopUpPage extends StatefulWidget {
  const WalletTopUpPage({super.key});
  @override
  State<WalletTopUpPage> createState() => _WalletTopUpPageState();
}

class _WalletTopUpPageState extends State<WalletTopUpPage> {
  Map<String, dynamic>? _user;
  final _amountCtrl = TextEditingController();
  String _method = 'VA_BANK';
  bool _busy = false;
  String? _error;
  String? _successRef;

  static const List<int> _chips = [50000, 100000, 200000, 500000];

  @override
  void initState() {
    super.initState();
    Session.load().then((u) => setState(() => _user = u));
  }

  Future<void> _submit() async {
    final amount = int.tryParse(_amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    if (amount < 10000) {
      setState(() => _error = 'Minimum top up Rp 10.000');
      return;
    }
    if (amount > 10000000) {
      setState(() => _error = 'Maksimum top up Rp 10.000.000 per transaksi');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _successRef = null;
    });
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/topup'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': _user!['id'],
          'amount': amount,
          'method': _method,
          'idempotency_key': 'topup-${_user!['id']}-${DateTime.now().millisecondsSinceEpoch}',
        }),
      );
      final body = jsonDecode(res.body);
      if (!mounted) return;
      if (res.statusCode == 200 || res.statusCode == 201) {
        setState(() {
          _busy = false;
          _successRef = body['data']?['reference_no']?.toString();
        });
      } else {
        setState(() {
          _busy = false;
          _error = body['message'] ?? 'Top up gagal.';
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Top Up GrSaldo'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          if (_successRef != null)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(14), border: Border.all(color: Colors.green.shade300)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [Icon(Icons.check_circle, color: Colors.green.shade700), const SizedBox(width: 10), Text('Top Up Berhasil!', style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.bold, fontSize: 16))]),
                const SizedBox(height: 8),
                Text('Nomor referensi: $_successRef', style: const TextStyle(fontSize: 13)),
                const SizedBox(height: 10),
                TextButton(onPressed: () => Navigator.pop(context), child: const Text('Kembali ke GrSaldo')),
              ]),
            ),
          const Text('Nominal Top Up', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          TextField(
            controller: _amountCtrl,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(
              hintText: 'Masukkan nominal (min. Rp 10.000)',
              prefixText: 'Rp ',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.payments_outlined),
            ),
            onChanged: (_) => setState(() => _error = null),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _chips.map((c) {
              return ActionChip(
                label: Text(formatRp(c)),
                backgroundColor: kPurpleCard.withOpacity(0.4),
                onPressed: () => setState(() { _amountCtrl.text = c.toString(); _error = null; }),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),
          const Text('Metode Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          for (final m in const [
            ['VA_BANK', 'Virtual Account Bank'],
            ['EWALLET', 'E-Wallet (OVO, DANA, GoPay)'],
            ['QRIS', 'QRIS'],
            ['CARD', 'Kartu Debit / Kredit'],
          ])
            RadioListTile<String>(
              value: m[0],
              groupValue: _method,
              activeColor: kPurpleMain,
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: Text(m[1], style: const TextStyle(fontSize: 14)),
              onChanged: (v) => setState(() => _method = v!),
            ),
          const SizedBox(height: 16),
          if (_error != null)
            Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 13))),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: _busy ? null : (_successRef != null ? null : _submit),
              icon: _busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.check_circle_outline),
              label: const Text('Konfirmasi Top Up'),
            ),
          ),
          const SizedBox(height: 12),
          const Text('Pembayaran diproses langsung (simulasi). Untuk produksi akan diarahkan ke payment gateway.', style: TextStyle(color: Colors.grey, fontSize: 11)),
        ],
      ),
    );
  }
}

/// Tarik Dana: nominal, rekening tujuan, verifikasi PIN, submit.
class WalletWithdrawPage extends StatefulWidget {
  const WalletWithdrawPage({super.key});
  @override
  State<WalletWithdrawPage> createState() => _WalletWithdrawPageState();
}

class _WalletWithdrawPageState extends State<WalletWithdrawPage> {
  Map<String, dynamic>? _user;
  Map<String, dynamic>? _wallet;
  List _rekening = [];
  final _amountCtrl = TextEditingController();
  Map? _selectedRek;
  bool _busy = false;
  String? _error;
  String? _successRef;
  String? _pin;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    _user = await Session.load();
    if (_user == null) return;
    try {
      final wr = await httpGetWithRetry('$kApiBase/wallets?user_id=${_user!['id']}');
      if (wr.statusCode == 200) {
        final list = jsonDecode(wr.body)['data'] as List?;
        setState(() => _wallet = (list != null && list.isNotEmpty) ? Map<String, dynamic>.from(list.first as Map) : null);
      }
      final rr = await httpGetWithRetry('$kApiBase/wallet/rekening?user_id=${_user!['id']}');
      if (rr.statusCode == 200) {
        setState(() => _rekening = jsonDecode(rr.body)['data'] as List? ?? []);
      }
    } catch (_) {}
  }

  int _balanceOf() => int.tryParse((_wallet?['balance'] ?? '0').toString()) ?? 0;

  Future<void> _requestPinDialog() async {
    final res = await showDialog<Map>(
      context: context,
      builder: (ctx) => WalletPinDialog(
        uid: _user!['id'],
        onVerified: () {
          Navigator.pop(ctx, <String, dynamic>{'ok': true});
        },
      ),
    );
    if (res == null) return;
    _submit();
  }

  Future<void> _submit() async {
    final amount = int.tryParse(_amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    if (amount < 25000) {
      setState(() => _error = 'Minimum penarikan Rp 25.000');
      return;
    }
    if (amount > 5000000) {
      setState(() => _error = 'Maksimum penarikan Rp 5.000.000 per transaksi');
      return;
    }
    if (_selectedRek == null) {
      setState(() => _error = 'Pilih rekening tujuan terlebih dahulu.');
      return;
    }
    if (amount > _balanceOf()) {
      setState(() => _error = 'Saldo tidak cukup untuk penarikan ini.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _successRef = null;
    });
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/withdraw'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': _user!['id'],
          'amount': amount,
          'rekening_id': _selectedRek!['id'],
          'pin': _pin!,
          'idempotency_key': 'wd-${_user!['id']}-${DateTime.now().millisecondsSinceEpoch}',
        }),
      );
      final body = jsonDecode(res.body);
      if (!mounted) return;
      if (res.statusCode == 200 || res.statusCode == 201) {
        setState(() {
          _busy = false;
          _successRef = body['data']?['reference_no']?.toString();
        });
      } else {
        setState(() {
          _busy = false;
          _error = body['message'] ?? 'Penarikan gagal.';
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final balance = _balanceOf();
    return Scaffold(
      appBar: AppBar(title: const Text('Tarik Dana'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: kPurpleCard.withOpacity(0.4), borderRadius: BorderRadius.circular(12)),
            child: Row(children: [
              const Icon(Icons.account_balance_wallet_outlined, color: kPurpleMain),
              const SizedBox(width: 12),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('Saldo tersedia', style: TextStyle(color: Colors.grey, fontSize: 12)),
                Text(formatRp(balance), style: TextStyle(color: kPurpleMain, fontWeight: FontWeight.bold, fontSize: 17)),
              ])),
            ]),
          ),
          const SizedBox(height: 16),
          if (_successRef != null)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(14), border: Border.all(color: Colors.green.shade300)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [Icon(Icons.check_circle, color: Colors.green.shade700), const SizedBox(width: 10), Text('Penarikan Berhasil Diproses!', style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.bold, fontSize: 16))]),
                const SizedBox(height: 8),
                Text('Nomor referensi: $_successRef\nDana akan ditransfer ke rekening tujuan kamu.', style: const TextStyle(fontSize: 13)),
                const SizedBox(height: 10),
                TextButton(onPressed: () => Navigator.pop(context), child: const Text('Kembali ke GrSaldo')),
              ]),
            ),
          const Text('Nominal Penarikan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          const SizedBox(height: 10),
          TextField(
            controller: _amountCtrl,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(
              hintText: 'Min. Rp 25.000 • Maks. Rp 5.000.000',
              prefixText: 'Rp ',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.payments_outlined),
            ),
            onChanged: (_) => setState(() => _error = null),
          ),
          const SizedBox(height: 16),
          Row(children: const [Text('Rekening Tujuan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)), Spacer()]),
          const SizedBox(height: 10),
          if (_rekening.isEmpty)
            const Text('Belum ada rekening tersimpan. Tambahkan di menu Rekening.', style: TextStyle(color: Colors.grey, fontSize: 13))
          else
            for (final r in _rekening)
              RadioListTile<Map>(
                value: r as Map,
                groupValue: _selectedRek,
                activeColor: kPurpleMain,
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text('${r['bank_name']} ${(r['account_number'] ?? '')}', style: const TextStyle(fontSize: 14)),
                subtitle: Text('a.n. ${(r['account_holder'] ?? '')}${r['is_default'] == true ? ' • Rekening utama' : ''}', style: const TextStyle(fontSize: 12)),
                onChanged: (v) => setState(() { _selectedRek = v; _error = null; }),
              ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () async {
                final r = await Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletRekeningPage()));
                if (r == true) _load();
              },
              icon: const Icon(Icons.add),
              label: const Text('Tambah Rekening Baru'),
            ),
          ),
          const SizedBox(height: 16),
          if (_error != null)
            Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 13))),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: _busy ? null : (_successRef != null ? null : (_selectedRek == null || (_amountCtrl.text.replaceAll(RegExp(r'[^0-9]'), '').isEmpty) ? () => _submit() : () => _requestPinDialog())),
              icon: _busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send_outlined),
              label: const Text('Konfirmasi Penarikan'),
            ),
          ),
          const SizedBox(height: 12),
          const Text('Penarikan perlu verifikasi PIN wallet. Validasi saldo dilakukan di server untuk mencegah duplikasi.', style: TextStyle(color: Colors.grey, fontSize: 11)),
        ],
      ),
    );
  }
}

/// Kelola rekening bank tersimpan (tambah, edit default, hapus).
class WalletRekeningPage extends StatefulWidget {
  const WalletRekeningPage({super.key});
  @override
  State<WalletRekeningPage> createState() => _WalletRekeningPageState();
}

class _WalletRekeningPageState extends State<WalletRekeningPage> {
  Map<String, dynamic>? _user;
  List _rekening = [];
  bool _busy = false;
  final _bankCtrl = TextEditingController();
  final _noCtrl = TextEditingController();
  final _namaCtrl = TextEditingController();
  bool _showForm = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    _user = await Session.load();
    if (_user == null) return;
    try {
      final res = await httpGetWithRetry('$kApiBase/wallet/rekening?user_id=${_user!['id']}');
      if (res.statusCode == 200) setState(() => _rekening = jsonDecode(res.body)['data'] as List? ?? []);
    } catch (_) {}
  }

  Future<void> _save() async {
    if (_bankCtrl.text.trim().isEmpty || _noCtrl.text.trim().isEmpty || _namaCtrl.text.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/rekening'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': _user!['id'],
          'bank_name': _bankCtrl.text.trim(),
          'account_number': _noCtrl.text.trim(),
          'account_holder': _namaCtrl.text.trim(),
          'is_default': _rekening.isEmpty,
        }),
      );
      if (!mounted) return;
      if (res.statusCode == 201) {
        _bankCtrl.clear();
        _noCtrl.clear();
        _namaCtrl.clear();
        setState(() { _busy = false; _showForm = false; });
        await _load();
        Navigator.pop(context, true);
      } else {
        final body = jsonDecode(res.body);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(body['message'] ?? 'Gagal menyimpan')));
        setState(() => _busy = false);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Koneksi gagal: $e')));
      setState(() => _busy = false);
    }
  }

  Future<void> _setDefault(int id) async {
    try {
      final res = await http.put(
        Uri.parse('$kApiBase/wallet/rekening/$id'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'user_id': _user!['id'], 'is_default': true}),
      );
      if (res.statusCode == 200) {
        await _load();
        Navigator.pop(context, true);
      }
    } catch (_) {}
  }

  Future<void> _delete(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Rekening?'),
        content: const Text('Rekening ini akan dihapus dari wallet kamu.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Hapus')),
        ],
      ),
    );
    if (confirm != true) return;
    try {
      final res = await http.delete(Uri.parse('$kApiBase/wallet/rekening/$id?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        await _load();
        Navigator.pop(context, true);
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _bankCtrl.dispose();
    _noCtrl.dispose();
    _namaCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kelola Rekening'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_showForm)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: kPurpleCard.withOpacity(0.4), borderRadius: BorderRadius.circular(14)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Rekening Baru', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 10),
                  TextField(controller: _bankCtrl, decoration: const InputDecoration(labelText: 'Nama Bank (mis. BCA, Mandiri, BRI)', border: OutlineInputBorder(), prefixIcon: Icon(Icons.account_balance))),
                  const SizedBox(height: 10),
                  TextField(controller: _noCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Nomor Rekening', border: OutlineInputBorder(), prefixIcon: Icon(Icons.numbers))),
                  const SizedBox(height: 10),
                  TextField(controller: _namaCtrl, decoration: const InputDecoration(labelText: 'Nama Pemilik Rekening', border: OutlineInputBorder(), prefixIcon: Icon(Icons.person))),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: _busy ? null : _save,
                      icon: _busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.save_outlined),
                      label: const Text('Simpan Rekening'),
                    ),
                  ),
                ],
              ),
            )
          else
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => setState(() => _showForm = true),
                icon: const Icon(Icons.add),
                label: const Text('Tambah Rekening Baru'),
              ),
            ),
          const SizedBox(height: 10),
          if (_rekening.isEmpty && !_showForm)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: Text('Belum ada rekening tersimpan.', style: TextStyle(color: Colors.grey)))),
          for (final r in _rekening)
            Card(
              margin: const EdgeInsets.only(bottom: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                leading: CircleAvatar(radius: 22, backgroundColor: kPurpleCard.withOpacity(0.5), child: const Icon(Icons.account_balance, color: kPurpleMain)),
                title: Text('${r['bank_name']} ${r['account_number']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text('a.n. ${r['account_holder']}${r['is_default'] == true ? ' • Rekening utama' : ''}'),
                trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                  if (r['is_default'] != true)
                    IconButton(icon: const Icon(Icons.star_border, size: 20), tooltip: 'Jadikan utama', onPressed: () => _setDefault(r['id'])),
                  IconButton(icon: const Icon(Icons.delete_outline, size: 20, color: Colors.red), tooltip: 'Hapus', onPressed: () => _delete(r['id'])),
                ]),
              ),
            ),
          const SizedBox(height: 10),
          const Text('Untuk produksi, verifikasi nama pemilik akan melalui inquiry bank API.', style: TextStyle(color: Colors.grey, fontSize: 11)),
        ],
      ),
    );
  }
}

/// Set / ubah PIN wallet 6 digit.
class WalletPinPage extends StatefulWidget {
  const WalletPinPage({super.key});
  @override
  State<WalletPinPage> createState() => _WalletPinPageState();
}

class _WalletPinPageState extends State<WalletPinPage> {
  Map<String, dynamic>? _user;
  bool _pinExists = false;
  bool _loading = true;
  final _oldCtrl = TextEditingController();
  final _new1Ctrl = TextEditingController();
  final _new2Ctrl = TextEditingController();
  bool _busy = false;
  String? _msg;

  @override
  void initState() {
    super.initState();
    _checkPin();
  }

  Future<void> _checkPin() async {
    _user = await Session.load();
    if (_user == null) {
      setState(() => _loading = false);
      return;
    }
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/pin/verify'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'user_id': _user!['id'], 'pin': ''}),
      );
      final body = jsonDecode(res.body);
      if (mounted) {
        setState(() {
          _loading = false;
          _pinExists = body['data']?['pin_set'] == true;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    final newPin = _new1Ctrl.text.trim();
    if (!RegExp(r'^\d{6}$').hasMatch(newPin)) {
      setState(() => _msg = 'PIN baru harus 6 digit angka.');
      return;
    }
    if (newPin != _new2Ctrl.text.trim()) {
      setState(() => _msg = 'Konfirmasi PIN tidak cocok.');
      return;
    }
    if (_pinExists && !RegExp(r'^\d{6}$').hasMatch(_oldCtrl.text.trim())) {
      setState(() => _msg = 'Masukkan PIN lama 6 digit.');
      return;
    }
    setState(() {
      _busy = true;
      _msg = null;
    });
    try {
      final payload = {'user_id': _user!['id'], 'new_pin': newPin};
      if (_pinExists) payload['old_pin'] = _oldCtrl.text.trim();
      final res = await http.post(
        Uri.parse('$kApiBase/wallet/pin/set'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      );
      final body = jsonDecode(res.body);
      if (!mounted) return;
      if (res.statusCode == 200) {
        setState(() {
          _busy = false;
          _msg = 'PIN berhasil ${_pinExists ? 'diubah' : 'dibuat'}.';
        });
      } else {
        setState(() {
          _busy = false;
          _msg = body['message'] ?? 'Gagal menyimpan PIN.';
        });
      }
    } catch (e) {
      setState(() {
        _busy = false;
        _msg = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  void dispose() {
    _oldCtrl.dispose();
    _new1Ctrl.dispose();
    _new2Ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_pinExists ? 'Ubah PIN Wallet' : 'Buat PIN Wallet'), backgroundColor: kPurpleMain, foregroundColor: Colors.white),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(color: kPurpleCard.withOpacity(0.4), borderRadius: BorderRadius.circular(12)),
                  child: const Row(children: [
                    Icon(Icons.shield_outlined, color: kPurpleMain),
                    SizedBox(width: 12),
                    Expanded(child: Text('PIN 6 digit wajib untuk top up & tarik dana. PIN salah 5x akan mengunci wallet sementara 5 menit.', style: TextStyle(fontSize: 12))),
                  ]),
                ),
                const SizedBox(height: 18),
                if (_pinExists) ...[
                  TextField(controller: _oldCtrl, obscureText: true, keyboardType: TextInputType.number, maxLength: 6, decoration: const InputDecoration(labelText: 'PIN Lama', border: OutlineInputBorder(), prefixIcon: Icon(Icons.lock_outline))),
                  const SizedBox(height: 12),
                ],
                TextField(controller: _new1Ctrl, obscureText: true, keyboardType: TextInputType.number, maxLength: 6, decoration: const InputDecoration(labelText: 'PIN Baru (6 digit)', border: OutlineInputBorder(), prefixIcon: Icon(Icons.key))),
                const SizedBox(height: 12),
                TextField(controller: _new2Ctrl, obscureText: true, keyboardType: TextInputType.number, maxLength: 6, decoration: const InputDecoration(labelText: 'Konfirmasi PIN Baru', border: OutlineInputBorder(), prefixIcon: Icon(Icons.key))),
                const SizedBox(height: 16),
                if (_msg != null)
                  Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(_msg!, style: TextStyle(color: _msg!.contains('berhasil') ? Colors.green.shade800 : Colors.red.shade800, fontSize: 13))),
                const SizedBox(height: 14),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _busy ? null : _save,
                    icon: _busy ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.check_circle_outline),
                    label: const Text('Simpan PIN'),
                  ),
                ),
              ],
            ),
    );
  }
}
