'use strict';

/* Medios institucionales compartidos por las páginas y sus editores. */
function galleryItems(record) {
  const items = Array.isArray(record.galeria) ? record.galeria : recordGalleryDefaults(record);
  const main = record.imagen ? [{ src: record.imagen, alt: record.titulo || record.nombre || '' }] : [];
  const seen = new Set();
  return [...main, ...items].filter(item => item && typeof item.src === 'string' && item.src && !seen.has(item.src) && seen.add(item.src));
}

function galleryHTML(record, title = 'Galería de imágenes') {
  const items = galleryItems(record);
  if (!items.length) return '';
  return `<section class="photo-gallery" data-gallery aria-label="${esc(title)}">
    <div class="gallery-stage">${items.map((item, i) => `<figure data-gallery-slide ${i ? 'hidden' : ''}>
      <button type="button" class="gallery-enlarge" data-enlarge aria-label="Ampliar imagen: ${esc(item.alt || title)}"><img src="${esc(img(item.src))}" alt="${esc(item.alt || title)}" loading="lazy" decoding="async"></button>
      ${item.alt ? `<figcaption>${esc(item.alt)}</figcaption>` : ''}</figure>`).join('')}</div>
    ${items.length > 1 ? `<div class="gallery-controls"><button type="button" class="btn sec sm" data-gallery-prev aria-label="Imagen anterior">← Anterior</button><span data-gallery-count aria-live="polite">1 / ${items.length}</span><button type="button" class="btn sec sm" data-gallery-next aria-label="Imagen siguiente">Siguiente →</button></div>
      <div class="gallery-thumbs" aria-label="Elegir imagen">${items.map((item, i) => `<button type="button" data-gallery-index="${i}" aria-label="Mostrar imagen ${i + 1}" aria-current="${i === 0}"><img src="${esc(img(item.src))}" alt="" loading="lazy"></button>`).join('')}</div>` : ''}
  </section>`;
}

function bindInstitutionalMedia() {
  $$('[data-gallery]').forEach(box => {
    const slides = $$('[data-gallery-slide]', box);
    let index = 0;
    const show = n => {
      index = (n + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.hidden = i !== index);
      $$('[data-gallery-index]', box).forEach((button, i) => button.setAttribute('aria-current', String(i === index)));
      const counter = $('[data-gallery-count]', box);
      if (counter) counter.textContent = `${index + 1} / ${slides.length}`;
    };
    const previous = $('[data-gallery-prev]', box), next = $('[data-gallery-next]', box);
    if (previous) previous.onclick = () => show(index - 1);
    if (next) next.onclick = () => show(index + 1);
    $$('[data-gallery-index]', box).forEach(button => button.onclick = () => show(+button.dataset.galleryIndex));
  });
  $$('[data-enlarge]').forEach(button => button.onclick = () => {
    const image = $('img', button);
    if (!image) return;
    const m = modal({ title: esc(image.alt || 'Imagen institucional'), size: 'lg', body: `<img class="image-full" src="${esc(image.src)}" alt="${esc(image.alt)}">` });
    const dialog = $('.modal', m.el);
    dialog.addEventListener('keydown', event => {
      if (event.key === 'Tab') { event.preventDefault(); $('.modal-x', m.el).focus(); }
    });
  });
}

function institutionalGalleryField(label = 'Galería de imágenes') {
  return { n: 'galeria', l: label, t: 'custom', full: true,
    render: work => `<p class="note">Agrega hasta 12 fotografías adicionales. La imagen principal se conserva y aparece primero. Describe cada imagen para facilitar su comprensión.</p>
      <div class="gallery-editor">${(work.galeria ?? recordGalleryDefaults(work)).map((item, i) => `<div class="gallery-editor-item"><img src="${esc(img(item.src))}" alt="Vista previa de imagen ${i + 1}"><label>Descripción de la imagen ${i + 1}<input class="inp" data-gallery-alt="${i}" maxlength="240" value="${esc(item.alt || '')}"></label><div><button type="button" class="btn xs sec" data-gallery-move="${i}" ${i ? '' : 'disabled'} aria-label="Mover imagen ${i + 1} hacia arriba">↑</button> <button type="button" class="btn xs ghost" data-gallery-remove="${i}">Quitar imagen ${i + 1}</button></div></div>`).join('')}</div>
      <label class="btn sm sec">Agregar fotografías<input type="file" data-gallery-upload accept=".jpg,.jpeg,.png,.webp" multiple hidden ${(work.galeria || []).length >= 12 ? 'disabled' : ''}></label>`,
    bind: (work, box, rerender) => {
      work.galeria ??= recordGalleryDefaults(work);
      $$('[data-gallery-alt]', box).forEach(input => input.oninput = () => work.galeria[+input.dataset.galleryAlt].alt = input.value);
      $$('[data-gallery-remove]', box).forEach(button => button.onclick = async () => {
        if (await confirmBox('¿Quitar esta imagen de la galería?', { ok: 'Quitar' })) { work.galeria.splice(+button.dataset.galleryRemove, 1); rerender(); }
      });
      $$('[data-gallery-move]', box).forEach(button => button.onclick = () => {
        const i = +button.dataset.galleryMove;
        if (i > 0) { [work.galeria[i - 1], work.galeria[i]] = [work.galeria[i], work.galeria[i - 1]]; rerender(); }
      });
      const input = $('[data-gallery-upload]', box);
      input.onchange = () => editorFileTask(input, async () => {
        const files = [...input.files];
        if (files.length + work.galeria.length > 12) return toast('La galería admite hasta 12 fotografías adicionales.', 'err');
        for (const file of files) { const error = checkFile(file, 'image'); if (error) return toast(error, 'err'); }
        const added = [];
        for (const file of files) added.push({ src: await compressImage(file), alt: file.name.replace(/\.[^.]+$/, '').slice(0, 240) });
        if (ADM.work !== work) return;
        work.galeria.push(...added); rerender();
      });
    }
  };
}

