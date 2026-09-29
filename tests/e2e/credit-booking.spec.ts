import { test, expect, Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Krediidi-broneerimise E2E kontroll.
 *
 * Eeldab: bash scripts/setup-credit-e2e.sh
 * (seeder + tests/e2e/.e2e-dates.json). Enne igat täisjooksu reseedi,
 * kuigi broneerimistest koristab enda järel.
 *
 * Reegel: Seltskonnatrenn minperiod=60, mincancel=1440 (live-CSV-st).
 *  A (+2h): available=true, pastentry=true  -> krediit LUBATUD (põhiregressioon)
 *  B (+30min): available=false              -> SULETUD (mõlemad)
 *  C (+30h): available=true, pastentry=false -> krediit LUBATUD
 *  D (+2h, täis): limiit ületatud           -> SULETUD (timetable peidab täis kuupäevad,
 *     seetõttu kontroll hash-modaliga #entry{id}, mis näitab limiiditeadet)
 */

const BASE = process.env.E2E_BASE_URL ?? 'http://localhost:8000';
const LOCALE = '/et';
const CLIENT = { email: 'e2e-client@racster.com', password: 'secret' };
const POOR = { email: 'e2e-poor@racster.com', password: 'secret' };

type DatesJson = {
  A: { id: number; start: string };
  B: { id: number; start: string };
  C: { id: number; start: string };
  D: { id: number; start: string };
  entryId: number;
};

function loadDates(): DatesJson {
  const p = path.join(__dirname, '.e2e-dates.json');
  if (!fs.existsSync(p)) {
    throw new Error(
      `Puudub ${p}. Käivita esmalt: bash scripts/setup-credit-e2e.sh (seeder kirjutab selle faili).`,
    );
  }
  return JSON.parse(fs.readFileSync(p, 'utf-8'));
}

async function login(page: Page, email: string, password: string) {
  await page.goto(`${BASE}${LOCALE}/login`);
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: /Logi sisse|Login/i }).click();
  // Edukas login viib timetable/home lehele (mitte tagasi loginile)
  await expect(page).not.toHaveURL(/login/, { timeout: 10000 });
}

async function csrfToken(page: Page): Promise<string> {
  const fromInput = await page
    .locator('input[name="_token"]')
    .first()
    .inputValue()
    .catch(() => '');
  if (fromInput) return fromInput;
  const html = await page.content();
  const m = html.match(/_token['"]\s*:\s*['"]([^'"]+)/);
  if (m) return m[1];
  throw new Error('CSRF tokenit ei leitud timetable lehelt');
}

