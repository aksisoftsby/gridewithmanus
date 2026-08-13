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

              // GridePay wallet card
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
                          const Row(children: [Icon(Icons.account_balance_wallet, color: kGoldBright, size: 18), SizedBox(width: 8), Text('GridePay', style: TextStyle(color: Color(0xFFF7D27E), fontWeight: FontWeight.bold, fontSize: 14))]),
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

  /// Wallet action buttons (Tarik / Top Up) inside the GridePay card.
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
        title: const Text('Gride Kirim'),
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
                              if (_pickupLat != null && _dropoffLat != null) ...[
                                Marker(
                                  point: ll.LatLng(_pickupLat!, _pickupLng!),
                                  width: 44,
                                  height: 44,
                                  child: const Icon(Icons.location_on, color: Colors.green, size: 40),
                                ),
                                Marker(
                                  point: ll.LatLng(_dropoffLat!, _dropoffLng!),
                                  width: 44,
                                  height: 44,
                                  child: const Icon(Icons.flag, color: Colors.deepPurple, size: 40),
                                ),
                              ],
                            ],
                          ),
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
                      if (_routeDurationSec != null) ...[
                        const SizedBox(height: 6),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Estimasi waktu tempuh'),
                            Text('${(_routeDurationSec! / 60).round()} menit',
                                style: const TextStyle(fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ],
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('${_distanceKm!.ceil()} km \u00d7 ${formatRp(_costPerKm.round())}/km'),
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
            if (_routePoints.length >= 2 && _pickMode == null)
              Card(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                clipBehavior: Clip.antiAlias,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                      child: Row(
                        children: [
                          Icon(Icons.route, color: Colors.teal.shade800, size: 18),
                          const SizedBox(width: 6),
                          Text('Rute Perjalanan (OpenStreetMap)',
                              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.teal.shade800, fontSize: 14)),
                        ],
                      ),
                    ),
                    Container(
                      height: 280,
                      color: Colors.grey.shade200,
                      child: FlutterMap(
                        mapController: _mapCtrl,
                        options: const MapOptions(
                          initialZoom: 13,
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
                                point: ll.LatLng(_pickupLat!, _pickupLng!),
                                width: 44,
                                height: 44,
                                child: const Icon(Icons.location_on, color: Colors.green, size: 40),
                              ),
                              Marker(
                                point: ll.LatLng(_dropoffLat!, _dropoffLng!),
                                width: 44,
                                height: 44,
                                child: const Icon(Icons.flag, color: Colors.deepPurple, size: 40),
                              ),
                            ],
                          ),
                          PolylineLayer(
                            polylines: [
                              Polyline(
                                points: _routePoints,
                                strokeWidth: 4.5,
                                color: Colors.redAccent.shade700,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
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
        final list = (data['data'] as List).cast<Map<String, dynamic>>();
        final lastPage = data['last_page'] is int ? data['last_page'] as int : int.tryParse(data['last_page'].toString()) ?? 1;
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
                                  final photos = (item['photos'] as List?) ?? [];
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
    final photos = ((i['photos'] as List?) ?? []).map((p) => p.toString()).toList();
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
      _photoUrls.addAll(((i['photos'] as List?) ?? []).map((p) => p.toString()));
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