/* Agrupa cargos por persona sin perder ninguna función. */
function institutionalTeam() {
  const people = new Map();
  const add = (person, job, director = false, hierarchy = false) => {
    if (!person) return;
    if (!people.has(person.id)) people.set(person.id, { p: person, cargos: [], esDirector: false, jerarquia: false });
    const item = people.get(person.id);
    if (!item.cargos.some(cargo => normTitle(cargo) === normTitle(job))) item.cargos.push(job);
    item.esDirector ||= director; item.jerarquia ||= hierarchy;
  };
  sortO(P('jerarquica').filter(isPub)).forEach(j => add(persona(j.personaId), j.cargo, !!j.esDirector, true));
  progs().forEach(program => add(persona(program.coordinadorId), `Coordinador(a) de ${program.nombre}`));
  return [...people.values()];
}

function institutionOrgHTML(entries) {
  const ids = new Set(entries.map(entry => entry.id));
  const children = parent => entries.filter(entry => entry.superiorId === parent);
  const node = (entry, ancestors = new Set()) => {
    if (ancestors.has(entry.id)) return '';
    const next = new Set([...ancestors, entry.id]);
    const person = persona(entry.personaId), branch = children(entry.id);
    return `<li><div class="org-node"><h3>${esc(entry.unidad || entry.cargo)}</h3>${entry.unidad && entry.unidad !== entry.cargo ? `<small>${esc(entry.cargo)}</small>` : ''}<p>${esc(person ? fullName(person) : '')}</p></div>${branch.length ? `<ul>${branch.map(child => node(child, next)).join('')}</ul>` : ''}</li>`;
  };
  const roots = entries.filter(entry => !entry.superiorId || !ids.has(entry.superiorId));
  const linked = roots.filter(entry => children(entry.id).length);
  const independent = roots.filter(entry => !children(entry.id).length);
  return `<section class="org-section" aria-labelledby="org-heading"><h2 id="org-heading">Organigrama institucional</h2>
    ${linked.length ? `<div class="org-scroll" tabindex="0" aria-label="Organigrama: desplázate para consultar sus dependencias"><ul class="org-tree">${linked.map(entry => node(entry)).join('')}</ul></div>` : ''}
    ${independent.length ? `<h3>Unidades institucionales</h3><div class="org-units">${independent.map(entry => `<section class="org-unit"><h3>${esc(entry.unidad || entry.cargo)}</h3><p>${esc(fullName(persona(entry.personaId)))}</p></section>`).join('')}</div>` : ''}
    <p><a href="#/nosotros/plana-jerarquica">Consultar el directorio institucional</a></p></section>`;
}

