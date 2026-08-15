import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:shared_preferences/shared_preferences.dart';

const String kApiBase = 'https://ridesip.my.id/api';

String formatRp(int value) =>
    'Rp ${value.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}';

class Session {
  static const String _key = 'ridesip_merchant_user';
  static Map<String, dynamic>? _user;
  static Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw != null) {
      try {
        _user = Map<String, dynamic>.from(jsonDecode(raw));
      } catch (_) {}
    }
  }

  static Future<void> save(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    _user = user;
    await prefs.setString(_key, jsonEncode(user));
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
    _user = null;
  }

  static Map<String, dynamic>? get user => _user;
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Session.load();
  runApp(const MerchantApp());
}

class MerchantApp extends StatelessWidget {
  const MerchantApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'RideSip Merchant',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepOrange),
        useMaterial3: true,
      ),
      home: const MerchantRoot(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class MerchantRoot extends StatefulWidget {
  const MerchantRoot({super.key});

  @override
  State<MerchantRoot> createState() => _MerchantRootState();
}

class _MerchantRootState extends State<MerchantRoot> {
  @override
  Widget build(BuildContext context) {
    final user = Session.user;
    if (user != null && user['role'] == 'MEMBER') {
      return const MerchantHome();
    }
    return const MerchantAuthPage();
  }
}

// ================= AUTH PAGE =================

class MerchantAuthPage extends StatefulWidget {
  const MerchantAuthPage({super.key});

  @override
  State<MerchantAuthPage> createState() => _MerchantAuthPageState();
}

class _MerchantAuthPageState extends State<MerchantAuthPage> {
  bool isLogin = true;
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _mnameCtrl = TextEditingController();
  String? _error;
  bool _busy = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _passCtrl.dispose();
    _mnameCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _error = null;
      _busy = true;
    });
    try {
      if (isLogin) {
        final res = await http.post(
          Uri.parse('$kApiBase/login'),
          headers: const {'Content-Type': 'application/json'},
          body: jsonEncode({
            'email': _emailCtrl.text.trim(),
            'password': _passCtrl.text,
          }),
        );
        final data = jsonDecode(res.body);
        if (res.statusCode == 200) {
          final user = Map<String, dynamic>.from(data['data']);
          if (user['role'] != 'MEMBER') {
            setState(() {
              _busy = false;
              _error = 'Akun ini bukan akun Merchant (role: ${user['role']}). Gunakan aplikasi Customer atau Driver.';
            });
            return;
          }
          final me = await http.get(Uri.parse('$kApiBase/merchant/me?user_id=${user['id']}'));
          if (me.statusCode != 200) {
            setState(() {
              _busy = false;
              _error = 'Akun ini belum memiliki toko. Daftar sebagai Merchant terlebih dahulu.';
            });
            return;
          }
          final merchant = jsonDecode(me.body)['data']['merchant'];
          user['merchant_id'] = merchant['id'];
          user['merchant_name'] = merchant['name'];
          await Session.save(user);
          if (mounted) setState(() => _busy = false);
          return;
        }
        setState(() {
          _busy = false;
          _error = data['message'] ?? 'Login gagal.';
        });
      } else {
        final res = await http.post(
          Uri.parse('$kApiBase/register-merchant'),
          headers: const {'Content-Type': 'application/json'},
          body: jsonEncode({
            'full_name': _nameCtrl.text.trim(),
            'email': _emailCtrl.text.trim(),
            'phone': _phoneCtrl.text.trim().isEmpty ? null : _phoneCtrl.text.trim(),
            'password': _passCtrl.text,
            'merchant_name': _mnameCtrl.text.trim(),
            'merchant_type': 'FOOD',
            'merchant_city': 'Kediri',
          }),
        );
        final data = jsonDecode(res.body);
        setState(() {
          _busy = false;
          if (res.statusCode == 201) {
            _error = 'Registrasi berhasil! Silakan login dengan email & password Anda.';
            isLogin = true;
          } else {
            _error = data['message'] ?? 'Registrasi gagal.';
          }
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
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 40),
              Icon(Icons.storefront, size: 72, color: Colors.deepOrange.shade600),
              const SizedBox(height: 12),
              const Text('RideSip Merchant', textAlign: TextAlign.center, style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              Text(isLogin ? 'Masuk ke akun toko Anda' : 'Daftar jadi mitra toko RideSip', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)),
              const SizedBox(height: 28),
              Row(
                children: [
                  Expanded(
                    child: TextButton(
                      style: TextButton.styleFrom(foregroundColor: isLogin ? Colors.white : Colors.deepOrange.shade700, backgroundColor: isLogin ? Colors.deepOrange : Colors.deepOrange.shade50),
                      onPressed: () => setState(() => isLogin = true),
                      child: const Text('Login', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextButton(
                      style: TextButton.styleFrom(foregroundColor: isLogin ? Colors.deepOrange.shade700 : Colors.white, backgroundColor: isLogin ? Colors.deepOrange.shade50 : Colors.deepOrange),
                      onPressed: () => setState(() => isLogin = false),
                      child: const Text('Daftar', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (!isLogin) ...[
                      _field(_nameCtrl, 'Nama Lengkap', Icons.person),
                      const SizedBox(height: 12),
                      _field(_mnameCtrl, 'Nama Toko', Icons.store, validator: (v) => v == null || v.trim().isEmpty ? 'Nama toko wajib diisi' : null),
                      const SizedBox(height: 12),
                    ],
                    _field(_emailCtrl, 'Email', Icons.email, validator: (v) => (v == null || v.trim().isEmpty) ? 'Email wajib diisi' : (!v.contains('@') ? 'Format email salah' : null)),
                    const SizedBox(height: 12),
                    _field(_phoneCtrl, 'No HP (opsional)', Icons.phone),
                    const SizedBox(height: 12),
                    _field(_passCtrl, 'Password (min 6 karakter)', Icons.lock, obscure: true, validator: (v) => (v == null || v.length < 6) ? 'Password min 6 karakter' : null),
                    const SizedBox(height: 20),
                    if (_error != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.red.shade200)),
                        child: Text(_error!, style: TextStyle(color: Colors.red.shade800, fontSize: 13)),
                      ),
                      const SizedBox(height: 12),
                    ],
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: Colors.deepOrange, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14)),
                      onPressed: _busy ? null : _submit,
                      child: _busy ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text(isLogin ? 'Login' : 'Daftar Sekarang', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(TextEditingController ctrl, String label, IconData icon, {bool obscure = false, String? Function(String?)? validator}) {
    return TextFormField(
      controller: ctrl,
      obscureText: obscure,
      keyboardType: icon == Icons.phone ? TextInputType.phone : (label.toLowerCase().contains('email') ? TextInputType.emailAddress : null),
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon, color: Colors.deepOrange.shade400), border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)), filled: true, fillColor: Colors.grey.shade100),
      validator: validator ?? (v) => v == null || v.trim().isEmpty ? '$label wajib diisi' : null,
    );
  }
}

// ================= MERCHANT HOME (4 TABS) =================

class MerchantHome extends StatefulWidget {
  const MerchantHome({super.key});

  @override
  State<MerchantHome> createState() => _MerchantHomeState();
}

class _MerchantHomeState extends State<MerchantHome> {
  int _tab = 0;
  Map<String, dynamic>? merchant;

  @override
  void initState() {
    super.initState();
    _loadMerchant();
  }

  Future<void> _loadMerchant() async {
    final user = Session.user;
    if (user == null) return;
    try {
      final res = await http.get(Uri.parse('$kApiBase/merchant/me?user_id=${user['id']}'));
      if (res.statusCode == 200) {
        setState(() => merchant = Map<String, dynamic>.from(jsonDecode(res.body)['data']['merchant']));
      }
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _tab,
        children: [
          MerchantOrdersPage(refreshMerchant: _loadMerchant),
          MerchantProductsPage(),
          const MerchantAkunPage(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        destinations: const [
          NavigationDestination(icon: Icon(Icons.receipt_long), label: 'Orderan'),
          NavigationDestination(icon: Icon(Icons.inventory_2), label: 'Produk'),
          NavigationDestination(icon: Icon(Icons.account_circle), label: 'Akun'),
        ],
      ),
    );
  }
}

// ================= ORDERAN PAGE =================

class MerchantOrdersPage extends StatefulWidget {
  final VoidCallback refreshMerchant;
  const MerchantOrdersPage({super.key, required this.refreshMerchant});

  @override
  State<MerchantOrdersPage> createState() => _MerchantOrdersPageState();
}

class _MerchantOrdersPageState extends State<MerchantOrdersPage> {
  List orders = [];
  bool isLoading = true;
  String? error;

  Future<void> fetchOrders() async {
    final user = Session.user;
    if (user == null) return;
    setState(() {
      isLoading = true;
      error = null;
    });
    try {
      final res = await http.get(Uri.parse('$kApiBase/orders?merchant_id=${user['merchant_id'] ?? ''}'));
      if (res.statusCode == 200) {
        setState(() {
          orders = jsonDecode(res.body)['data'];
          isLoading = false;
        });
      } else {
        setState(() {
          error = 'Gagal memuat orderan.';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        error = 'Koneksi gagal: $e';
        isLoading = false;
      });
    }
  }

  String _statusLabel(String? s) {
    switch ((s ?? '').toUpperCase()) {
      case 'PENDING': return 'Menunggu';
      case 'ACCEPTED': return 'Diterima';
      case 'PROCESSING': return 'Diproses';
      case 'ON_DELIVERY': return 'Dikirim';
      case 'COMPLETED': return 'Selesai';
      case 'CANCELLED': return 'Dibatalkan';
      default: return s ?? '-';
    }
  }

  Color _statusColor(String? s) {
    switch ((s ?? '').toUpperCase()) {
      case 'PENDING': return Colors.orange;
      case 'COMPLETED': return Colors.green;
      case 'CANCELLED': return Colors.red;
      default: return Colors.blue;
    }
  }

  @override
  void initState() {
    super.initState();
    fetchOrders();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Orderan Toko'), backgroundColor: Colors.deepOrange, foregroundColor: Colors.white, actions: [
        IconButton(
          icon: const Icon(Icons.refresh),
          onPressed: fetchOrders,
        ),
      ]),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(child: Text(error!))
              : RefreshIndicator(
                  onRefresh: fetchOrders,
                  child: orders.isEmpty
                      ? const Center(child: Text('Belum ada orderan untuk toko Anda.'))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: orders.length,
                          itemBuilder: (context, index) {
                            final o = orders[index];
                            final inv = o['invoice'] is Map ? Map<String, dynamic>.from(o['invoice']) : null;
                            final merchantNet = (inv?['merchant_net'] ?? 0).round();
                            final total = (inv?['total'] ?? o['total_amount'] ?? 0).round();
                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              child: InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () => _showInvoice(context, o, inv),
                                child: Padding(
                                  padding: const EdgeInsets.all(14),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text(o['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                                          Chip(label: Text(_statusLabel(o['status']), style: const TextStyle(color: Colors.white, fontSize: 11)), backgroundColor: _statusColor(o['status']), padding: EdgeInsets.zero, visualDensity: VisualDensity.compact),
                                        ],
                                      ),
                                      const SizedBox(height: 8),
                                      Text('${o['order_type'] ?? ''} • ${o['created_at'] ?? ''}', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                                      const SizedBox(height: 6),
                                      Text(o['delivery_address'] ?? o['dropoff_address'] ?? '', style: const TextStyle(fontSize: 13)),
                                      const Divider(height: 16),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text('Total pesanan', style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
                                          Text(formatRp(total), style: const TextStyle(fontWeight: FontWeight.bold)),
                                        ],
                                      ),
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text('Pendapatan toko', style: TextStyle(fontSize: 13, color: Colors.deepOrange.shade700)),
                                          Text(formatRp(merchantNet), style: TextStyle(fontWeight: FontWeight.bold, color: Colors.deepOrange.shade800)),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
    );
  }

  void _showInvoice(BuildContext context, Map<String, dynamic> o, Map<String, dynamic>? inv) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(o['order_number'] ?? 'Detail'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              _row('Status', _statusLabel(o['status'])),
              _row('Tipe', o['order_type'] ?? '-'),
              _row('Pelanggan', '${o['recipient_name'] ?? '-'} • ${o['recipient_phone'] ?? '-'}'),
              const Divider(),
              if (inv != null)
                ...inv.entries.map((e) => _row(e.key.toString().replaceAll('_', ' '), e.value.toString())),
            ],
          ),
        ),
        actions: [TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Tutup'))],
      ),
    );
  }

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text(label, style: TextStyle(fontSize: 13, color: Colors.grey.shade700)),
          Flexible(child: Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500), textAlign: TextAlign.end)),
        ]),
      );
}