async function postAjax(page: Page, url: string, params: Record<string, string>) {
  const token = await csrfToken(page);
  return page.evaluate(
    async ({ base, locale, endpoint, params, token }) => {
      const res = await fetch(`${base}${locale}/${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ ...params, _token: token }),
      });
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch {
        // PHP hoiatused HTML-ina JSON-i ees (nt display_errors lokaalses PHP-s)
        const start = text.indexOf('{');
        if (start >= 0) return JSON.parse(text.slice(start));
        throw new Error(`Mitte-JSON vastus: ${text.slice(0, 200)}`);
      }
    },
    { base: BASE, locale: LOCALE, endpoint: url, params, token },
  );
}

async function openModal(page: Page, dateId: number) {
  await page.goto(`${BASE}${LOCALE}/timetable`);
  const entry = page.locator(`.date-entry[data-eid="${dateId}"]`).first();
  await expect(entry, `date-entry data-eid=${dateId} peab olema nähtav`).toBeVisible({ timeout: 10000 });
  await entry.click();
  await expect(page.locator('#show-entry-data')).toBeVisible({ timeout: 10000 });
  await expect(page.locator('#show-entry-data .modal-content.modal-loading')).toHaveCount(0, {
    timeout: 10000,
  });
}

test.describe('krediit formaadi reeglite järgi', () => {
  const dates = loadDates();

  test('A (+2h): info lubab krediiti (available=true, pastentry=true, okcredit=true)', async ({
    page,
  }) => {
    await login(page, CLIENT.email, CLIENT.password);
    await page.goto(`${BASE}${LOCALE}/timetable`);
    const info: any = await postAjax(page, 'acquireEntryData', { eid: String(dates.A.id) });
    expect(info.success).toBe(true);
    expect(info.entry.available).toBe(true); // formaadi aken (60min) avatud
    expect(info.entry.pastentry).toBe(true); // tühistamisaken (1440min) suletud
    expect(info.entry.okcredit).toBe(true); // jääk katab hinna
    expect(info.entry.started).toBe(false);
  });

  test('A (+2h): krediidiga klient näeb "Osale" nuppu, mitte "Tee makse"', async ({ page }) => {
    await login(page, CLIENT.email, CLIENT.password);
    await openModal(page, dates.A.id);
    const btn = page.locator('#attend-entry');
    await expect(btn).toBeVisible();
    await expect(btn).toBeEnabled();
    await expect(btn).not.toContainText(/Tee makse/);
    await expect(page.locator('#show-entry-data .modal-footer')).not.toContainText(
      /Osalemise aeg on möödas/,
    );
  });

  test('A (+2h): broneerimine krediidiga õnnestub ilma Stripe-ita (ja koristab)', async ({
    page,
  }) => {
    await login(page, CLIENT.email, CLIENT.password);
    await page.goto(`${BASE}${LOCALE}/timetable`);

    // Idempotentsus: eelmise katkenud jooksu broneeringu tühistame esmalt.
    const before: any = await postAjax(page, 'acquireEntryData', { eid: String(dates.A.id) });
    if (before.entry?.attend === true) {
      await postAjax(page, 'attendPeriod', { eid: String(dates.A.id) });
    }

    const res: any = await postAjax(page, 'attendPeriod', {
      eid: String(dates.A.id),
      cqty: '1',
    });
    expect(res.success).toBe(true);
    expect(res.pay_url ?? null).toBeNull(); // krediit katab hinna -> pole Stripe-i
    expect(res.msg).toMatch(/salvestatud|õnnestus/i);

    // Korista: tühista broneering, et kordusjooksud alustaksid puhtalt.
    const cancel: any = await postAjax(page, 'attendPeriod', { eid: String(dates.A.id) });
    expect(cancel.success).toBe(true);
  });

  test('B (+30min): broneerimine suletud (available=false) nii krediidile kui rahale', async ({
    page,
  }) => {
    await login(page, CLIENT.email, CLIENT.password);
    await page.goto(`${BASE}${LOCALE}/timetable`);
    const info: any = await postAjax(page, 'acquireEntryData', { eid: String(dates.B.id) });
    expect(info.entry.available).toBe(false);

    const res: any = await postAjax(page, 'attendPeriod', {
      eid: String(dates.B.id),
      cqty: '1',
    });
    expect(res.success).toBe(false);

    await openModal(page, dates.B.id);
    await expect(page.locator('#show-entry-data .modal-footer')).toContainText(
      /Osalemise aeg on möödas/,
    );
    await expect(page.locator('#attend-entry')).toBeHidden();
  });

  test('D (täis): limiidi teade hash-modaliga', async ({ page }) => {
    // Täis kuupäevi timetable ei renderda (display päring filtreerib),
    // seetõttu avame modali otse hash-iga: openDateModal näitab limiiditeadet.
    await login(page, CLIENT.email, CLIENT.password);
    await page.goto(`${BASE}${LOCALE}/timetable#entry${dates.D.id}`);
    await expect(page.locator('#show-entry-data')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('#show-entry-data .modal-body')).toContainText(
      /maksimaalne arv osalejaid/,
    );
    await expect(page.locator('#attend-entry')).toBeHidden();
  });

  test('A (+2h): krediidita klient näeb "Tee makse"', async ({ page }) => {
    await login(page, POOR.email, POOR.password);
    await openModal(page, dates.A.id);
    await expect(page.locator('#attend-entry')).toContainText(/Tee makse/);
  });
});
