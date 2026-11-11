/* Ejecutar únicamente contra una base de pruebas aislada. Véase README.md. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const base = process.env.PORTAL_TEST_URL || 'http://127.0.0.1:8081';
const output = process.env.PORTAL_TEST_SCREENSHOTS;
(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium', args: ['--no-sandbox'] });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const open = async route => {
      await page.goto(base + '/' + route);
      await page.waitForSelector('.site-footer');
      await page.locator('h1').first().waitFor();
    };
    const capture = async name => {
      if (output) { fs.mkdirSync(output, { recursive: true }); await page.screenshot({ path: path.join(output, name + '.png'), fullPage: true }); }
    };
    await open('#/');
    assert.equal(await page.locator('[data-newsletter]').count(), 0);
    assert.equal(await page.locator('.topbar a').filter({ hasText: 'Biblioteca virtual' }).count(), 0);
    const logo = await page.locator('.site-header .brand img').evaluate(el => ({ animation: getComputedStyle(el).animationName, transform: getComputedStyle(el).transform }));
    assert.equal(logo.animation, 'none'); assert.equal(logo.transform, 'none');
    await page.locator('.site-header .brand').hover();
    assert.equal(await page.locator('.site-header .brand img').evaluate(el => getComputedStyle(el).transform), 'none');
    await capture('inicio-desktop');
    await open('#/nosotros/reconocimientos');
    const count = await page.evaluate(() => P('reconocimientos').filter(isPub).filter(r => r.foto).length);
    assert.equal(await page.locator('.timeline [data-enlarge]').count(), count);
    await capture('reconocimientos-desktop');
    await page.locator('.timeline [data-enlarge]').first().click();
    await page.locator('.image-full').waitFor(); await page.keyboard.press('Escape');
    assert.equal(await page.locator('.image-full').count(), 0);
    await open('#/nosotros/plana-jerarquica');
    const names = await page.locator('.collage figcaption strong, .director .msg>strong').allTextContents();
    assert.equal(names.length, new Set(names).size);
    await capture('plana-jerarquica-desktop');
    const program = await page.evaluate(() => progs()[0]);
    await open('#/programas/' + program.slug);
    assert.equal(await page.locator('.program-overview [data-gallery]').count(), 1);
    assert(await page.locator('[data-program-section="campo-laboral"]').isVisible());
    const counter = page.locator('.program-gallery [data-gallery-count]');
    const first = await counter.innerText();
    await page.locator('.program-gallery [data-gallery-next]').click();
    assert.notEqual(await counter.innerText(), first);
    await page.locator('.program-gallery [data-gallery-prev]').click();
    assert.equal(await counter.innerText(), first);
    await capture('programa-desktop');
    await open('#/nosotros/organizacion');
    assert.equal(await page.locator('img[src*="organigrama-mpp-2024"]').count(), 1);
    assert(await page.locator('a[href$=".pdf#page=7"]').count());
    await capture('organigrama-desktop');
    const chartReview = await page.evaluate(() => {
      const record = page('organizacion'), original = record.organigrama;
      try {
        record.organigrama = '/assets/chincha/sede-principal-bb3c09d0.webp';
        const html = ROUTES['nosotros/organizacion'](['nosotros', 'organizacion']);
        return { showsCustomChart: html.includes(record.organigrama), citesManual: html.includes('Manual de Perfil del Puesto, versión 2024') };
      } finally { if (original === undefined) delete record.organigrama; else record.organigrama = original; }
    });
    assert(chartReview.showsCustomChart); assert.equal(chartReview.citesManual, false);
    const editors = await page.evaluate(() => ['privacidad', 'terminos', 'faq', 'resena'].map(key => ({ key, gallery: SPECS.paginas.fields(page(key)).some(field => field.n === 'galeria') })));
    assert.deepEqual(editors.map(editor => editor.gallery), [false, false, false, true]);
    const sitemapReview = await page.evaluate(() => {
      const originalDownload = downloadText, originalServices = inst().serviciosActivos;
      let xml = '';
      try {
        downloadText = text => { xml = text; };
        inst().serviciosActivos = []; downloadSitemap(); const disabled = xml;
        inst().serviciosActivos = ['mesa-de-partes', 'libro-de-reclamaciones']; downloadSitemap();
        return { disabled, enabled: xml };
      } finally {
        downloadText = originalDownload;
        if (originalServices === undefined) delete inst().serviciosActivos; else inst().serviciosActivos = originalServices;
      }
    });
    for (const route of ['/servicios/mesa-de-partes', '/servicios/consulta-certificados', '/transparencia/libro-de-reclamaciones']) assert(!sitemapReview.disabled.includes(route));
    assert(sitemapReview.disabled.includes('/servicios/bolsa-laboral'));
    assert(sitemapReview.enabled.includes('/servicios/mesa-de-partes'));
    assert(sitemapReview.enabled.includes('/transparencia/libro-de-reclamaciones'));
    assert(!sitemapReview.enabled.includes('/servicios/consulta-certificados'));

    await open('#/servicios');
    assert.equal(await page.locator('.service-external a').count(), 1);
    assert.equal(await page.locator('a[href="#/servicios/mesa-de-partes"]').count(), 0);
    await capture('servicios-desktop');
    for (const route of ['#/servicios/mesa-de-partes', '#/servicios/libro-de-reclamaciones', '#/transparencia/libro-de-reclamaciones', '#/libro-de-reclamaciones']) {
      await open(route); assert.equal(await page.locator('h1').innerText(), 'Servicio no disponible');
    }
    for (const route of ['#/', '#/programas/' + program.slug, '#/nosotros/organizacion', '#/nosotros/plana-jerarquica', '#/servicios']) {
      await page.setViewportSize({ width: 390, height: 844 }); await open(route);
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Desbordamiento horizontal en ' + route);
    }
    await open('#/programas/' + program.slug); await capture('programa-mobile');
    const programs = await page.evaluate(() => progs().map(program => program.slug));
    for (const slug of programs) {
      await open('#/programas/' + slug);
      await page.locator('.program-gallery').waitFor();
      await page.locator('#app img').evaluateAll(images => images.forEach(image => { image.loading = 'eager'; }));
      await page.waitForFunction(() => [...document.querySelectorAll('#app img[src]')].every(image => image.complete && image.naturalWidth > 0), null, { timeout: 10000 });
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Desbordamiento en programa ' + slug);
      const next = page.locator('.program-gallery [data-gallery-next]');
      if (await next.count()) {
        await next.click();
        assert(await page.locator('.program-gallery [data-gallery-slide]:not([hidden]) img').evaluate(image => image.naturalWidth > 0));
      }
    }

    // Carga, guardado real y lectura tras recarga; siempre en una base local de pruebas.
    if (process.env.PORTAL_TEST_PASSWORD_FILE) {
      const password = fs.readFileSync(process.env.PORTAL_TEST_PASSWORD_FILE, 'utf8').trim();
      const state = await (await page.request.get(base + '/api/portal')).json();
      const login = await page.request.post(base + '/api/session', { headers: { 'X-CSRF-Token': state.csrf }, data: { username: process.env.PORTAL_TEST_USER || 'devadmin', password } });
      assert.equal(login.status(), 200);
      await page.reload();
      await page.setViewportSize({ width: 1440, height: 1000 });
      await page.goto(base + '/#/admin/programas/' + program.id);
      await page.locator('[data-gallery-upload]').waitFor({ state: 'attached' });
      const original = await page.evaluate(id => structuredClone(DB.programas.find(p => p.id === id).galeria ?? null), program.id);
      try {
        await page.locator('[data-gallery-upload]').setInputFiles(path.join(__dirname, '../../public/assets/chincha/sede-principal-bb3c09d0.webp'));
        await page.waitForFunction(() => EDITOR_UPLOADS.size === 0 && ADM.work.galeria.length >= 2);
        await page.locator('[data-gallery-alt]').last().fill('Fotografía de prueba de persistencia');
        const submit = page.locator('[data-cf]').getByRole('button', { name: 'Guardar cambios', exact: true });
        await submit.click();
        await page.waitForTimeout(400);
        await page.reload();
        const persisted = await page.evaluate(id => DB.programas.find(p => p.id === id).galeria, program.id);
        assert(persisted.some(item => item.alt === 'Fotografía de prueba de persistencia'));
        await page.goto(base + '/#/admin/config'); await page.locator('[name=bolsaMineduUrl]').waitFor();
        assert.equal(await page.locator('[name=serviciosActivos]').count(), 6);
      } finally {
        await page.evaluate(({ id, original }) => { const program = DB.programas.find(p => p.id === id); if (original === null) delete program.galeria; else program.galeria = original; if (!saveDB()) throw new Error('No se pudo restaurar la galería de prueba'); }, { id: program.id, original });
      }
    }
    if (process.env.PORTAL_TEST_PASSWORD_FILE) {
      await page.goto(base + '/#/admin/config');
      await page.locator('[name=serviciosActivos]').first().waitFor();
      const activeBefore = await page.evaluate(() => structuredClone(inst().serviciosActivos ?? null));
      const guest = await browser.newPage();
      guest.on('pageerror', error => errors.push(error.message));
      try {
        await page.locator('[name=serviciosActivos][value="mesa-de-partes"]').check();
        await page.locator('[data-sf]').getByRole('button', { name: 'Guardar cambios', exact: true }).click();
        await page.waitForFunction(() => inst().serviciosActivos?.includes('mesa-de-partes'));
        await page.reload();
        assert(await page.evaluate(() => institutionalServiceActive('mesa-de-partes')));
        await guest.goto(base + '/#/servicios'); await guest.locator('.site-footer').waitFor();
        assert(await guest.locator('.content a[href="#/servicios/mesa-de-partes"]').count());
        await guest.goto(base + '/#/servicios/mesa-de-partes');
        await guest.locator('h1').waitFor();
        assert.equal(await guest.locator('h1').innerText(), 'Mesa de Partes Virtual');
      } finally {
        await guest.close();
        await page.evaluate(active => { if (active === null) delete inst().serviciosActivos; else inst().serviciosActivos = active; if (!saveDB()) throw new Error('No se pudo restaurar la configuración de pruebas'); }, activeBefore);
      }
      await page.goto(base + '/#/servicios/mesa-de-partes'); await page.locator('h1').waitFor();
      assert.equal(await page.locator('h1').innerText(), 'Servicio no disponible');
      await page.goto(base + '/#/admin/paginas/pg-resena');
      await page.locator('[data-cf]').waitFor();
      const expected = await page.evaluate(() => {
        const work = ADM.work;
        work.versiones = [{ titulo: work.titulo, contenido: work.contenido, imagen: work.imagen, mensaje: work.mensaje, fecha: new Date().toISOString(), por: 'test' }];
        work.galeria = [{ src: '/assets/chincha/sede-principal-bb3c09d0.webp', alt: 'Versión reciente' }];
        const count = recordGalleryDefaults(work).length; route(); return count;
      });
      await page.locator('[data-restv]').click();
      await page.locator('.modal-f [data-r="1"]').click();
      assert.equal(await page.evaluate(() => ADM.work.galeria.length), expected);
      // Una galería vacía explícita se restaura vacía; una configurada se copia sin mutar el historial.
      for (const gallery of [[], [{ src: '/assets/chincha/sede-principal-bb3c09d0.webp', alt: 'Descripción histórica' }]]) {
        await page.evaluate(gallery => { ADM.work.versiones[0].galeria = gallery; route(); }, gallery);
        await page.locator('[data-restv]').click(); await page.locator('.modal-f [data-r="1"]').click();
        assert.equal(await page.evaluate(() => ADM.work.galeria.length), gallery.length);
        if (gallery.length) {
          await page.locator('[data-gallery-alt]').first().fill('Descripción modificada en el editor');
          assert.equal(await page.evaluate(() => ADM.work.versiones[0].galeria[0].alt), 'Descripción histórica');
        }
      }
      // Los casos de restauración usan solo el editor; no guardan las versiones sintéticas.
      await page.goto(base + '/#/nosotros/resena-historica');
    }
    assert.deepEqual(errors, []);
    console.log(`PASS: browser checks, desktop/mobile, ${programs.length} programs with loaded images, gallery controls, service availability, version restoration, configuration persistence`  + (process.env.PORTAL_TEST_PASSWORD_FILE ? ', upload and persistence after reload' : ''));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
