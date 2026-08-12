import 'package:flutter_test/flutter_test.dart';

import 'package:app_driver/main.dart';

void main() {
  testWidgets('App renders without crashing', (WidgetTester tester) async {
    await tester.pumpWidget(const DriverApp());
    expect(find.text('SuperApp Driver Dashboard'), findsOneWidget);
  });
}