/* Solo se anuncian servicios cuya puesta en funcionamiento se confirmó en Configuración. */
const CHINCHA_SERVICES = [
  ['mesa-de-partes', 'Mesa de Partes Virtual'], ['libro-de-reclamaciones', 'Libro de Reclamaciones'],
  ['consulta-certificados', 'Consulta de certificados'], ['biblioteca', 'Biblioteca Virtual'],
  ['correo', 'Correo Institucional'], ['aula', 'Aula Virtual']
];
const institutionalServiceActive = key => Array.isArray(inst().serviciosActivos) && inst().serviciosActivos.includes(key);
const institutionalJobURL = () => {
  const url = inst().bolsaMineduUrl || ext('bolsa-chincha');
  return safePortalLink(url, false) ? url : '';
};
function institutionalPublicLink(url) {
  if (typeof url !== 'string') return false;
  if (/^#\/(?:transparencia\/)?libro-de-reclamaciones(?:[?#]|$)/.test(url)) return institutionalServiceActive('libro-de-reclamaciones');
  const key = url.match(/^#\/servicios\/([^?#]+)/)?.[1];
  if (key && CHINCHA_SERVICES.some(([id]) => id === key)) return institutionalServiceActive(key);
  for (const id of ['biblioteca', 'correo', 'aula']) if (ext(id) && url === ext(id)) return institutionalServiceActive(id);
  return true;
}
function institutionalServiceLinks() {
  return [{ label: 'Servicios', href: '#/servicios' },
    ...CHINCHA_SERVICES.filter(([key]) => institutionalServiceActive(key)).map(([key, label]) => ({ label, href: ['biblioteca', 'correo', 'aula'].includes(key) ? ext(key) : '#/servicios/' + key })).filter(item => item.href),
    { label: 'Bolsa laboral', href: '#/servicios/bolsa-laboral' }];
}
function institutionalServicesPage(path, bolsa = false) {
  const url = institutionalJobURL();
  const cards = sortO(P('servicios').filter(service => isPub(service) && service.implementado === true));
  const links = institutionalServiceLinks().filter(item => !['#/servicios', '#/servicios/bolsa-laboral'].includes(item.href));
  const body = `<h1>${bolsa ? 'Bolsa laboral' : 'Servicios'}</h1>
    ${!bolsa && (cards.length || links.length) ? `<div class="grid g2">${links.map(item => `<article class="card cb"><h2>${esc(item.label)}</h2><a class="btn" ${linkAttrs(item.href)}>Acceder</a></article>`).join('')}${cards.map(service => `<article class="card cb"><h2>${esc(service.nombre)}</h2><p>${esc(service.descripcion)}</p><dl class="kv"><dt>Ubicación</dt><dd>${esc(service.ubicacion)}</dd><dt>Horario</dt><dd>${esc(service.horario)}</dd><dt>Responsable</dt><dd>${esc(service.responsable)}</dd><dt>Costo</dt><dd>${esc(service.costo)}</dd></dl></article>`).join('')}</div>` : ''}
    <section class="card cb service-external"><h2>Bolsa laboral</h2><p>Consulta las oportunidades laborales en la plataforma externa.</p>${url ? `<a class="btn" href="${esc(url)}" target="_blank" rel="noopener noreferrer">Ir a Bolsa laboral ↗<span class="sr-only"> (se abre en una pestaña nueva)</span></a>` : '<p>El acceso a la plataforma estará disponible próximamente.</p>'}</section>`;
  return shell(path, { title: bolsa ? 'Bolsa laboral' : 'Servicios', crumbs: [['Servicios', '#/servicios'], ...(bolsa ? [['Bolsa laboral']] : [])], side: null, body });
}
R('servicios', path => institutionalServicesPage(path));
R('servicios/complementarios', path => institutionalServicesPage(path));
R('servicios/bolsa-laboral', path => institutionalServicesPage(path, true));
['mesa-de-partes', 'libro-de-reclamaciones', 'consulta-certificados'].forEach(key => {
  const original = ROUTES['servicios/' + key];
  R('servicios/' + key, (...args) => institutionalServiceActive(key) ? original(...args) : shell(args[0], {
    title: 'Servicio no disponible', crumbs: [['Servicios', '#/servicios']], side: null,
    body: '<h1>Servicio no disponible</h1><p>Este servicio todavía no está habilitado para la atención en línea.</p><a class="btn" href="#/contacto">Consultar con la institución</a>'
  }));
});

['transparencia/libro-de-reclamaciones', 'libro-de-reclamaciones'].forEach(alias => R(alias, (...args) => ROUTES['servicios/libro-de-reclamaciones'](...args)));

/* Los campos nuevos se integran en los editores existentes, sin duplicar sus flujos de guardado. */
SPECS.programas.fields.splice(SPECS.programas.fields.findIndex(field => field.n === 'perfil'), 0,
  institutionalGalleryField('Galería para Perfil de egreso y Campo laboral'));
const originalPageFields = SPECS.paginas.fields;
SPECS.paginas.fields = work => [...originalPageFields(work), ...(!['jerarquica', 'docentes', 'organizacion', 'privacidad', 'terminos', 'faq'].includes(work.clave) ? [institutionalGalleryField()] : []), ...(work.clave === 'organizacion' ? [{ n: 'organigrama', l: 'Organigrama oficial (imagen completa)', t: 'image', full: true, hint: 'Carga el organigrama aprobado. También puedes definir las dependencias entre cargos en Plana jerárquica.' }] : [])];
SPECS.jerarquica.fields.push({ n: 'superiorId', l: 'Depende jerárquicamente de', t: 'select', opts: work => alive('jerarquica').filter(entry => entry.id !== work.id).map(entry => [entry.id, entry.cargo]), hint: 'Selecciona únicamente la dependencia oficial. Déjalo vacío si todavía no está confirmada.' });
SPECS.servicios.fields.push({ n: 'implementado', l: 'Servicio implementado y disponible para atención', t: 'checkbox', full: true });
function recordGalleryDefaults(record) {
  if (record.brochure?.type?.startsWith('image/') && record.brochure.url) return [{ src: record.brochure.url, alt: 'Brochure de ' + record.nombre }];
  if (['presentacion', 'resena', 'mision', 'reconocimientos', 'campus'].includes(record.clave)) {
    return [
      { src: '/assets/chincha/sede-principal-bb3c09d0.webp', alt: 'Sede institucional del IESTP Chincha' },
      { src: '/assets/chincha/comunidad-chincha-d46b0f5b.jpg', alt: 'Comunidad del IESTP Chincha' }
    ].filter(item => item.src !== record.imagen);
  }
  return [];
}
