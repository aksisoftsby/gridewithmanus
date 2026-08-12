import 'package:flutter_test/flutter_test.dart';

import 'package:app_merchant/main.dart';

void main() {
  testWidgets('App renders without crashing', (WidgetTester tester) async {
    await tester.pumpWidget(const MerchantApp());
    expect(find.text('SuperApp Merchant Dashboard'), findsOneWidget);
  });
}
