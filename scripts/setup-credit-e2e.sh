#!/usr/bin/env bash
#
# E2E andmete ettevalmistus krediidi-broneerimise kontrolliks (Playwright).
#
#  1. Sünkroniseerib live-konfiguratsiooni (racster-live-data/*.csv) lokaalse DB-ga
#     CreditBookingE2ESeeder kaudu (minperiod/mincancel pealkirja järgi).
#  2. Loob deterministliku stsenaariumi (E2E entry kuupäevad kustutatakse ja luuakse uuesti):
#       A (+2h)  - available=true,  pastentry=true  -> krediit LUBATUD (põhitest)
#       B (+30m) - available=false                  -> SULETUD
#       C (+30h) - available=true,  pastentry=false -> krediit LUBATUD
#       D (+2h, täis) - limiit ületatud             -> SULETUD
#     Seeder kirjutab kuupäevade id-d faili tests/e2e/.e2e-dates.json.
#
# B (+30min) aegub kiiresti, seega käivita see skript vahetult enne Playwrighti.
#
# Kasutus:
#   bash scripts/setup-credit-e2e.sh
#   npx playwright test --config=playwright.config.ts tests/e2e/credit-booking.spec.ts
#
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> Seeding E2E credit-booking data (live-CSV rules + relative dates)..."
php artisan db:seed --class=CreditBookingE2ESeeder

echo ""
echo "==> Kuupäevad (tests/e2e/.e2e-dates.json):"
cat tests/e2e/.e2e-dates.json
echo ""
echo "==> Valmis. Testkasutajad: e2e-client@racster.com / secret (krediit 100), e2e-poor@racster.com / secret (0)"
echo "==> Käivita KOHE (B aegub ~30min): npx playwright test --config=playwright.config.ts tests/e2e/credit-booking.spec.ts"
