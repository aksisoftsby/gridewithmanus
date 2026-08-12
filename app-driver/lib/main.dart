import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

void main() {
  runApp(const DriverApp());
}

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

class DriverHome extends StatefulWidget {
  const DriverHome({super.key});

  @override
  State<DriverHome> createState() => _DriverHomeState();
}

class _DriverHomeState extends State<DriverHome> {
  List orders = [];
  bool isLoading = true;
  final String baseUrl = 'https://gride.web.id/api';

  @override
  void initState() {
    super.initState();
    fetchOrders();
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

  /// Invoice breakdown sesuai jenis pesanan. Driver hanya melihat komponen
  /// yang menjadi bagiannya: biaya perjalanan dan pendapatannya.
  List<Widget> _invoiceRows(Map<String, dynamic> invoice, Map<String, dynamic> order) {
    final rows = <Widget>[];
    final t = invoice['order_type'] ?? '';
    if (t == 'RIDE' || t == 'DELIVERY') {
      if (invoice['trip_distance_km'] != null) {
        rows.add(_row('Jarak', '${double.tryParse(invoice['trip_distance_km'].toString())?.toStringAsFixed(1) ?? '-'} km'));
      }
      rows.add(_row('Tarif dasar', 'Rp ${NumberFormatRp((invoice['base_fare'] ?? 0).round())}'));
      if ((invoice['trip_cost'] ?? 0) > 0) {
        rows.add(_row(invoice['trip_cost_label'] ?? 'Biaya perjalanan', 'Rp ${NumberFormatRp((invoice['trip_cost'] ?? 0).round())}'));
      }
      if ((invoice['admin_commission'] ?? 0) > 0) {
        rows.add(_row(invoice['admin_commission_label'] ?? 'Potongan admin',
            '- Rp ${NumberFormatRp((invoice['admin_commission']).round())}',
            negative: true));
      }
      rows.add(_row(invoice['driver_net_label'] ?? 'Pendapatan Driver',
          'Rp ${NumberFormatRp((invoice['driver_net'] ?? 0).round())}',
          bold: true));
    } else {
      rows.add(_row('Subtotal', 'Rp ${NumberFormatRp((invoice['subtotal'] ?? 0).round())}'));
      if ((invoice['delivery_fee'] ?? 0) > 0) {
        rows.add(_row('Ongkos kirim', 'Rp ${NumberFormatRp((invoice['delivery_fee'] ?? 0).round())}'));
      }
      rows.add(_row('Total pesanan', 'Rp ${NumberFormatRp((invoice['total'] ?? 0).round())}'));
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
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Status Driver: ONLINE', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.teal)),
                        SizedBox(height: 8),
                        Text('Siap menerima pesanan pengantaran dan antar-jemput di sekitar Anda.', style: TextStyle(fontSize: 13, color: Colors.black87)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Text('Daftar Pesanan Masuk', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 10),
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
                                    backgroundColor: Colors.teal.shade100,
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

String NumberFormatRp(int value) => value.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]}.',
    );
