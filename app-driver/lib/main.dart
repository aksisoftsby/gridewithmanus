import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  runApp(const DriverApp());
}

const String kApiBase = 'https://gride.web.id/api';

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gride Driver',
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
  static const String _key = 'gride_driver_user';

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

  @override
  void initState() {
    super.initState();
    _loadUser().then((_) => fetchOrders());
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
        title: const Text('Gride Driver'),
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
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
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
        if ((data['role'] ?? '').toString() != 'DRIVER') {
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
          const Text('Bergabung sebagai driver Gride dan mulai dapatkan penghasilan.',
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
          const SizedBox(height: 10),

          // Kendaraan
          if (vehicle != null)
            Card(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: const Icon(Icons.car_rental, color: Colors.teal),
                title: Text('Kendaraan: ${(vehicle['vehicle_type'] ?? '').toString().toUpperCase()}'),
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
