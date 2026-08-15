import 'package:flutter/material.dart';
import 'dart:async';
import 'dart:convert';
import "package:http/http.dart" as http;
import "package:flutter_inappwebview/flutter_inappwebview.dart";
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  runApp(const DriverApp());
}

const String kApiBase = 'https://ridesip.my.id/api';

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'RideSip Driver',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: const DriverHome(),
      debugShowCheckedModeBanner: false,
    );
  }
}

// ============ Session (login tersimpan di device) ============

class Session {
  static const String _key = 'ridesip_driver_user';

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
}

// ============ Format Rupiah ============

String formatRp(int value) => value.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]}.',
    );

// ============ DriverHome (tabs: Home / Akun) ============

class DriverHome extends StatefulWidget {
  const DriverHome({super.key});

  @override
  State<DriverHome> createState() => _DriverHomeState();
}

class _DriverHomeState extends State<DriverHome> {
  int _tab = 0;
  Map<String, dynamic>? _user;
  final GlobalKey<_OrdersPageState> _ordersKey = GlobalKey();

  void refreshOrders() => _ordersKey.currentState?.fetchOrders();
  final GlobalKey<_AccountPageState> _accountKey = GlobalKey();

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await Session.load();
    setState(() => _user = user);
  }

  void onLoggedOut() {
    setState(() => _user = null);
    refreshOrders();
  }

  void onLoggedIn(Map<String, dynamic> user) {
    setState(() => _user = user);
    refreshOrders();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      OrdersPage(key: _ordersKey, onNeedRefresh: refreshOrders),
      AccountPage(
        key: _accountKey,
        onLoggedOut: onLoggedOut,
        onLoggedIn: onLoggedIn,
      ),
    ];
    return Scaffold(
      body: pages[_tab],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.list_alt), label: 'Pesanan'),
          NavigationDestination(icon: Icon(Icons.account_circle), label: 'Akun'),
        ],
      ),
    );
  }
}

// ============ Halaman Pesanan (Home) ============

class OrdersPage extends StatefulWidget {
  final VoidCallback? onNeedRefresh;
  const OrdersPage({super.key, this.onNeedRefresh});
  @override
  State<OrdersPage> createState() => _OrdersPageState();
}

class _OrdersPageState extends State<OrdersPage> {
  List orders = [];
  bool isLoading = true;
  Map<String, dynamic>? _user;

