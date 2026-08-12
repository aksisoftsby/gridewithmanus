import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

void main() {
  runApp(const MerchantApp());
}

class MerchantApp extends StatelessWidget {
  const MerchantApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Gride Merchant',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepOrange),
        useMaterial3: true,
      ),
      home: const MerchantHome(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class MerchantHome extends StatefulWidget {
  const MerchantHome({super.key});

  @override
  State<MerchantHome> createState() => _MerchantHomeState();
}

class _MerchantHomeState extends State<MerchantHome> with SingleTickerProviderStateMixin {
  List merchants = [];
  List orders = [];
  bool isLoading = true;
  final String baseUrl = 'https://gride.web.id/api';
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    fetchMerchants();
    fetchOrders();
  }

  Future<void> fetchMerchants() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/merchants'));
      if (res.statusCode == 200) {
        setState(() => merchants = jsonDecode(res.body)['data']);
      }
    } catch (e) {}
  }

  Future<void> fetchOrders() async {
    setState(() => isLoading = true);
    try {
      final res = await http.get(Uri.parse('$baseUrl/orders'));
      if (res.statusCode == 200) {
        setState(() {
          orders = jsonDecode(res.body)['data'];
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() => isLoading = false);
    }
  }

  String NumberFormatRp(int value) => value.toString().replaceAllMapped(
        RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
        (m) => '${m[1]}.',
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gride Merchant'),
        backgroundColor: Colors.deepOrange,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Pesanan'),
            Tab(text: 'Mitra Toko'),
          ],
        ),
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [_ordersTab, _merchantsTab],
            ),
    );
  }

  Widget get _ordersTab => RefreshIndicator(
        onRefresh: fetchOrders,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.deepOrange.shade50,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.deepOrange),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Pesanan Masuk', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.deepOrange)),
                  SizedBox(height: 8),
                  Text('Lihat rincian pembayaran pesanan: subtotal, komisi restro, potongan admin, dan pendapatan bersih toko Anda.', style: TextStyle(fontSize: 13, color: Colors.black87)),
                ],
              ),
            ),
            const SizedBox(height: 20),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: orders.length,
              itemBuilder: (context, index) {
                final order = orders[index];
                final invoice = Map<String, dynamic>.from(order['invoice'] ?? {});
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
                            Text(order['order_number'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            Chip(
                              label: Text(order['status'] ?? '', style: const TextStyle(fontSize: 11)),
                              backgroundColor: Colors.deepOrange.shade100,
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text('Customer: ${order['customer_name'] ?? '-'}'),
                        const SizedBox(height: 8),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _row('Subtotal', 'Rp ${NumberFormatRp((invoice['subtotal'] ?? 0).round())}'),
                              if ((invoice['delivery_fee'] ?? 0) > 0)
                                _row('Ongkos kirim', 'Rp ${NumberFormatRp((invoice['delivery_fee'] ?? 0).round())}'),
                              if ((invoice['trip_cost'] ?? 0) > 0)
                                _row(invoice['trip_cost_label'] ?? 'Biaya perjalanan', 'Rp ${NumberFormatRp((invoice['trip_cost'] ?? 0).round())}'),
                              if ((invoice['merchant_commission'] ?? 0) > 0)
                                _row(invoice['merchant_commission_label'] ?? 'Komisi restro',
                                    '- Rp ${NumberFormatRp((invoice['merchant_commission']).round())}',
                                    negative: true),
                              if ((invoice['admin_commission'] ?? 0) > 0)
                                _row(invoice['admin_commission_label'] ?? 'Potongan admin',
                                    '- Rp ${NumberFormatRp((invoice['admin_commission']).round())}',
                                    negative: true),
                              _row('Total pesanan', 'Rp ${NumberFormatRp((invoice['total'] ?? 0).round())}'),
                              _row(invoice['merchant_net_label'] ?? 'Pendapatan Bersih Toko',
                                  'Rp ${NumberFormatRp((invoice['merchant_net'] ?? 0).round())}',
                                  bold: true),
                            ],
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
      );

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
            color: negative ? Colors.red[700] : (bold ? Colors.deepOrange.shade800 : Colors.black87),
          )),
        ],
      ),
    );
  }

  Widget get _merchantsTab => RefreshIndicator(
        onRefresh: fetchMerchants,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text('Mitra Toko Terdaftar', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
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
                    leading: Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        color: Colors.deepOrange.shade100,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.storefront, color: Colors.deepOrange),
                    ),
                    title: Text(m['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('Kategori: ${m['type'] ?? '-'} • Kota: ${m['city'] ?? '-'}'),
                    trailing: const Icon(Icons.chevron_right),
                  ),
                );
              },
            ),
          ],
        ),
      );
}
