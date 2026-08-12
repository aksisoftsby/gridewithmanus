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
      title: 'SuperApp Merchant',
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

class _MerchantHomeState extends State<MerchantHome> {
  List merchants = [];
  bool isLoading = true;
  final String baseUrl = 'http://10.0.2.2:8000/api';

  @override
  void initState() {
    super.initState();
    fetchMerchants();
  }

  Future<void> fetchMerchants() async {
    setState(() => isLoading = true);
    try {
      final res = await http.get(Uri.parse('$baseUrl/merchants'));
      if (res.statusCode == 200) {
        setState(() {
          merchants = jsonDecode(res.body)['data'];
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
        title: const Text('SuperApp Merchant Dashboard'),
        backgroundColor: Colors.deepOrange,
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: fetchMerchants,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.deepOrange[50],
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.deepOrange),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Toko Anda: Aktif & Buka', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.deepOrange)),
                        SizedBox(height: 8),
                        Text('Kelola menu produk, pesanan masuk, dan jam operasional toko Anda di sini.', style: TextStyle(fontSize: 13, color: Colors.black87)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),
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
                              color: Colors.deepOrange[100],
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Icon(Icons.storefront, color: Colors.deepOrange),
                          ),
                          title: Text(m['name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                          subtitle: Text('Kategori: ${m['type']} • Kota: ${m['city']}'),
                          trailing: const Icon(Icons.chevron_right),
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