// ================= PRODUK PAGE =================

class MerchantProductsPage extends StatefulWidget {
  const MerchantProductsPage({super.key});

  @override
  State<MerchantProductsPage> createState() => _MerchantProductsPageState();
}

class _MerchantProductsPageState extends State<MerchantProductsPage> {
  List products = [];
  bool isLoading = true;

  Future<void> fetchProducts() async {
    final user = Session.user;
    if (user == null) return;
    setState(() => isLoading = true);
    try {
      final res = await http.get(Uri.parse('$kApiBase/products?merchant_id=${user['merchant_id'] ?? ''}'));
      if (res.statusCode == 200) {
        setState(() {
          products = jsonDecode(res.body)['data'];
          isLoading = false;
        });
      }
    } catch (_) {
      setState(() => isLoading = false);
    }
  }

  @override
  void initState() {
    super.initState();
    fetchProducts();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Produk Toko'), backgroundColor: Colors.deepOrange, foregroundColor: Colors.white),
      floatingActionButton: FloatingActionButton(
        backgroundColor: Colors.deepOrange,
        foregroundColor: Colors.white,
        onPressed: () => _openForm(context, null),
        child: const Icon(Icons.add),
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: fetchProducts,
              child: products.isEmpty
                  ? const Center(child: Padding(padding: EdgeInsets.all(32), child: Text('Belum ada produk.\nKlik tombol + untuk menambah produk.')))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: products.length,
                      itemBuilder: (context, index) {
                        final p = products[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(12),
                            leading: p['image_url'] != null && (p['image_url'] as String).isNotEmpty
                                ? ClipRRect(borderRadius: BorderRadius.circular(8), child: Image.network(p['image_url'], width: 56, height: 56, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox(width: 56, height: 56)))
                                : Container(width: 56, height: 56, decoration: BoxDecoration(color: Colors.deepOrange.shade100, borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.fastfood, color: Colors.deepOrange)),
                            title: Text(p['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Text((p['description'] ?? '').toString().isEmpty ? '-' : p['description'], maxLines: 1, overflow: TextOverflow.ellipsis),
                            trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                              Text(formatRp((double.tryParse((p['price'] ?? '0').toString()) ?? 0).round()), style: TextStyle(fontWeight: FontWeight.bold, color: Colors.deepOrange.shade800)),
                              const SizedBox(width: 8),
                              IconButton(icon: const Icon(Icons.edit, color: Colors.blue), iconSize: 20, onPressed: () => _openForm(context, p)),
                            ]),
                            onTap: () => _openForm(context, p),
                          ),
                        );
                      },
                    ),
            ),
    );
  }

  void _openForm(BuildContext context, Map<String, dynamic>? product) {
    showDialog(
      context: context,
      builder: (ctx) => ProductFormDialog(
        product: product,
        onSaved: () {
          Navigator.pop(ctx);
          fetchProducts();
        },
      ),
    );
  }
}

