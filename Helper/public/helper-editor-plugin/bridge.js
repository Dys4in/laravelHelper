/* Complemento invisible de ONLYOFFICE: no intenta acceder al iframe desde Laravel. */
(function () {
    'use strict';
    const channel = 'helper-editor-v1';
    const origin = window.location.origin;
    let initialized = false;
    let busy = false;

    function inform(type, requestId, payload) {
        window.top.postMessage({ channel, type, requestId, payload }, origin);
    }

    function execute(name, args) {
        return new Promise((resolve, reject) => {
            try {
                if (!window.Asc?.plugin?.executeMethod) throw new Error('API del complemento no disponible.');
                const ok = window.Asc.plugin.executeMethod(name, args, result => resolve(result));
                if (ok === false) reject(new Error('OnlyOffice no aceptó el comando.'));
            } catch (e) { reject(e); }
        });
    }

    window.Asc = window.Asc || {};
    window.Asc.plugin = window.Asc.plugin || {};
    window.Asc.plugin.init = function () {
        initialized = true;
        inform('ready');
    };
    window.Asc.plugin.button = function () {};

    window.addEventListener('message', async function (event) {
        if (!initialized || event.origin !== origin || event.source !== window.top) return;
        const message = event.data;
        if (!message || message.channel !== channel || message.type !== 'command' || busy) return;
        busy = true;
        try {
            let result = null;
            if (message.action === 'selectedText') {
                result = await execute('GetSelectedText', [{Numbering:false, Math:false, ParaSeparator:'\n'}]);
            } else if (message.action === 'pasteText') {
                const content = String(message.payload?.content || '').slice(0, 20000);
                if (!content) throw new Error('El texto está vacío.');
                await execute('PasteText', [content]);
            } else if (message.action === 'pageGeometry') {
                result = await new Promise((resolve,reject) => {
                    try {
                        window.Asc.plugin.callCommand(function () {
                            const section=Api.GetDocument().GetFinalSection();
                            const mm=(twips)=>twips*25.4/1440;
                            const width=section.GetPageWidth()-section.GetPageMarginLeft()-section.GetPageMarginRight();
                            const height=section.GetPageHeight()-section.GetPageMarginTop()-section.GetPageMarginBottom();
                            return {widthMm:mm(width),heightMm:mm(height)};
                        },false,true,geometry=>resolve(geometry));
                    } catch(e) {reject(e);}
                });
            } else if (message.action === 'insertImageBatch') {
                const payload = message.payload || {};
                const items = payload.images;
                if (!Array.isArray(items) || !items.length || items.length > 8) throw new Error('Debes seleccionar entre 1 y 8 imágenes.');
                if (!['grid', 'vertical'].includes(payload.layout)) throw new Error('Distribución no válida.');
                const caption = String(payload.caption || '').slice(0, 180).replace(/[\u0000-\u001f]/g,' ');
                const checked = items.map(item => {
                    if (!/^data:image\/(?:png|jpeg);base64,[a-zA-Z0-9+/=]+$/.test(item.src || '') || item.src.length > 4500000) throw new Error('Imagen inválida o demasiado grande.');
                    if (![item.widthMm,item.heightMm].every(n=>Number.isFinite(n) && n>0) || item.widthMm>400 || item.heightMm>400) throw new Error('Las dimensiones exceden el espacio de la página.');
                    return {src:item.src,w:Math.round(item.widthMm*36000),h:Math.round(item.heightMm*36000),align:['left','center','right'].includes(item.align)?item.align:'center'};
                });
                if (checked.reduce((n,x)=>n+x.src.length,0)>17000000) throw new Error('El conjunto supera el tamaño máximo. Reduce el número de imágenes.');
                window.Asc.scope = window.Asc.scope || {};
                window.Asc.scope.helperBatch = {images:checked, layout:payload.layout, caption, gridPages:payload.gridPages===true};
                await new Promise((resolve,reject) => {
                    try {
                        window.Asc.plugin.callCommand(function () {
                            const input = Asc.scope.helperBatch;
                            const doc = Api.GetDocument();
                            const parts = [];
                            if (input.layout === 'grid' && input.images.length > 1) {
                                // Una cuadrícula inicia en hoja nueva; máximo 4 fotografías por hoja.
                                const initialBreak=Api.CreateParagraph();
                                initialBreak.AddPageBreak();
                                parts.push(initialBreak);
                                for (let start=0;start<input.images.length;start+=4) {
                                    if (start>0) {
                                        const pageBreak=Api.CreateParagraph();
                                        pageBreak.AddPageBreak();
                                        parts.push(pageBreak);
                                    }
                                    const group=input.images.slice(start,start+4);
                                    // Siempre crear una cuadrícula 2×2.
                                    // ONLYOFFICE cambió el orden de (columnas, filas) a
                                    // (filas, columnas) desde 9.4; 2×2 funciona en ambas APIs.
                                    // Las celdas no utilizadas quedan vacías si hay 2 o 3 fotos.
                                    const table=Api.CreateTable(2,2);
                                    table.SetWidth('percent',100);
                                    group.forEach((entry,i)=>{
                                        const cell=table.GetCell(Math.floor(i/2),i%2);
                                        if (!cell) throw new Error('No se pudo crear una celda de la cuadrícula.');
                                        const para=cell.GetContent().GetElement(0);
                                        para.SetJc('center');
                                        para.AddDrawing(Api.CreateImage(entry.src,entry.w,entry.h));
                                    });
                                    parts.push(table);
                                }
                            } else {
                                input.images.forEach(entry => {
                                    const para = Api.CreateParagraph();
                                    para.SetJc(entry.align);
                                    para.AddDrawing(Api.CreateImage(entry.src,entry.w,entry.h));
                                    parts.push(para);
                                });
                            }
                            if (input.caption) {
                                const para=Api.CreateParagraph();
                                para.SetJc('center');
                                para.AddText(input.caption);
                                parts.push(para);
                            }
                            doc.InsertContent(parts);
                        }, false, true, r => {
                            if(r?.onlyoffice_id_error_message) reject(new Error(r.onlyoffice_id_error_message));
                            else resolve(r);
                        });
                    } catch(e) {reject(e);}
                });
            } else if (message.action === 'insertImage') {
                const data = message.payload || {};
                if (!/^data:image\/png;base64,[a-zA-Z0-9+/=]+$/.test(data.src || '')) throw new Error('Imagen PNG no válida.');
                if (data.src.length > 5500000) throw new Error('Imagen demasiado grande.');
                if (!Number.isFinite(data.widthMm) || !Number.isFinite(data.heightMm) || data.widthMm < 1 || data.heightMm < 1 || data.widthMm > 170) throw new Error('Dimensiones inválidas.');
                const align = ['left', 'center', 'right'].includes(data.align) ? data.align : 'center';
                // El API de documentos usa EMU: 1 mm = 36.000 EMU.
                window.Asc.scope = window.Asc.scope || {};
                window.Asc.scope.helperImage = {src:data.src, width:Math.round(data.widthMm * 36000), height:Math.round(data.heightMm * 36000), align};
                await new Promise((resolve, reject) => {
                    try {
                        window.Asc.plugin.callCommand(function () {
                            const doc = Api.GetDocument();
                            const para = Api.CreateParagraph();
                            para.SetJc(Asc.scope.helperImage.align);
                            const picture = Api.CreateImage(Asc.scope.helperImage.src, Asc.scope.helperImage.width, Asc.scope.helperImage.height);
                            para.AddDrawing(picture);
                            doc.InsertContent([para]);
                        }, false, true, result => {
                            if (result?.onlyoffice_id_error_message) reject(new Error(result.onlyoffice_id_error_message));
                            else resolve(result);
                        });
                    } catch (e) { reject(e); }
                });
            } else {
                throw new Error('Acción no permitida.');
            }
            inform('result', message.requestId, {result});
        } catch (error) {
            inform('failure', message.requestId, {error: String(error?.message || error)});
        } finally { busy = false; }
    });
})();