  // Ride-hailing (Gr-Antar Orang)
  List<Map<String, dynamic>> _inboundRides = [];
  Map<String, dynamic>? _activeRide;
  Timer? _ridePollTimer;
  bool _rideLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUser().then((_) => fetchOrders());
  }

  @override
  void dispose() {
    _ridePollTimer?.cancel();
    super.dispose();
  }

  int? _driverUserId() {
    if (_user == null) return null;
    final d = _user!['driver_id'];
    return d == null ? null : int.tryParse(d.toString());
  }

  void _startRidePolling() {
    _ridePollTimer?.cancel();
    _ridePollTimer = Timer.periodic(const Duration(seconds: 5), (_) => fetchRides());
    fetchRides();
  }

  Future<void> fetchRides() async {
    final uid = _driverUserId();
    if (uid == null) return;
    try {
      final res = await http.get(Uri.parse('$kApiBase/rides/current?user_id=$uid'));
      if (res.statusCode == 200) {
        final cur = jsonDecode(res.body)['data'];
        final inboundRes = await http.get(Uri.parse('$kApiBase/rides/inbound?user_id=$uid'));
        final inbound = inboundRes.statusCode == 200
            ? List<Map<String, dynamic>>.from(jsonDecode(inboundRes.body)['data'] ?? [])
            : <Map<String, dynamic>>[];
        if (mounted) {
          setState(() {
            _activeRide = (cur is Map && cur.isNotEmpty) ? Map<String, dynamic>.from(cur) : null;
            _inboundRides = inbound;
            _rideLoading = false;
          });
        }
        return;
      }
      if (mounted) setState(() => _rideLoading = false);
    } catch (_) {
      if (mounted) setState(() => _rideLoading = false);
    }
  }

  Future<void> _rideAction(String id, String action) async {
    final uid = _driverUserId();
    if (uid == null) return;
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/rides/$id/$action?user_id=$uid'),
        headers: const {'Content-Type': 'application/json'},
      );
      final ok = res.statusCode == 200;
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(ok ? 'Berhasil' : 'Gagal: ${res.body}'),
              backgroundColor: ok ? Colors.green : Colors.red),
        );
        fetchRides();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Gagal: $e'), backgroundColor: Colors.red));
      }
    }
  }

  Color _rideStatusColor(String? status) {
    switch (status) {
      case 'SEARCHING_DRIVER':
        return Colors.orange.shade700;
      case 'DRIVER_ACCEPTED':
      case 'DRIVER_ARRIVING':
        return Colors.blue.shade700;
      case 'DRIVER_ARRIVED':
      case 'TRIP_STARTED':
        return Colors.indigo.shade700;
      case 'COMPLETED':
        return Colors.green.shade700;
      case 'CANCELLED':
        return Colors.red.shade700;
      default:
        return Colors.grey.shade600;
    }
  }

  Future<void> _loadUser() async => _user = await Session.load();

  Future<void> fetchOrders() async {
    setState(() => isLoading = true);
    try {
      final res = await http.get(Uri.parse('$kApiBase/orders'));
      if (res.statusCode == 200) {
        setState(() {
          orders = jsonDecode(res.body)['data'];
          isLoading = false;
        });
      } else {
        setState(() => isLoading = false);
      }
    } catch (e) {
      setState(() => isLoading = false);
    }
    // Ride-hailing: polling hanya setelah driver login
    if (_user != null) _startRidePolling();
  }

  List<Widget> _invoiceRows(Map<String, dynamic> invoice, Map<String, dynamic> order) {
    final rows = <Widget>[];
    final t = invoice['order_type'] ?? '';
    if (t == 'RIDE' || t == 'DELIVERY') {
      if (invoice['trip_distance_km'] != null) {
        rows.add(_row('Jarak', '${double.tryParse(invoice['trip_distance_km'].toString())?.toStringAsFixed(1) ?? '-'} km'));
      }
      rows.add(_row('Tarif dasar', 'Rp ${formatRp((invoice['base_fare'] ?? 0).round())}'));
      if ((invoice['trip_cost'] ?? 0) > 0) {
        rows.add(_row(invoice['trip_cost_label'] ?? 'Biaya perjalanan', 'Rp ${formatRp((invoice['trip_cost'] ?? 0).round())}'));
      }
      if ((invoice['admin_commission'] ?? 0) > 0) {
        rows.add(_row(invoice['admin_commission_label'] ?? 'Potongan admin',
            '- Rp ${formatRp((invoice['admin_commission']).round())}',
            negative: true));
      }
      rows.add(_row(invoice['driver_net_label'] ?? 'Pendapatan Driver',
          'Rp ${formatRp((invoice['driver_net'] ?? 0).round())}',
          bold: true));
    } else {
      rows.add(_row('Subtotal', 'Rp ${formatRp((invoice['subtotal'] ?? 0).round())}'));
      if ((invoice['delivery_fee'] ?? 0) > 0) {
        rows.add(_row('Ongkos kirim', 'Rp ${formatRp((invoice['delivery_fee'] ?? 0).round())}'));
      }
      rows.add(_row('Total pesanan', 'Rp ${formatRp((invoice['total'] ?? 0).round())}'));
    }
    return rows;
  }

  Widget _row(String label, String value, {bool bold = false, bool negative = false}) {
    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 13, color: Colors.grey[700])),
          Text(value, style: TextStyle(
            fontSize: 13,
            fontWeight: bold ? FontWeight.bold : FontWeight.w500,
            color: negative ? Colors.red[700] : (bold ? Colors.teal[800] : Colors.black87),
          )),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('RideSip Driver'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: fetchOrders,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.teal.shade50,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.teal),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Halo, ${_user?['full_name'] ?? 'Driver'}!',
                          style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal, fontSize: 16),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Daftar pesanan masuk dari customer & merchant. Login dulu di menu Akun untuk melihat pesanan milik Anda.',
                          style: TextStyle(fontSize: 13, color: Colors.black87),
                        ),
                        const SizedBox(height: 14),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            icon: const Icon(Icons.bolt, size: 18),
                            label: const Text('PPOB — Pulsa, PLN, Voucher Game & Lainnya'),
                            style: FilledButton.styleFrom(backgroundColor: Colors.teal, padding: const EdgeInsets.symmetric(vertical: 12)),
                            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PpobWebViewPage())),
                          ),
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            icon: const Icon(Icons.campaign, size: 18),
                            label: const Text('Iklan Gratis — Jual & Beli Barang Bekas'),
                            style: FilledButton.styleFrom(backgroundColor: const Color(0xFFD8006B), padding: const EdgeInsets.symmetric(vertical: 12)),
                            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const IklanWebViewPage())),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                  if (_user != null) ...[
                    if (_rideLoading)
                      const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
                    else if (_activeRide != null) ...[
                      Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        color: _rideStatusColor(_activeRide!['status']).withOpacity(0.08),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: _rideStatusColor(_activeRide!['status']))),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.directions_car, color: _rideStatusColor(_activeRide!['status'])),
                                  const SizedBox(width: 8),
                                  const Text('Ride Aktif', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text('No: ${_activeRide!['order_number'] ?? '-'}', style: const TextStyle(fontWeight: FontWeight.w600)),
                              if ((_activeRide!['pickup_address'] ?? '') != '') Text('Jemput: ${_activeRide!['pickup_address']}'),
                              if ((_activeRide!['dropoff_address'] ?? '') != '') Text('Tujuan: ${_activeRide!['dropoff_address']}'),
                              if ((_activeRide!['customer_name'] ?? '') != '') Text('Penumpang: ${_activeRide!['customer_name']}'),
                              if ((_activeRide!['fare'] ?? 0) > 0) Text('Tarif: Rp ${formatRp((_activeRide!['fare'] ?? 0).round())}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                              const SizedBox(height: 8),
                              Chip(label: Text((_activeRide!['status'] ?? '').toString().replaceAll('_', ' '), style: const TextStyle(fontSize: 11, color: Colors.white)),
                                  backgroundColor: _rideStatusColor(_activeRide!['status'])),
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  if (_activeRide!['status'] == 'DRIVER_ACCEPTED' || _activeRide!['status'] == 'DRIVER_ARRIVING')
                                    Expanded(child: FilledButton.icon(onPressed: () => _rideAction(_activeRide!['id'].toString(), 'arriving'),
                                        icon: const Icon(Icons.navigation, size: 16), label: const Text('Menuju Penjemputan'))),
                                  if (_activeRide!['status'] == 'DRIVER_ARRIVING' || _activeRide!['status'] == 'DRIVER_ARRIVED' || _activeRide!['status'] == 'TRIP_STARTED')
                                    Expanded(child: FilledButton.icon(onPressed: () => _rideAction(_activeRide!['id'].toString(), 'arrived'),
                                        icon: const Icon(Icons.location_on, size: 16), label: const Text('Tiba di Penjemputan'))),
                                  if (_activeRide!['status'] == 'DRIVER_ARRIVED')
                                    Expanded(child: FilledButton.icon(onPressed: () => _rideAction(_activeRide!['id'].toString(), 'start'),
                                        icon: const Icon(Icons.play_arrow, size: 16), label: const Text('Mulai Perjalanan'))),
                                  if (_activeRide!['status'] == 'TRIP_STARTED')
                                    Expanded(child: FilledButton.icon(onPressed: () => _rideAction(_activeRide!['id'].toString(), 'complete'),
                                        icon: const Icon(Icons.flag, size: 16), label: const Text('Selesai'))),
                                  if (_activeRide!['status'] == 'DRIVER_ACCEPTED')
                                    Expanded(child: OutlinedButton.icon(onPressed: () => _rideAction(_activeRide!['id'].toString(), 'reject'),
                                        icon: const Icon(Icons.close, size: 16), label: const Text('Batalkan'), style: OutlinedButton.styleFrom(foregroundColor: Colors.red))),
                                ].where((e) => true).toList(),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ] else if (_inboundRides.isNotEmpty) ...[
                      const Text('Permintaan Antar Orang', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 10),
                      for (final ride in _inboundRides)
                        Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          color: Colors.orange.shade50,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('No: ${ride['order_number'] ?? '-'}', style: const TextStyle(fontWeight: FontWeight.bold)),
                                if ((ride['pickup_address'] ?? '') != '') Text('Jemput: ${ride['pickup_address']}'),
                                if ((ride['dropoff_address'] ?? '') != '') Text('Tujuan: ${ride['dropoff_address']}'),
                                if ((ride['customer_name'] ?? '') != '') Text('Penumpang: ${ride['customer_name']}'),
                                if ((ride['fare'] ?? 0) > 0) Text('Tarif: Rp ${formatRp((ride['fare'] ?? 0).round())}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                                const SizedBox(height: 10),
                                Row(
                                  children: [
                                    Expanded(child: FilledButton.icon(onPressed: () => _rideAction(ride['id'].toString(), 'accept'),
                                        icon: const Icon(Icons.check, size: 16), label: const Text('Terima'),
                                        style: FilledButton.styleFrom(backgroundColor: Colors.green))),
                                    const SizedBox(width: 8),
                                    Expanded(child: OutlinedButton.icon(onPressed: () => _rideAction(ride['id'].toString(), 'reject'),
                                        icon: const Icon(Icons.close, size: 16), label: const Text('Tolak'),
                                        style: OutlinedButton.styleFrom(foregroundColor: Colors.red))),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                    ] else
                      Card(
                        child: const Padding(
                          padding: EdgeInsets.all(24),
                          child: Text('Belum ada permintaan ride masuk. Tetap online agar dapat orderan ride.', textAlign: TextAlign.center),
                        ),
                      ),
                    const SizedBox(height: 20),
                  ],
                  const Text('Daftar Pesanan Masuk', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 10),
                  if (orders.isEmpty)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(24),
                        child: Text('Belum ada pesanan masuk.', textAlign: TextAlign.center),
                      ),
                    ),
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: orders.length,
                    itemBuilder: (context, index) {
                      final order = orders[index];
                      final invoice = Map<String, dynamic>.from(order['invoice'] ?? {});
                      final isMine = order['driver_id'] != null &&
                          _user != null &&
                          order['driver_id'].toString() == _user!['driver_id'].toString();
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(order['order_number'] ?? '',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                  ),
                                  Chip(
                                    label: Text(order['status'] ?? '', style: const TextStyle(fontSize: 11)),
                                    backgroundColor: isMine ? Colors.amber.shade100 : Colors.teal.shade100,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              if ((order['customer_name'] ?? '') != '') Text('Customer: ${order['customer_name']}'),
                              if ((order['merchant_name'] ?? '') != '') Text('Merchant: ${order['merchant_name']}'),
                              if ((order['pickup_address'] ?? '') != '') Text('Jemput: ${order['pickup_address']}'),
                              if ((order['dropoff_address'] ?? '') != '') Text('Tujuan: ${order['dropoff_address']}'),
                              const SizedBox(height: 8),
                              Container(
                                decoration: BoxDecoration(
                                  color: Colors.grey.shade100,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                padding: const EdgeInsets.all(12),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: _invoiceRows(invoice, order),
                                ),
                              ),
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
}

// ============ Halaman Akun (Login / Daftar / Saldo / History) ============

class AccountPage extends StatefulWidget {
  final VoidCallback onLoggedOut;
  final void Function(Map<String, dynamic>) onLoggedIn;
  const AccountPage({super.key, required this.onLoggedOut, required this.onLoggedIn});
  @override
  State<AccountPage> createState() => _AccountPageState();
}

class _AccountPageState extends State<AccountPage> {
  Map<String, dynamic>? _user;
  Map<String, dynamic>? _driver;
  Map<String, dynamic>? _earnings;
  bool isLoadingProfile = false;
  bool isLoadingHistory = false;
  int _seg = 0; // 0 = login, 1 = daftar

  // form login
  final _loginEmailCtrl = TextEditingController();
  final _loginPassCtrl = TextEditingController();

  // form daftar
  final _regNameCtrl = TextEditingController();
  final _regEmailCtrl = TextEditingController();
  final _regPhoneCtrl = TextEditingController();
  final _regPassCtrl = TextEditingController();
  final _regPlateCtrl = TextEditingController();
  String _regVehicle = 'MOTOR';

  @override
  void initState() {
    super.initState();
    _hydrate();
  }

  Future<void> _hydrate() async {
    final user = await Session.load();
    if (user != null) {
      setState(() => _user = user);
      await Future.wait([loadProfile(), loadEarnings()]);
    }
  }

  Future<void> loadProfile() async {
    if (_user == null) return;
    setState(() => isLoadingProfile = true);
    try {
      final res = await http.get(Uri.parse('$kApiBase/driver/me?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        setState(() {
          _driver = data;
          isLoadingProfile = false;
        });
        return;
      }
    } catch (_) {}
    setState(() => isLoadingProfile = false);
  }

  Future<void> loadEarnings() async {
    if (_user == null) return;
    setState(() => isLoadingHistory = true);
    try {
      final res = await http.get(Uri.parse('$kApiBase/driver/earnings?user_id=${_user!['id']}'));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        setState(() {
          _earnings = data;
          isLoadingHistory = false;
        });
        return;
      }
    } catch (_) {}
    setState(() => isLoadingHistory = false);
  }

  void _snack(String msg, {bool error = true}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: error ? Colors.red.shade700 : Colors.teal.shade700,
      ),
    );
  }

  Future<void> doLogin() async {
    final email = _loginEmailCtrl.text.trim();
    final pass = _loginPassCtrl.text;
    if (email.isEmpty || pass.isEmpty) {
      _snack('Email dan password harus diisi.');
      return;
    }
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/login'),
        headers: const {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': pass}),
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 200 && body['status'] == 'success') {
        final data = Map<String, dynamic>.from(body['data']);
        if ((data['role'] ?? '').toString() != 'MEMBER') {
          _snack('Akun ini bukan akun driver.');
          return;
        }
        await Session.save(data);
        setState(() => _user = data);
        widget.onLoggedIn(data);
        _loginEmailCtrl.clear();
        _loginPassCtrl.clear();
        await Future.wait([loadProfile(), loadEarnings()]);
        _snack('Login berhasil. Selamat bekerja!', error: false);
      } else {
        _snack(body['message'] ?? 'Login gagal.');
      }
    } catch (_) {
      _snack('Koneksi gagal. Periksa internet Anda.');
    }
  }

  Future<void> doRegister() async {
    final name = _regNameCtrl.text.trim();
    final email = _regEmailCtrl.text.trim();
    final phone = _regPhoneCtrl.text.trim();
    final pass = _regPassCtrl.text;
    final plate = _regPlateCtrl.text.trim();
    if (name.isEmpty || email.isEmpty || pass.length < 6) {
      _snack('Nama lengkap, email, dan password (min 6 karakter) wajib diisi.');
      return;
    }
    try {
      final res = await http.post(
        Uri.parse('$kApiBase/register-driver'),
        headers: const {'Content-Type': 'application/json'},
        body: jsonEncode({
          'full_name': name,
          'email': email,
          if (phone.isNotEmpty) 'phone': phone,
          'password': pass,
          'vehicle_type': _regVehicle,
          if (plate.isNotEmpty) 'plate_number': plate,
        }),
      );
      final body = jsonDecode(res.body);
      if (res.statusCode == 201 && body['status'] == 'success') {
        _snack('Pendaftaran berhasil! Silakan login.', error: false);
        setState(() => _seg = 0);
        _regNameCtrl.clear();
        _regEmailCtrl.clear();
        _regPhoneCtrl.clear();
        _regPassCtrl.clear();
        _regPlateCtrl.clear();
      } else {
        _snack(body['message'] ?? 'Pendaftaran gagal.');
      }
    } catch (_) {
      _snack('Koneksi gagal. Periksa internet Anda.');
    }
  }

  Future<void> doLogout() async {
    await Session.clear();
    setState(() {
      _user = null;
      _driver = null;
      _earnings = null;
    });
    widget.onLoggedOut();
    _snack('Anda sudah keluar.', error: false);
  }

  @override
  void dispose() {
    _loginEmailCtrl.dispose();
    _loginPassCtrl.dispose();
    _regNameCtrl.dispose();
    _regEmailCtrl.dispose();
    _regPhoneCtrl.dispose();
    _regPassCtrl.dispose();
    _regPlateCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Akun'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: _user == null ? _buildAuth() : _buildAccount(),
    );
  }

  // ===== Belum login: form login / daftar =====
  Widget _buildAuth() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 8),
          const Icon(Icons.directions_car_filled, size: 56, color: Colors.teal),
          const SizedBox(height: 4),
          const Text('Masuk / Daftar Driver', textAlign: TextAlign.center,
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          const Text('Bergabung sebagai driver RideSip dan mulai dapatkan penghasilan.',
              textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: Colors.black54)),
          const SizedBox(height: 16),
          SegmentedButton<int>(
            segments: const [
              ButtonSegment(value: 0, label: Text('Login'), icon: Icon(Icons.login, size: 16)),
              ButtonSegment(value: 1, label: Text('Daftar Driver'), icon: Icon(Icons.person_add, size: 16)),
            ],
            selected: {_seg},
            onSelectionChanged: (s) => setState(() => _seg = s.first),
          ),
          const SizedBox(height: 16),
          if (_seg == 0) ...[
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      controller: _loginEmailCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Email', border: OutlineInputBorder(), prefixIcon: Icon(Icons.email),
                      ),
                      keyboardType: TextInputType.emailAddress,
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _loginPassCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Password', border: OutlineInputBorder(), prefixIcon: Icon(Icons.lock),
                      ),
                      obscureText: true,
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 46,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.teal,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: doLogin,
                        child: const Text('LOGIN', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ] else ...[
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      controller: _regNameCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Nama Lengkap', border: OutlineInputBorder(), prefixIcon: Icon(Icons.person),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _regEmailCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Email', border: OutlineInputBorder(), prefixIcon: Icon(Icons.email),
                      ),
                      keyboardType: TextInputType.emailAddress,
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _regPhoneCtrl,
                      decoration: const InputDecoration(
                        labelText: 'No. HP', border: OutlineInputBorder(), prefixIcon: Icon(Icons.phone),
                      ),
                      keyboardType: TextInputType.phone,
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _regPassCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Password (min 6 karakter)', border: OutlineInputBorder(), prefixIcon: Icon(Icons.lock),
                      ),
                      obscureText: true,
                    ),
                    const SizedBox(height: 12),
                    const Text('Kendaraan', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(value: 'MOTOR', label: Text('Motor')),
                        ButtonSegment(value: 'MOBIL', label: Text('Mobil')),
                      ],
                      selected: {_regVehicle},
                      onSelectionChanged: (s) => setState(() => _regVehicle = s.first),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _regPlateCtrl,
                      decoration: const InputDecoration(
                        labelText: 'Nomor Plat (contoh: AG 1234 XY)', border: OutlineInputBorder(), prefixIcon: Icon(Icons.car_rental),
                      ),
                      textCapitalization: TextCapitalization.characters,
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 46,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.teal,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: doRegister,
                        child: const Text('DAFTAR DRIVER', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
          const SizedBox(height: 24),
          const Text(
            'Setelah mendaftar & login, saldo wallet dan riwayat pemasukan Anda akan tampil di halaman ini.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: Colors.black45),
          ),
        ],
      ),
    );
  }

  // ===== Sudah login: profil, saldo, history =====
  Widget _buildAccount() {
    final wallet = Map<String, dynamic>.from(_driver?['wallet'] ?? {'balance': 0, 'pending_balance': 0});
    final balance = (double.tryParse(wallet['balance']?.toString() ?? '0') ?? 0).round();
    final pending = (double.tryParse(wallet['pending_balance']?.toString() ?? '0') ?? 0).round();
    final vehicle = _driver?['vehicle'];
    final history = List<Map<String, dynamic>>.from(_earnings?['history'] ?? []);
    final totalEarned = (_earnings?['total_earned'] ?? 0).round();
    final totalPending = (_earnings?['total_pending'] ?? 0).round();

    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([loadProfile(), loadEarnings()]);
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Profil card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.teal.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.teal),
            ),
            child: Row(
              children: [
                const CircleAvatar(radius: 26, backgroundColor: Colors.teal, child: Icon(Icons.person, color: Colors.white, size: 30)),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(_user?['full_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(_user?['email'] ?? '', style: TextStyle(fontSize: 12, color: Colors.grey[700])),
                      const SizedBox(height: 4),
                      Chip(
                        label: Text('DRIVER · ${_driver?['driver_status'] ?? 'OFFLINE'}', style: const TextStyle(fontSize: 11)),
                        backgroundColor: Colors.teal.shade100,
                        visualDensity: VisualDensity.compact,
                      ),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: doLogout,
                  icon: const Icon(Icons.logout, color: Colors.red),
                  tooltip: 'Keluar',
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Saldo wallet card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Colors.teal, Colors.tealAccent], begin: Alignment.topLeft, end: Alignment.bottomRight),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('SALDO DOMPET DRIVER', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600, letterSpacing: 1)),
                const SizedBox(height: 6),
                Text('Rp $formatRp(balance)', style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.bold)),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                        decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(10)),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Tercatat', style: TextStyle(color: Colors.white70, fontSize: 11)),
                            Text('Rp $formatRp(balance)', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                        decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(10)),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Menunggu', style: TextStyle(color: Colors.white70, fontSize: 11)),
                            Text('Rp $formatRp(pending)', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          // GrSaldo (wallet summary dari server)
          GrSaldoCard(userId: int.tryParse(_driver?['id']?.toString() ?? '0')),
          const SizedBox(height: 10),
          // Kendaraan Saya (multi-kendaraan)
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const KendaraanPage())),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.teal.shade200),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(color: Colors.teal.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
                    child: const Icon(Icons.car_rental, color: Colors.teal),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Kendaraan Saya', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87)),
                        SizedBox(height: 2),
                        Text('Kelola semua kendaraan Anda (maks. 1 aktif per jenis)', style: TextStyle(fontSize: 12, color: Colors.black54)),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right, color: Colors.black45),
                ],
              ),
            ),
          ),
          const SizedBox(height: 10),
          // Kendaraan lama (compat, dari tabel users)
          if (vehicle != null)
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: const Icon(Icons.car_rental, color: Colors.black38),
                title: Text('Kendaraan lama: ${(vehicle['vehicle_type'] ?? '').toString().toUpperCase()}'),
                subtitle: Text('Plat: ${vehicle['plate_number'] ?? '-'}'),
              ),
            ),
          const SizedBox(height: 10),

          // Riwayat pemasukan
          const Text('Riwayat Pemasukan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 10),
          if (isLoadingHistory)
            const Card(child: Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator())))
          else if (history.isEmpty)
            const Card(
              child: Padding(padding: EdgeInsets.all(24), child: Text('Belum ada riwayat pemasukan. Selesaikan pesanan untuk mulai mendapat penghasilan.', textAlign: TextAlign.center)),
            )
          else ...[
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              color: Colors.teal.shade50,
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Total sudah cair', style: TextStyle(color: Colors.grey[700])),
                    Text('Rp $formatRp(totalEarned)', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              color: Colors.amber.shade50,
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Total menunggu cair', style: TextStyle(color: Colors.grey[700])),
                    Text('Rp $formatRp(totalPending)', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.orange)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 10),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: history.length,
              itemBuilder: (context, i) {
                final h = history[i];
                final net = (h['driver_net'] ?? 0).round();
                final isCompleted = (h['status'] ?? '').toString().toUpperCase() == 'COMPLETED';
                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(h['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                            ),
                            Chip(
                              label: Text(h['status'] ?? '', style: const TextStyle(fontSize: 11)),
                              backgroundColor: isCompleted ? Colors.green.shade100 : Colors.orange.shade100,
                              visualDensity: VisualDensity.compact,
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        if ((h['pickup_address'] ?? '') != '') Text('Jemput: ${h['pickup_address']}', style: TextStyle(fontSize: 13, color: Colors.grey[700])),
                        if ((h['dropoff_address'] ?? '') != '') Text('Tujuan: ${h['dropoff_address']}', style: TextStyle(fontSize: 13, color: Colors.grey[700])),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(h['created_at']?.toString().substring(0, 16) ?? '', style: TextStyle(fontSize: 12, color: Colors.grey[500])),
                            Text('Rp $formatRp(net)', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ],
      ),
    );
  }
}

// ============ Halaman Iklan Gratis — WebView ke https://ridesip.my.id/iklan-webview ============
// ============ GrSaldo Card (wallet summary server) ============
class GrSaldoCard extends StatefulWidget {
  final int userId;
  const GrSaldoCard({super.key, required this.userId});
  @override
  State<GrSaldoCard> createState() => _GrSaldoCardState();
}

class _GrSaldoCardState extends State<GrSaldoCard> {
  Map<String, dynamic>? _summary;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.userId <= 0) return;
    try {
      final res = await http.get(Uri.parse('$kApiBase/wallet/summary?user_id=${widget.userId}'));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        if (mounted) setState(() => _summary = Map<String, dynamic>.from(data ?? {}));
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (widget.userId <= 0 || _summary == null) {
      return const SizedBox.shrink();
    }
    final bal = (_summary!['balance'] ?? 0).round();
    final earn = (_summary!['total_earning'] ?? 0).round();
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Color(0xFF1B8A5A), Color(0xFF2ECC71)], begin: Alignment.topLeft, end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('GRSALDO', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600, letterSpacing: 1)),
          const SizedBox(height: 6),
          Text('Rp ${formatRp(bal)}', style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold)),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
                  decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(10)),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Pemasukan', style: TextStyle(color: Colors.white70, fontSize: 11)),
                      Text('Rp ${formatRp(earn)}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
                  decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(10)),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Total Transaksi', style: TextStyle(color: Colors.white70, fontSize: 11)),
                      Text('${_summary!['total_transactions'] ?? 0}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white54)),
              onPressed: _load,
              child: const Text('Segarkan', style: TextStyle(color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }
}

class IklanWebViewPage extends StatefulWidget {
  const IklanWebViewPage({super.key});
  @override
  State<IklanWebViewPage> createState() => _IklanWebViewPageState();
}
class _IklanWebViewPageState extends State<IklanWebViewPage> {
  String? _url;
  @override
  void initState() {
    super.initState();
    _boot();
  }
  Future<void> _boot() async {
    final user = await Session.load();
    if (!mounted) return;
    if (user == null || user['id'] == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Silakan login terlebih dahulu untuk membuka Iklan Gratis.')),
      );
      Navigator.pop(context);
      return;
    }
    final uid = user['id'].toString();
    try {
      final res = await http.get(Uri.parse('$kApiBase/iklan-gratis/webview-token?user_id=$uid')).timeout(const Duration(seconds: 10));
      final data = jsonDecode(res.body);
      final token = data['data']?['token'];
      if (res.statusCode != 200 || token == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Gagal menyiapkan sesi Iklan Gratis: ${data['message'] ?? 'server error'}')),
          );
          Navigator.pop(context);
        }
        return;
      }
      final name = (data['data']['full_name'] ?? '').toString();
      final phone = (data['data']['phone'] ?? '').toString();
      if (mounted) {
        setState(() => _url = 'https://ridesip.my.id/iklan-webview/?session_token=$token&user_id=$uid&name=${Uri.encodeComponent(name)}&phone=${Uri.encodeComponent(phone)}');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Koneksi gagal: $e')));
        Navigator.pop(context);
      }
    }
  }
  @override
  Widget build(BuildContext context) {
    if (_url == null) {
      return Scaffold(
        backgroundColor: Colors.teal,
        body: const Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }
    return Scaffold(
      backgroundColor: Colors.teal,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              color: Colors.teal,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white, size: 26),
                    onPressed: () => Navigator.pop(context),
                  ),
                  const Expanded(
                    child: Text('Iklan Gratis RideSip', textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),
            Expanded(
              child: InAppWebView(
                initialUrlRequest: URLRequest(url: WebUri(_url!)),
                initialSettings: InAppWebViewSettings(
                  javaScriptEnabled: true,
                  domStorageEnabled: true,
                  useHybridComposition: true,
                  supportMultipleWindows: false,
                  userAgent: 'RideSipDriverApp/1.0',
                  javaScriptCanOpenWindowsAutomatically: false,
                ),
                onLoadStop: (controller, url) {
                  controller.addJavaScriptHandler(handlerName: 'onPostSuccess', callback: (args) async {
                    if (mounted) Navigator.pop(context);
                  });
                },
                onReceivedError: (controller, request, error) {},
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============ Halaman PPOB — WebView ke https://ridesip.my.id/ppob/ ============

class PpobWebViewPage extends StatefulWidget {
  const PpobWebViewPage({super.key});

  @override
  State<PpobWebViewPage> createState() => _PpobWebViewPageState();
}

class _PpobWebViewPageState extends State<PpobWebViewPage> {
  String? _url;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  Future<void> _boot() async {
    final user = await Session.load();
    if (!mounted) return;
    if (user == null || user['id'] == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Silakan login terlebih dahulu untuk membuka PPOB.')),
      );
      Navigator.pop(context);
      return;
    }
    final uid = user['id'].toString();
    try {
      final res = await http.get(Uri.parse('$kApiBase/ppob/webview-token?user_id=$uid')).timeout(const Duration(seconds: 10));
      final data = jsonDecode(res.body);
      final token = data['data']?['token'];
      if (res.statusCode != 200 || token == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Gagal menyiapkan sesi PPOB: ${data['message'] ?? 'server error'}')),
          );
          Navigator.pop(context);
        }
        return;
      }
      final name = (data['data']['full_name'] ?? '').toString();
      final phone = (data['data']['phone'] ?? '').toString();
      if (mounted) {
        setState(() => _url = 'https://ridesip.my.id/ppob/?session_token=$token&user_id=$uid&name=${Uri.encodeComponent(name)}&phone=${Uri.encodeComponent(phone)}');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Koneksi gagal: $e')));
        Navigator.pop(context);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_url == null) {
      return Scaffold(
        backgroundColor: Colors.teal,
        body: const Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }
    return Scaffold(
      backgroundColor: Colors.teal,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              color: Colors.teal,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white, size: 26),
                    onPressed: () => Navigator.pop(context),
                  ),
                  const Expanded(
                    child: Text('PPOB & Pulsa', textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),
            Expanded(
              child: InAppWebView(
                initialUrlRequest: URLRequest(url: WebUri(_url!)),
                initialSettings: InAppWebViewSettings(
                  javaScriptEnabled: true,
                  domStorageEnabled: true,
                  useHybridComposition: true,
                  supportMultipleWindows: false,
                  userAgent: 'RideSipDriverApp/1.0',
                  javaScriptCanOpenWindowsAutomatically: false,
                ),
                onLoadStop: (controller, url) {
                  controller.addJavaScriptHandler(handlerName: 'onTransactionSuccess', callback: (args) async {
                    if (mounted) Navigator.pop(context);
                  });
                },
                onReceivedError: (controller, request, error) {},
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============ Halaman Kendaraan Saya — multi-kendaraan per driver ============

class KendaraanPage extends StatefulWidget {
  const KendaraanPage({super.key});

  @override
  State<KendaraanPage> createState() => _KendaraanPageState();
}

class _KendaraanPageState extends State<KendaraanPage> {
  static const List<String> kJenis = ['MOTOR', 'MOBIL', 'BAJAJ', 'TRUK', 'PICKUP_TERBUKA', 'PICKUP_BOX'];

  static const Map<String, IconData> kJenisIcons = {
    'MOTOR': Icons.two_wheeler,
    'MOBIL': Icons.directions_car,
    'BAJAJ': Icons.airline_seat_recline_normal,
    'TRUK': Icons.local_shipping,
    'PICKUP_TERBUKA': Icons.local_shipping_outlined,
    'PICKUP_BOX': Icons.inventory_2,
  };

  static const Map<String, String> kJenisLabel = {
    'MOTOR': 'Motor',
    'MOBIL': 'Mobil',
    'BAJAJ': 'Bajaj',
    'TRUK': 'Truk',
    'PICKUP_TERBUKA': 'Pickup Terbuka',
    'PICKUP_BOX': 'Pickup Box',
  };

  List<Map<String, dynamic>> _vehicles = [];
  bool _loading = true;
  String? _error;
  int? _userId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final user = await Session.load();
    _userId = user != null ? user['id'] : null;
    if (_userId == null) {
      setState(() {
        _loading = false;
        _error = 'Silakan login terlebih dahulu.';
      });
      return;
    }
    try {
      final res = await http
          .get(Uri.parse('$kApiBase/driver/kendaraan?user_id=$_userId'))
          .timeout(const Duration(seconds: 10));
      final data = jsonDecode(res.body);
      if (res.statusCode == 200) {
        final list = data['data'];
        setState(() {
          _vehicles = list is List ? list.map((e) => Map<String, dynamic>.from(e)).toList() : <Map<String, dynamic>>[];
          _loading = false;
        });
      } else {
        setState(() {
          _loading = false;
          _error = data['message'] ?? 'Gagal memuat kendaraan.';
        });
      }
    } catch (e) {
      setState(() {
        _loading = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  Future<void> _toggleAktif(Map<String, dynamic> v, bool aktif) async {
    final id = v['id'];
    final res = await http
        .patch(Uri.parse('$kApiBase/driver/kendaraan/$id/toggle-aktif?user_id=$_userId'),
            headers: const {'Content-Type': 'application/json'},
            body: jsonEncode({'is_aktif': aktif}))
        .timeout(const Duration(seconds: 10));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
      await _load();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Gagal mengubah status kendaraan.')),
        );
      }
    }
  }

  Future<void> _setDefaults(Map<String, dynamic> v) async {
    final id = v['id'];
    final res = await http
        .patch(Uri.parse('$kApiBase/driver/kendaraan/$id/set-default?user_id=$_userId'),
            headers: const {'Content-Type': 'application/json'},
            body: jsonEncode({}))
        .timeout(const Duration(seconds: 10));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
      await _load();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Gagal mengatur kendaraan utama.')),
        );
      }
    }
  }

  Future<void> _hapus(Map<String, dynamic> v) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus kendaraan?'),
        content: Text('Kendaraan ${v['jenis_kendaraan']} ${v['plat_nomor']} akan dihapus (soft delete). '
            'Kendaraan yang sedang ada order berjalan tidak dapat dihapus.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Hapus', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    final res = await http
        .delete(Uri.parse('$kApiBase/driver/kendaraan/${v['id']}?user_id=$_userId'))
        .timeout(const Duration(seconds: 10));
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
      await _load();
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(data['message'] ?? 'Gagal menghapus kendaraan.')),
        );
      }
    }
  }

  void _bukaForm(Map<String, dynamic>? existing) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => KendaraanFormPage(kendaraan: existing)),
    ).then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Kendaraan Saya'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      floatingActionButton: _userId != null && !_loading
          ? FloatingActionButton(
              backgroundColor: Colors.teal,
              foregroundColor: Colors.white,
              onPressed: () => _bukaForm(null),
              child: const Icon(Icons.add),
            )
          : null,
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, textAlign: TextAlign.center))
              : _vehicles.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.car_rental, size: 64, color: Colors.black26),
                            const SizedBox(height: 16),
                            const Text('Belum ada kendaraan.', style: TextStyle(fontSize: 16, color: Colors.black54)),
                            const SizedBox(height: 24),
                            ElevatedButton.icon(
                              style: ElevatedButton.styleFrom(backgroundColor: Colors.teal, foregroundColor: Colors.white),
                              onPressed: () => _bukaForm(null),
                              icon: const Icon(Icons.add),
                              label: const Text('Tambah Kendaraan'),
                            ),
                          ],
                        ),
                      ),
                    )
                  : RefreshIndicator(onRefresh: _load, child: _buildList()),
    );
  }

  Widget _buildList() {
    final byJenis = <String, List<Map<String, dynamic>>>{};
    for (final v in _vehicles) {
      final j = v['jenis_kendaraan'].toString();
      byJenis.putIfAbsent(j, () => []).add(v);
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Colors.teal.withOpacity(0.08), borderRadius: BorderRadius.circular(10)),
          child: const Text(
            'Status AKTIF menentukan kendaraan mana yang muncul saat Anda mengajukan bid. '
            'Maksimal 1 kendaraan aktif untuk setiap jenis kendaraan.',
            style: const TextStyle(fontSize: 12, color: Colors.black54),
          ),
        ),
        const SizedBox(height: 12),
        ...kJenis.where(byJenis.containsKey).expand((j) => [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(kJenisLabel[j] ?? j, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.black54)),
              ),
              ...byJenis[j]!.map((v) => _vehicleCard(v)),
            ]),
      ],
    );
  }

  Widget _vehicleCard(Map<String, dynamic> v) {
    final jenis = v['jenis_kendaraan'].toString();
    final aktif = v['is_aktif'] == true;
    final isDefault = v['is_default'] == true;
    final status = v['status_verifikasi']?.toString() ?? 'approved';
    final verified = status == 'approved';
    final pending = status == 'pending';
    return Card(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: (verified ? Colors.green : pending ? Colors.orange : Colors.red).withOpacity(0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(kJenisIcons[jenis] ?? Icons.car_rental,
                      color: verified ? Colors.green : pending ? Colors.orange : Colors.red),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        v['plat_nomor']?.toString().toUpperCase() ?? '-',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 2),
                      Text(_infoLine(v), style: const TextStyle(fontSize: 12, color: Colors.black54)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: verified ? Colors.green.withOpacity(0.12) : pending ? Colors.orange.withOpacity(0.12) : Colors.red.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(99),
                  ),
                  child: Text(
                    verified ? 'Terverifikasi' : pending ? 'Menunggu' : 'Ditolak',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: verified ? Colors.green.shade700 : pending ? Colors.orange.shade700 : Colors.red.shade700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Text('Aktif untuk order', style: TextStyle(fontSize: 12, color: aktif ? Colors.green.shade700 : Colors.black54)),
                const Spacer(),
                Switch(
                  value: aktif,
                  activeColor: Colors.teal,
                  onChanged: (val) => _toggleAktif(v, val),
                ),
              ],
            ),
            const Divider(height: 16),
            Row(
              children: [
                IconButton(
                  icon: Icon(isDefault ? Icons.star : Icons.star_border,
                      color: isDefault ? Colors.amber : Colors.black38, size: 22),
                  onPressed: isDefault ? null : () => _setDefaults(v),
                  tooltip: isDefault ? 'Kendaraan utama' : 'Jadikan kendaraan utama',
                ),
                Text(isDefault ? 'Kendaraan utama' : '', style: const TextStyle(fontSize: 12, color: Colors.amber)),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.delete_outline, color: Colors.red, size: 22),
                  tooltip: 'Hapus kendaraan',
                  onPressed: () => _hapus(v),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _infoLine(Map<String, dynamic> v) {
    final parts = <String>[];
    if (v['merk'] != null && v['merk'].toString().isNotEmpty) parts.add(v['merk'].toString());
    if (v['model'] != null && v['model'].toString().isNotEmpty) parts.add(v['model'].toString());
    if (v['tahun'] != null && v['tahun'].toString().isNotEmpty) parts.add(v['tahun'].toString());
    if (v['warna'] != null && v['warna'].toString().isNotEmpty) parts.add(v['warna'].toString());
    return parts.isEmpty ? 'Tidak ada detail tambahan' : parts.join(' • ');
  }
}

// ============ Form Tambah / Edit Kendaraan ============

class KendaraanFormPage extends StatefulWidget {
  final Map<String, dynamic>? kendaraan;
  const KendaraanFormPage({super.key, this.kendaraan});

  @override
  State<KendaraanFormPage> createState() => _KendaraanFormPageState();
}

class _KendaraanFormPageState extends State<KendaraanFormPage> {
  final _formKey = GlobalKey<FormState>();
  String _jenis = 'MOTOR';
  final _platCtrl = TextEditingController();
  final _merkCtrl = TextEditingController();
  final _modelCtrl = TextEditingController();
  final _tahunCtrl = TextEditingController();
  final _warnaCtrl = TextEditingController();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final k = widget.kendaraan;
    if (k != null) {
      _jenis = k['jenis_kendaraan']?.toString() ?? 'MOTOR';
      _platCtrl.text = k['plat_nomor']?.toString() ?? '';
      _merkCtrl.text = k['merk']?.toString() ?? '';
      _modelCtrl.text = k['model']?.toString() ?? '';
      _tahunCtrl.text = k['tahun']?.toString() ?? '';
      _warnaCtrl.text = k['warna']?.toString() ?? '';
    }
  }

  Future<void> _simpan() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final user = await Session.load();
    final uid = user?['id'];
    if (uid == null) {
      setState(() => _saving = false);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Silakan login terlebih dahulu.')));
      return;
    }
    final body = {
      'user_id': uid,
      'jenis_kendaraan': _jenis,
      'plat_nomor': _platCtrl.text.trim(),
      'merk': _merkCtrl.text.trim(),
      'model': _modelCtrl.text.trim(),
      'tahun': _tahunCtrl.text.trim(),
      'warna': _warnaCtrl.text.trim(),
    };
    final isEdit = widget.kendaraan != null;
    final url = isEdit ? '$kApiBase/driver/kendaraan/${widget.kendaraan!['id']}' : '$kApiBase/driver/kendaraan';
    try {
      final res = await (isEdit
              ? http.put(Uri.parse(url), headers: const {'Content-Type': 'application/json'}, body: jsonEncode(body))
              : http.post(Uri.parse(url), headers: const {'Content-Type': 'application/json'}, body: jsonEncode(body)))
          .timeout(const Duration(seconds: 15));
      final data = jsonDecode(res.body);
      if (res.statusCode == 200) {
        if (mounted) Navigator.pop(context, true);
      } else {
        if (mounted) {
          setState(() => _saving = false);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'Gagal menyimpan kendaraan.')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Koneksi gagal: $e')));
      }
    }
  }

  @override
  void dispose() {
    _platCtrl.dispose();
    _merkCtrl.dispose();
    _modelCtrl.dispose();
    _tahunCtrl.dispose();
    _warnaCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.kendaraan != null;
    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Kendaraan' : 'Tambah Kendaraan'),
        backgroundColor: Colors.teal,
        foregroundColor: Colors.white,
      ),
      body: _saving
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  const Text('Jenis Kendaraan', style: TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _kendaraanFormJenis.entries.map((entry) {
                      final sel = _jenis == entry.key;
                      return ChoiceChip(
                        selected: sel,
                        label: Text(entry.value),
                        selectedColor: Colors.teal,
                        backgroundColor: Colors.grey.shade100,
                        labelStyle: TextStyle(color: sel ? Colors.white : Colors.black87, fontWeight: sel ? FontWeight.bold : FontWeight.normal),
                        onSelected: (s) {
                          if (s) setState(() => _jenis = entry.key);
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 18),
                  _field('Nomor Plat', _platCtrl, 'Contoh: AG 1234 ZZ', mandatory: true),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: _field('Merk', _merkCtrl, 'Contoh: Honda')),
                      const SizedBox(width: 12),
                      Expanded(child: _field('Model', _modelCtrl, 'Contoh: Beat')),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(child: _field('Tahun', _tahunCtrl, 'Contoh: 2022')),
                      const SizedBox(width: 12),
                      Expanded(child: _field('Warna', _warnaCtrl, 'Contoh: Hitam')),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text('Upload foto kendaraan & STNK belum tersedia di aplikasi, hubungi admin bila diperlukan.',
                      style: TextStyle(fontSize: 11, color: Colors.black45)),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.teal,
                      foregroundColor: Colors.white,
                      minimumSize: const Size.fromHeight(48),
                    ),
                    onPressed: _simpan,
                    child: Text(isEdit ? 'Simpan Perubahan' : 'Tambah Kendaraan'),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _field(String label, TextEditingController ctrl, String hint, {bool mandatory = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          decoration: InputDecoration(
            hintText: hint,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          ),
          validator: mandatory ? (v) => (v == null || v.trim().isEmpty) ? '$label wajib diisi' : null : null,
        ),
      ],
    );
  }
}

// Konstanta jenis kendaraan untuk form
const Map<String, String> _kendaraanFormJenis = {
  'MOTOR': 'Motor',
  'MOBIL': 'Mobil',
  'BAJAJ': 'Bajaj',
  'TRUK': 'Truk',
  'PICKUP_TERBUKA': 'Pickup Terbuka',
  'PICKUP_BOX': 'Pickup Box',
};