class ProductFormDialog extends StatefulWidget {
  final Map<String, dynamic>? product;
  final VoidCallback onSaved;
  const ProductFormDialog({super.key, this.product, required this.onSaved});

  @override
  State<ProductFormDialog> createState() => _ProductFormDialogState();
}

class _ProductFormDialogState extends State<ProductFormDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameCtrl;
  late final TextEditingController _descCtrl;
  late final TextEditingController _priceCtrl;
  late final TextEditingController _imgCtrl;
  late bool _available;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    _nameCtrl = TextEditingController(text: p?['name'] ?? '');
    _descCtrl = TextEditingController(text: (p?['description'] ?? '').toString() == 'null' ? '' : (p?['description'] ?? ''));
    _priceCtrl = TextEditingController(text: p != null ? (double.tryParse((p['price'] ?? '0').toString()) ?? 0).round().toString() : '');
    _imgCtrl = TextEditingController(text: (p?['image_url'] ?? '').toString() == 'null' ? '' : (p?['image_url'] ?? ''));
    _available = p == null || p['is_available'] == true || p['is_available'] == 1 || p['is_available'] == 'true';
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _priceCtrl.dispose();
    _imgCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    final user = Session.user!;
    final body = {
      'user_id': user['id'],
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'price': double.tryParse(_priceCtrl.text.trim()) ?? 0,
      'image_url': _imgCtrl.text.trim().isEmpty ? null : _imgCtrl.text.trim(),
      'is_available': _available,
    };
    try {
      http.Response res;
      if (widget.product != null) {
        res = await http.put(Uri.parse('$kApiBase/products/${widget.product!['id']}'), headers: const {'Content-Type': 'application/json'}, body: jsonEncode(body));
      } else {
        body['merchant_id'] = user['merchant_id'];
        res = await http.post(Uri.parse('$kApiBase/products'), headers: const {'Content-Type': 'application/json'}, body: jsonEncode(body));
      }
      final data = jsonDecode(res.body);
      setState(() {
        _busy = false;
        if (res.statusCode == 200 || res.statusCode == 201) {
          widget.onSaved();
        } else {
          _error = data['message'] ?? 'Gagal menyimpan produk.';
        }
      });
    } catch (e) {
      setState(() {
        _busy = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.product == null ? 'Tambah Produk' : 'Edit Produk'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'Nama produk'), validator: (v) => v == null || v.trim().isEmpty ? 'Nama wajib' : null),
              const SizedBox(height: 10),
              TextFormField(controller: _descCtrl, decoration: const InputDecoration(labelText: 'Deskripsi'), maxLines: 2),
              const SizedBox(height: 10),
              TextFormField(controller: _priceCtrl, decoration: const InputDecoration(labelText: 'Harga (Rp)'), keyboardType: TextInputType.number, validator: (v) => v == null || v.trim().isEmpty ? 'Harga wajib' : null),
              const SizedBox(height: 10),
              TextFormField(controller: _imgCtrl, decoration: const InputDecoration(labelText: 'URL gambar (opsional)')),
              const SizedBox(height: 10),
              Row(children: [
                const Text('Tersedia dijual'),
                const Spacer(),
                Switch(value: _available, onChanged: (v) => setState(() => _available = v), activeColor: Colors.deepOrange),
              ]),
              if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: TextStyle(color: Colors.red.shade700, fontSize: 12))),
              const SizedBox(height: 8),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.deepOrange, foregroundColor: Colors.white),
                onPressed: _busy ? null : _submit,
                child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text(widget.product == null ? 'Simpan Produk' : 'Update Produk'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ================= AKUN PAGE =================

class MerchantAkunPage extends StatefulWidget {
  const MerchantAkunPage({super.key});

  @override
  State<MerchantAkunPage> createState() => _MerchantAkunPageState();
}

class _MerchantAkunPageState extends State<MerchantAkunPage> {
  Map<String, dynamic>? merchant;
  Map<String, dynamic>? earnings;
  bool isLoading = true;
  String? error;

  Future<void> _loadAll() async {
    final user = Session.user;
    if (user == null) return;
    setState(() {
      isLoading = true;
      error = null;
    });
    try {
      final me = await http.get(Uri.parse('$kApiBase/merchant/me?user_id=${user['id']}'));
      final earn = await http.get(Uri.parse('$kApiBase/merchant/earnings?user_id=${user['id']}'));
      setState(() {
        if (me.statusCode == 200) merchant = Map<String, dynamic>.from(jsonDecode(me.body)['data']['merchant']);
        if (earn.statusCode == 200) earnings = jsonDecode(earn.body)['data'];
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        error = 'Koneksi gagal: $e';
        isLoading = false;
      });
    }
  }

  Future<void> _logout() async {
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(title: const Text('Logout'), content: const Text('Keluar dari akun merchant?'), actions: [
      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
      TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Logout', style: TextStyle(color: Colors.red))),
    ]));
    if (ok == true) {
      await Session.clear();
      if (mounted) {
        Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const MerchantAuthPage()));
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  @override
  Widget build(BuildContext context) {
    final user = Session.user;
    return Scaffold(
      appBar: AppBar(title: const Text('Akun Merchant'), backgroundColor: Colors.deepOrange, foregroundColor: Colors.white, actions: [
        IconButton(icon: const Icon(Icons.refresh), onPressed: _loadAll),
      ]),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(child: Text(error!))
              : RefreshIndicator(
                  onRefresh: _loadAll,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      // ---- Profil ----
                      Card(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Row(children: [
                              const CircleAvatar(radius: 28, backgroundColor: Colors.deepOrange, child: Icon(Icons.storefront, color: Colors.white, size: 30)),
                              const SizedBox(width: 12),
                              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text(user?['full_name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17)),
                                Text(user?['email'] ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                              ])),
                              IconButton(icon: const Icon(Icons.logout, color: Colors.red), onPressed: _logout, tooltip: 'Logout'),
                            ]),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 12),
                      // ---- Info toko ----
                      Card(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                              const Text('Info Toko', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                              TextButton.icon(icon: const Icon(Icons.edit, size: 18), label: const Text('Update'), onPressed: () => _openMerchantInfoForm()),
                            ]),
                            const Divider(height: 12),
                            _info('Nama toko', merchant?['name']),
                            _info('Tipe', merchant?['type']),
                            _info('Deskripsi', merchant?['description']),
                            _info('Telepon', merchant?['phone']),
                            _info('Alamat', merchant?['address_line']),
                            _info('Kota', merchant?['city']),
                            _info('Status', merchant?['is_open'] == true ? 'Buka' : 'Tutup'),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 12),
                      // ---- Saldo ----
                      Card(
                        color: Colors.green.shade50,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            const Text('Saldo Toko', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.green)),
                            const Divider(height: 14),
                            _moneyRow('Pendapatan sudah cair', (earnings?['total_earned'] ?? 0).round(), Colors.green.shade800),
                            const SizedBox(height: 8),
                            _moneyRow('Menunggu cair', (earnings?['total_pending'] ?? 0).round(), Colors.orange.shade800),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 12),
                      // ---- PPOB ----
                      Card(
                        color: Colors.green.shade50,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            const Text('PPOB & Pulsa', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.green)),
                            const Divider(height: 14),
                            const Text('Beli pulsa, paket data, token PLN, voucher game, dan bayar tagihan langsung dari aplikasi.', style: TextStyle(fontSize: 13)),
                            const SizedBox(height: 10),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                icon: const Icon(Icons.bolt, size: 18),
                                label: const Text('Buka PPOB'),
                                style: FilledButton.styleFrom(backgroundColor: Colors.green, padding: const EdgeInsets.symmetric(vertical: 12)),
                                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PpobWebViewPage())),
                      const SizedBox(height: 12),
                      // ---- Iklan Gratis ----
                      Card(
                        color: const Color(0xFFFCE7F3),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            const Text('Iklan Gratis', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFFD8006B))),
                            const Divider(height: 14),
                            const Text('Pasang iklan baris gratis: jual barang, jasa, atau promo usaha Anda ke pengguna RideSip lainnya.', style: TextStyle(fontSize: 13)),
                            const SizedBox(height: 10),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                icon: const Icon(Icons.campaign, size: 18),
                                label: const Text('Buka Iklan Gratis'),
                                style: FilledButton.styleFrom(backgroundColor: const Color(0xFFD8006B), padding: const EdgeInsets.symmetric(vertical: 12)),
                                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const IklanWebViewPage())),
                              ),
                            ),
                          ]),
                        ),
                      ),
                              ),
                            ),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 12),
                      // ---- Transaksi ----
                      const Padding(padding: EdgeInsets.only(left: 4), child: Text('Riwayat Transaksi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16))),
                      const SizedBox(height: 8),
                      ...(earnings == null || (earnings!['history'] as List).isEmpty
                          ? [const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Belum ada transaksi.')))]
                          : (earnings!['history'] as List).map<Map<String, dynamic>>((t) => Map<String, dynamic>.from(t as Map)).toList().map<Widget>((t) {
                              final completed = (t['status'] ?? '').toString().toUpperCase() == 'COMPLETED';
                              return Card(
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                margin: const EdgeInsets.only(bottom: 8),
                                child: ListTile(
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                                  title: Text(t['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                  subtitle: Text('${t['order_type'] ?? ''} • ${t['created_at'] ?? ''}', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                                  trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [
                                    Text(formatRp((t['merchant_net'] ?? 0).round()), style: TextStyle(fontWeight: FontWeight.bold, color: completed ? Colors.green.shade700 : Colors.orange.shade700, fontSize: 13)),
                                    Text(completed ? 'cair' : 'pending', style: TextStyle(fontSize: 11, color: completed ? Colors.green : Colors.orange)),
                                  ]),
                                ),
                              );
                            })),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
    );
  }

  Widget _info(String label, dynamic value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          SizedBox(width: 100, child: Text(label, style: TextStyle(fontSize: 13, color: Colors.grey.shade700))),
          Expanded(child: Text((value ?? '-').toString().isEmpty ? '-' : value.toString(), style: const TextStyle(fontSize: 13))),
        ]),
      );

  Widget _moneyRow(String label, int value, Color color) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [Text(label, style: const TextStyle(fontSize: 14)), Text(formatRp(value), style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: color))],
      );

  void _openMerchantInfoForm() {
    showDialog(
      context: context,
      builder: (ctx) => MerchantInfoFormDialog(
        merchant: merchant,
        onSaved: () {
          Navigator.pop(ctx);
          _loadAll();
        },
      ),
    );
  }
}

