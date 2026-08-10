import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:flamattitude_app/main.dart';

void main() {
  testWidgets("L'app démarre sur l'écran de vérification biométrique", (WidgetTester tester) async {
    await tester.pumpWidget(const FlamattitudeApp());

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}
