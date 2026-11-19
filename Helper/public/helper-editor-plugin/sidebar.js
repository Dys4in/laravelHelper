/* HELPER - herramientas fuera del iframe. La edición real se hace desde el complemento. */
(() => {
  'use strict';
  const channel = 'helper-editor-v1';
  const cfg = window.helperEditorSettings;
  const $ = id => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  let bridge = null;
  let fragments = [];
  let images = [];
  let pageGeometry = {widthMm:160,heightMm:240, measured:false};
  const MAX_IMAGES = 8;
  const pending = new Map();
  const status = (message, error = false) => {
    $('helperBridgeStatus').textContent = message;
    $('helperBridgeStatus').style.color = error ? '#b91c1c' : '#6b6d85';
  };

  document.querySelectorAll('[data-pane]').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-pane]').forEach(item => {
        const selected = item === tab;
        item.setAttribute('aria-selected', String(selected));
        $(item.dataset.pane).hidden = !selected;
      });
    });
  });

  window.addEventListener('message', event => {
    if (event.origin !== location.origin || !event.data || event.data.channel !== channel) return;
    const m = event.data;
    if (m.type === 'ready') {
      bridge = event.source;
      status('✓ Herramientas conectadas a OnlyOffice');
      updatePageGeometry();
    }
    if (['result', 'failure'].includes(m.type)) {
      // Validar que la respuesta proceda del iframe registrado.
      if (!bridge || event.source !== bridge) return;
      const item = pending.get(m.requestId);
      if (!item) return;
      clearTimeout(item.timer);
      pending.delete(m.requestId);
      if (m.type === 'failure') item.reject(new Error(m.payload?.error || 'La acción falló.'));
      else item.resolve(m.payload?.result);
    }
  });

  function command(action, payload = {}) {
    if (!cfg.enabled) return Promise.reject(new Error('El documento está en modo lectura.'));
    if (!bridge) return Promise.reject(new Error('El complemento de OnlyOffice aún no se conectó. Revisa README e intenta actualizar.'));
    if (pending.size) return Promise.reject(new Error('Hay una operación pendiente.'));
    const requestId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => { pending.delete(requestId); reject(new Error('OnlyOffice no respondió a tiempo.')); }, 30000);
      pending.set(requestId, { resolve, reject, timer });
      bridge.postMessage({channel, type:'command', action, payload, requestId}, location.origin);
    });
  }

  async function request(url, options = {}) {
    const res = await fetch(url, {
      credentials:'same-origin',
      headers: { 'Accept':'application/json', ...(options.body ? {'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf} : {'X-CSRF-TOKEN':csrf}) },
      ...options,
    });
    if (res.status === 419) throw new Error('Sesión caducada. Actualiza la página e inicia sesión nuevamente.');
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || `Error HTTP ${res.status}`);
    return data;
  }

  function renderSnippets() {
    const container = $('helperSnippetList');
    const q = $('helperSnippetSearch').value.toLocaleLowerCase('es');
    container.replaceChildren();
    const matching = fragments.filter(f => (f.title + ' ' + f.content).toLocaleLowerCase('es').includes(q));
    if (!matching.length) {
      const p = document.createElement('p');
      p.className = 'helper-tool-hint'; p.textContent = 'Todavía no hay fragmentos con ese nombre.';
      container.append(p);
      return;
    }
    matching.forEach(f => {
      const card = document.createElement('article'); card.className = 'helper-snippet';
      const name = document.createElement('div'); name.className = 'helper-snippet-title'; name.textContent = f.title;
      const preview = document.createElement('div'); preview.className = 'helper-snippet-text'; preview.textContent = f.content;
      const actions = document.createElement('div'); actions.className = 'helper-snippet-actions';
      const insert = document.createElement('button'); insert.type = 'button'; insert.textContent = 'Insertar en cursor'; insert.disabled = !cfg.enabled;
      insert.addEventListener('click', async () => {
        try { await command('pasteText', {content:f.content}); status('Fragmento insertado en el documento.'); }
        catch (error) { status(error.message, true); }
      });
      const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Eliminar'; remove.setAttribute('aria-label', `Eliminar ${f.title}`);
      remove.addEventListener('click', async () => {
        if (!confirm(`¿Eliminar el fragmento «${f.title}»?`)) return;
        try {
          await request(cfg.deleteBaseUrl + '/' + encodeURIComponent(f.id), {method:'DELETE'});
          fragments = fragments.filter(x => x.id !== f.id);
          renderSnippets(); status('Fragmento eliminado.');
        } catch (error) { status(error.message, true); }
      });
      actions.append(insert, remove); card.append(name, preview, actions); container.append(card);
    });
  }

  async function loadSnippets() {
    try { fragments = await request(cfg.listUrl); renderSnippets(); }
    catch (error) { status(error.message, true); }
  }

  $('helperSnippetSearch').addEventListener('input', renderSnippets);
  $('helperCapture').addEventListener('click', async () => {
    try {
      const selected = await command('selectedText');
      if (!selected || !selected.trim()) throw new Error('Primero selecciona un fragmento dentro del documento.');
      $('helperSnippetContent').value = selected;
      $('helperSnippetTitle').focus();
      status('Selección capturada. Escribe un nombre y pulsa Guardar.');
    } catch (error) { status(error.message, true); }
  });

  $('helperSnippetSave').addEventListener('click', async () => {
    const title = $('helperSnippetTitle').value.trim();
    const content = $('helperSnippetContent').value.trim();
    if (!title || !content) { status('Indica un nombre y captura un texto.', true); return; }
    try {
      const created = await request(cfg.storeUrl, { method:'POST', body:JSON.stringify({title, content}) });
      fragments.unshift(created);
      $('helperSnippetTitle').value = ''; $('helperSnippetContent').value = '';
      renderSnippets(); status('Contenido guardado en tu biblioteca.');
    } catch (error) { status(error.message, true); }
  });

  // Obtener dimensiones REALES del papel y márgenes (no posición vertical del cursor).
  async function updatePageGeometry() {
    try {
      const g = await command('pageGeometry');
      if (g && [g.widthMm,g.heightMm].every(n=>Number.isFinite(n) && n>40)) {
        pageGeometry = {...g, measured:true};
        $('helperPageDimensions').textContent = `Área útil de hoja: ${g.widthMm.toFixed(0)} × ${g.heightMm.toFixed(0)} mm (sin márgenes).`;
        updateImagePreview();
      }
    } catch (err) {
      $('helperPageDimensions').textContent = 'No se pudieron medir los márgenes; se utilizará un tamaño conservador.';
    }
  }
  // Los tres tamaños reducen AMBAS dimensiones disponibles. Antes se reducía
  // únicamente la altura y las imágenes horizontales seguían a ancho completo.
  const SIZE_SCALES = {small:0.38, medium:0.68, full:1};
  function sizeScale() { return SIZE_SCALES[$('helperImageSpace').value] ?? 1; }
  function imageFit(width, height, boxWidth, boxHeight) {
    if (!(width > 0 && height > 0 && boxWidth > 0 && boxHeight > 0)) {
      throw new Error('Tamaño de imagen o celda inválido.');
    }
    const scale = Math.min(boxWidth / width, boxHeight / height);
    return {widthMm:width * scale, heightMm:height * scale};
  }
  // El margen interior evita que la foto desborde los bordes de las celdas.
  // La altura útil reserva algo de espacio para separaciones y el pie de foto.
  function imageBox(mode, count, scale = sizeScale()) {
    const usableWidth = Math.max(50, pageGeometry.widthMm);
    const usableHeight = Math.max(60, pageGeometry.heightMm);
    const grid = mode === 'grid' && count > 1;
    const width = grid ? (usableWidth - 12) / 2 : usableWidth - 4;
    const height = grid ? (usableHeight - 28) / 2 :
      (count === 1 ? usableHeight - 18 : (usableHeight - 18) / Math.min(count, 4));
    return {widthMm:Math.max(10, width * scale), heightMm:Math.max(10, height * scale), grid, scale};
  }

  const imageList = $('helperImageList');
  const layoutField = $('helperImageLayout');
  const previewLayout = $('helperImageLayoutPreview');
  function announce(message, isError = false) { status(message, isError); }

  async function decodeFile(file) {
    const objectUrl = URL.createObjectURL(file);
    try {
      const image = new Image();
      image.src = objectUrl;
      await image.decode();
      if (!image.naturalWidth || !image.naturalHeight) throw new Error('No se pudo leer la imagen.');
      return {width:image.naturalWidth, height:image.naturalHeight};
    } finally { URL.revokeObjectURL(objectUrl); }
  }

  function layoutFor(items) {
    const requested = layoutField.value;
    if (requested !== 'auto') return requested;
    // Con dos fotos de proporciones semejantes se aprovecha el ancho en dos columnas.
    // Si alguna es muy panorámica o vertical, conviene conservarla a ancho completo.
    if (items.length < 2) return 'vertical';
    const ratios = items.map(x => x.width / x.height);
    return ratios.every(r => r >= .72 && r <= 1.65) ? 'grid' : 'vertical';
  }

  function updateImagePreview() {
    previewLayout.replaceChildren();
    const mode = layoutFor(images);
    const grid = mode === 'grid' && images.length > 1;
    const box = imageBox(mode, images.length || 1);
    const fullBox = imageBox(mode, images.length || 1, 1);
    previewLayout.classList.toggle('is-grid', grid);
    previewLayout.classList.toggle('is-vertical', !grid);
    // Cada celda de previsualización tiene el mismo tamaño; la foto se ajusta
    // dentro usando exactamente la misma función que para el documento.
    images.forEach((item, i) => {
      const tile = document.createElement('div');
      tile.className = 'helper-image-preview-cell';
      const thumb = document.createElement('img');
      thumb.src = item.preview;
      thumb.alt = `Vista previa imagen ${i + 1}`;
      const fit = imageFit(item.width, item.height, box.widthMm, box.heightMm);
      thumb.style.width = `${Math.min(100, fit.widthMm / fullBox.widthMm * 100)}%`;
      thumb.style.height = `${Math.min(100, fit.heightMm / fullBox.heightMm * 100)}%`;
      const number = document.createElement('small');
      number.textContent = `${i + 1} · ${fit.widthMm.toFixed(0)} × ${fit.heightMm.toFixed(0)} mm`;
      tile.append(thumb, number);
      previewLayout.append(tile);
    });
    if (!images.length) {
      const tile = document.createElement('div');
      tile.className = 'helper-image-preview-empty';
      tile.textContent = 'Agrega imágenes para ver el tamaño y la distribución';
      previewLayout.append(tile);
    }
    const hint = $('helperImageSizingInfo');
    if (hint) {
      const label = {small:'Pequeño',medium:'Mediano',full:'Grande'}[$('helperImageSpace').value] || 'Grande';
      hint.textContent = `${label}: límite de cada imagen ${box.widthMm.toFixed(0)} × ${box.heightMm.toFixed(0)} mm${grid?' dentro de cada celda 2×2':''}. Las proporciones se mantienen.`;
    }
  }

  function renderImages() {
    imageList.replaceChildren();
    images.forEach((item, index) => {
      const row = document.createElement('div'); row.className = 'helper-image-item';
      const thumb = document.createElement('img'); thumb.src = item.preview; thumb.alt = '';
      const info = document.createElement('div'); info.className = 'helper-image-item-info';
      const title = document.createElement('strong'); title.textContent = `${index + 1}. ${item.file.name}`;
      const detail = document.createElement('span'); detail.textContent = `${item.width} × ${item.height} px`;
      info.append(title, detail);
      const actions = document.createElement('div'); actions.className = 'helper-image-item-actions';
      [[-1,'↑','Subir'],[1,'↓','Bajar'],[0,'×','Quitar']].forEach(([move,symbol,label]) => {
        const button = document.createElement('button'); button.type='button'; button.textContent=symbol;
        button.title=label; button.setAttribute('aria-label', `${label} imagen ${index+1}`);
        button.disabled=move!==0 && (index+move<0 || index+move>=images.length);
        button.addEventListener('click', () => {
          if (move === 0) { const removed=images.splice(index,1)[0]; URL.revokeObjectURL(removed.preview); }
          else { [images[index], images[index+move]]=[images[index+move],images[index]]; }
          renderImages();
        });
        actions.append(button);
      });
      row.append(thumb,info,actions); imageList.append(row);
    });
    updateImagePreview();
  }

  async function addFiles(files) {
    for (const file of files) {
      if (images.length >= MAX_IMAGES) { announce(`Máximo ${MAX_IMAGES} imágenes por bloque.`,true); break; }
      if (!['image/png','image/jpeg','image/webp'].includes(file.type) || file.size > 4*1024*1024) {
        announce('Solo PNG, JPG o WebP hasta 4 MB por archivo.',true); continue;
      }
      try {
        const dimensions = await decodeFile(file);
        images.push({file, ...dimensions, preview:URL.createObjectURL(file)});
      } catch(err) { announce(err.message,true); }
    }
    renderImages();
    if (images.length) announce(`${images.length} imagen(es) listas. Puedes cambiar su orden.`);
  }

  $('helperImageFile').addEventListener('change', async event => {
    await addFiles(Array.from(event.target.files || []));
    event.target.value='';
  });
  layoutField.addEventListener('change',updateImagePreview);
  $('helperImageSpace').addEventListener('change',updateImagePreview);
  const drop = $('helperImageDrop');
  drop.addEventListener('dragover',event=>{event.preventDefault();drop.classList.add('is-dragover');});
  drop.addEventListener('dragleave',()=>drop.classList.remove('is-dragover'));
  drop.addEventListener('drop',async event=>{
    event.preventDefault(); drop.classList.remove('is-dragover');
    if (cfg.enabled) await addFiles(Array.from(event.dataTransfer?.files||[]));
  });
  $('helperImageClear').addEventListener('click',()=>{
    images.forEach(x=>URL.revokeObjectURL(x.preview)); images=[];renderImages();
    announce('Lista vaciada.');
  });

  // Cuando el foco está en el panel, Ctrl+V también puede añadir capturas.
  $('helperPaneImages').addEventListener('paste', async event => {
    if (!cfg.enabled) return;
    const clipboardFiles = Array.from(event.clipboardData?.items || [])
      .filter(item => item.kind === 'file' && item.type.startsWith('image/'))
      .map(item => item.getAsFile()).filter(Boolean);
    if (!clipboardFiles.length) return;
    event.preventDefault();
    await addFiles(clipboardFiles);
  });

  $('helperPasteScreenshot').addEventListener('click',async()=>{
    try {
      if (!cfg.enabled) throw new Error('El editor está en modo lectura.');
      if (!navigator.clipboard?.read) throw new Error('Tu navegador no permite leer imágenes del portapapeles. Usa HTTPS/localhost o carga el archivo.');
      const items=await navigator.clipboard.read();
      for (const item of items) {
        const type=item.types.find(x=>['image/png','image/jpeg','image/webp'].includes(x));
        if (!type) continue;
        const blob=await item.getType(type);
        const file=new File([blob],`captura-${Date.now()}.${type.split('/')[1]}`,{type});
        await addFiles([file]);
        return;
      }
      throw new Error('No encontré una imagen en el portapapeles. Usa Windows + Shift + S primero.');
    } catch(err){announce(err.message,true);}
  });

  async function prepareImage(item, maxWidthMm, maxHeightMm) {
    const url=URL.createObjectURL(item.file);
    try {
      const img=new Image(); img.src=url; await img.decode();
      const limitPx=1800;
      const scale=Math.min(1,limitPx/img.naturalWidth,limitPx/img.naturalHeight);
      const canvas=document.createElement('canvas');
      canvas.width=Math.max(1,Math.round(img.naturalWidth*scale));
      canvas.height=Math.max(1,Math.round(img.naturalHeight*scale));
      const ctx=canvas.getContext('2d');
      if (!ctx) throw new Error('No se pudo procesar la imagen.');
      ctx.fillStyle='#fff';ctx.fillRect(0,0,canvas.width,canvas.height);
      ctx.drawImage(img,0,0,canvas.width,canvas.height);
      const mime=item.file.type==='image/png'?'image/png':'image/jpeg';
      let src=canvas.toDataURL(mime,mime==='image/jpeg'?.84:undefined);
      if(src.length>3000000) src=canvas.toDataURL('image/jpeg',.76);
      if(src.length>4500000) throw new Error('Una imagen procesada sigue siendo demasiado grande.');
      const fit=imageFit(img.naturalWidth,img.naturalHeight,maxWidthMm,maxHeightMm);
      return {src,...fit,align:$('helperImageAlign').value};
    } finally {URL.revokeObjectURL(url);}
  }

  $('helperInsertImage').addEventListener('click',async()=>{
    if(!images.length){announce('Agrega una o varias imágenes primero.',true);return;}
    const button=$('helperInsertImage');button.disabled=true;
    try {
      // Consulta las dimensiones al momento de la inserción, por si cambió la orientación.
      if(bridge) await updatePageGeometry();
      const mode=layoutFor(images);
      const box = imageBox(mode, images.length);
      const grid = box.grid;
      const prepared=[];
      for(let i=0;i<images.length;i++){
        announce(`Adaptando imagen ${i+1} de ${images.length}…`);
        prepared.push(await prepareImage(images[i],box.widthMm,box.heightMm));
      }
      // En una cuadrícula el complemento separa las imágenes en páginas de cuatro.
      await command('insertImageBatch', {images:prepared,layout:mode,caption:$('helperImageCaption').value.trim().slice(0,180),gridPages:grid});
      announce(`${prepared.length} imagen(es) insertadas en tamaño ${$('helperImageSpace').selectedOptions[0].textContent.split(' — ')[0].toLowerCase()}.`);
    }catch(err){announce(err.message,true);}
    finally{button.disabled=!cfg.enabled;}
  });
  renderImages();

  loadSnippets();
  setTimeout(() => { if (!bridge) status('Complemento sin conexión: comprueba su instalación y acceso a la API de OnlyOffice.', true); }, 12000);
})();