class MerchantInfoFormDialog extends StatefulWidget {
  final Map<String, dynamic>? merchant;
  final VoidCallback onSaved;
  const MerchantInfoFormDialog({super.key, this.merchant, required this.onSaved});

  @override
  State<MerchantInfoFormDialog> createState() => _MerchantInfoFormDialogState();
}

class _MerchantInfoFormDialogState extends State<MerchantInfoFormDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameCtrl;
  late final TextEditingController _descCtrl;
  late final TextEditingController _phoneCtrl;
  late final TextEditingController _addrCtrl;
  late final TextEditingController _cityCtrl;
  late final TextEditingController _logoCtrl;
  late final TextEditingController _bannerCtrl;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final m = widget.merchant;
    _nameCtrl = TextEditingController(text: m?['name'] ?? '');
    _descCtrl = TextEditingController(text: (m?['description'] ?? '').toString() == 'null' ? '' : (m?['description'] ?? ''));
    _phoneCtrl = TextEditingController(text: (m?['phone'] ?? '').toString() == 'null' ? '' : (m?['phone'] ?? ''));
    _addrCtrl = TextEditingController(text: (m?['address_line'] ?? '').toString() == 'null' ? '' : (m?['address_line'] ?? ''));
    _cityCtrl = TextEditingController(text: (m?['city'] ?? '').toString() == 'null' ? '' : (m?['city'] ?? ''));
    _logoCtrl = TextEditingController(text: (m?['logo_url'] ?? '').toString() == 'null' ? '' : (m?['logo_url'] ?? ''));
    _bannerCtrl = TextEditingController(text: (m?['banner_url'] ?? '').toString() == 'null' ? '' : (m?['banner_url'] ?? ''));
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _phoneCtrl.dispose();
    _addrCtrl.dispose();
    _cityCtrl.dispose();
    _logoCtrl.dispose();
    _bannerCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    final user = Session.user!;
    final body = {
      'user_id': user['id'],
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'phone': _phoneCtrl.text.trim(),
      'address_line': _addrCtrl.text.trim(),
      'city': _cityCtrl.text.trim(),
      'logo_url': _logoCtrl.text.trim().isEmpty ? null : _logoCtrl.text.trim(),
      'banner_url': _bannerCtrl.text.trim().isEmpty ? null : _bannerCtrl.text.trim(),
    };
    try {
      final res = await http.post(Uri.parse('$kApiBase/merchant/update'), headers: const {'Content-Type': 'application/json'}, body: jsonEncode(body));
      final data = jsonDecode(res.body);
      setState(() {
        _busy = false;
        if (res.statusCode == 200) {
          widget.onSaved();
        } else {
          _error = data['message'] ?? 'Gagal memperbarui info toko.';
        }
      });
    } catch (e) {
      setState(() {
        _busy = false;
        _error = 'Koneksi gagal: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Update Info Toko'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'Nama toko'), validator: (v) => v == null || v.trim().isEmpty ? 'Nama wajib' : null),
              const SizedBox(height: 10),
              TextFormField(controller: _descCtrl, decoration: const InputDecoration(labelText: 'Deskripsi'), maxLines: 2),
              const SizedBox(height: 10),
              TextFormField(controller: _phoneCtrl, decoration: const InputDecoration(labelText: 'Telepon'), keyboardType: TextInputType.phone),
              const SizedBox(height: 10),
              TextFormField(controller: _addrCtrl, decoration: const InputDecoration(labelText: 'Alamat')),
              const SizedBox(height: 10),
              TextFormField(controller: _cityCtrl, decoration: const InputDecoration(labelText: 'Kota')),
              const SizedBox(height: 10),
              TextFormField(controller: _logoCtrl, decoration: const InputDecoration(labelText: 'URL logo (opsional)')),
              const SizedBox(height: 10),
              TextFormField(controller: _bannerCtrl, decoration: const InputDecoration(labelText: 'URL banner (opsional)')),
              if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: TextStyle(color: Colors.red.shade700, fontSize: 12))),
              const SizedBox(height: 10),
              ElevatedButton(
                style: ElevatedButton.styleFrom(backgroundColor: Colors.deepOrange, foregroundColor: Colors.white),
                onPressed: _busy ? null : _submit,
                child: _busy ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Simpan Perubahan'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ============ Halaman Iklan Gratis — WebView ke https://ridesip.my.id/iklan-webview ============
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
        backgroundColor: Colors.deepOrange,
        body: const Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }
    return Scaffold(
      backgroundColor: Colors.deepOrange,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              color: Colors.deepOrange,
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
                  userAgent: 'RideSipMerchantApp/1.0',
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
    await Session.load();
    if (!mounted) return;
    final user = Session.user;
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
        backgroundColor: Colors.deepOrange,
        body: const Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }
    return Scaffold(
      backgroundColor: Colors.deepOrange,
      body: SafeArea(
        child: Column(
          children: [
            Container(
              color: Colors.deepOrange,
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
                  userAgent: 'RideSipMerchantApp/1.0',
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
