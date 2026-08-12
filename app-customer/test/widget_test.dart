import 'package:flutter_test/flutter_test.dart';

import 'package:app_customer/main.dart';

void main() {
  testWidgets('App renders without crashing', (WidgetTester tester) async {
    await tester.pumpWidget(const CustomerApp());
    expect(find.text('SuperApp Customer'), findsOneWidget);
  });
}
