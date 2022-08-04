!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.drupalImage=t())}(globalThis,(()=>(()=>{var e={"ckeditor5/src/core.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/upload.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/upload.js")},"ckeditor5/src/utils.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/utils.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function i(s){var r=t[s];if(void 0!==r)return r.exports;var n=t[s]={exports:{}};return e[s](n,n.exports,i),n.exports}i.d=(e,t)=>{for(var s in t)i.o(t,s)&&!i.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var s={};return(()=>{"use strict";i.d(s,{default:()=>E});var e=i("ckeditor5/src/core.js");function t(e,t,i){if(t.attributes)for(const[s,r]of Object.entries(t.attributes))e.setAttribute(s,r,i);t.styles&&e.setStyle(t.styles,i),t.classes&&e.addClass(t.classes,i)}function r(e){return e.createEmptyElement("img")}function n(e){const t=parseFloat(e);return!Number.isNaN(t)&&e===String(t)}const o=[{modelValue:"alignCenter",dataValue:"center"},{modelValue:"alignRight",dataValue:"right"},{modelValue:"alignLeft",dataValue:"left"}];class a extends e.Plugin{static get requires(){return["ImageUtils"]}static get pluginName(){return"DrupalImageEditing"}init(){const{editor:e}=this,{conversion:i}=e,{schema:s}=e.model;s.isRegistered("imageInline")&&s.extend("imageInline",{allowAttributes:["dataEntityUuid","dataEntityType","isDecorative","width","height"]}),s.isRegistered("imageBlock")&&s.extend("imageBlock",{allowAttributes:["dataEntityUuid","dataEntityType","isDecorative","width","height"]}),i.for("upcast").add(function(e){function t(t,i,s){const{viewItem:r}=i,{writer:n,consumable:a,safeInsert:l,updateConversionResult:u,schema:c}=s,d=[];let m;if(!a.test(r,{name:!0,attributes:"src"}))return;const g=a.test(r,{name:!0,attributes:"data-caption"});if(m=c.checkChild(i.modelCursor,"imageInline")&&!g?n.createElement("imageInline",{src:r.getAttribute("src")}):n.createElement("imageBlock",{src:r.getAttribute("src")}),e.plugins.has("ImageStyleEditing")&&a.test(r,{name:!0,attributes:"data-align"})){const e=r.getAttribute("data-align"),t=o.find((t=>t.dataValue===e));t&&(n.setAttribute("imageStyle",t.modelValue,m),d.push("data-align"))}if(g){const t=n.createElement("caption"),i=e.data.processor.toView(r.getAttribute("data-caption")),o=n.createDocumentFragment();s.consumable.constructor.createFrom(i,s.consumable),s.convertChildren(i,o);for(const e of Array.from(o.getChildren()))n.append(e,t);n.append(t,m),d.push("data-caption")}a.test(r,{name:!0,attributes:"data-entity-uuid"})&&(n.setAttribute("dataEntityUuid",r.getAttribute("data-entity-uuid"),m),d.push("data-entity-uuid")),a.test(r,{name:!0,attributes:"data-entity-type"})&&(n.setAttribute("dataEntityType",r.getAttribute("data-entity-type"),m),d.push("data-entity-type")),l(m,i.modelCursor)&&(a.consume(r,{name:!0,attributes:d}),u(m,i))}return e=>{e.on("element:img",t,{priority:"high"})}}(e)).attributeToAttribute({view:{name:"img",key:"width"},model:{key:"width",value:e=>n(e.getAttribute("width"))?`${e.getAttribute("width")}px`:`${e.getAttribute("width")}`}}).attributeToAttribute({view:{name:"img",key:"height"},model:{key:"height",value:e=>n(e.getAttribute("height"))?`${e.getAttribute("height")}px`:`${e.getAttribute("height")}`}}),i.for("downcast").add(function(){function e(e,t,i){const{item:s}=t,{consumable:r,writer:n}=i;if(!r.consume(s,e.name))return;const o=i.mapper.toViewElement(s),a=Array.from(o.getChildren()).find((e=>"img"===e.name));n.setAttribute("data-entity-uuid",t.attributeNewValue,a||o)}return t=>{t.on("attribute:dataEntityUuid",e)}}()).add(function(){function e(e,t,i){const{item:s}=t,{consumable:r,writer:n}=i;if(!r.consume(s,e.name))return;const o=i.mapper.toViewElement(s),a=Array.from(o.getChildren()).find((e=>"img"===e.name));n.setAttribute("data-entity-type",t.attributeNewValue,a||o)}return t=>{t.on("attribute:dataEntityType",e)}}()),i.for("dataDowncast").add(function(e){return t=>{t.on("insert:caption",((t,i,s)=>{const{consumable:r,writer:n,mapper:o}=s;if(!e.plugins.get("ImageUtils").isImage(i.item.parent)||!r.consume(i.item,"insert"))return;const a=e.model.createRangeIn(i.item),l=n.createDocumentFragment();o.bindElements(i.item,l);for(const{item:t}of Array.from(a)){const i={item:t,range:e.model.createRangeOn(t)},r=`insert:${t.name||"$text"}`;e.data.downcastDispatcher.fire(r,i,s);for(const r of t.getAttributeKeys())Object.assign(i,{attributeKey:r,attributeOldValue:null,attributeNewValue:i.item.getAttribute(r)}),e.data.downcastDispatcher.fire(`attribute:${r}`,i,s)}for(const e of n.createRangeIn(l).getItems())o.unbindViewElement(e);o.unbindViewElement(l);const u=e.data.processor.toData(l);if(u){const e=o.toViewElement(i.item.parent);n.setAttribute("data-caption",u,e)}}),{priority:"high"})}}(e)).elementToElement({model:"imageBlock",view:(e,{writer:t})=>r(t),converterPriority:"high"}).elementToElement({model:"imageInline",view:(e,{writer:t})=>r(t),converterPriority:"high"}).add(function(){function e(e,t,i){const{item:s}=t,{consumable:r,writer:n}=i,a=o.find((e=>e.modelValue===t.attributeNewValue));if(!a||!r.consume(s,e.name))return;const l=i.mapper.toViewElement(s),u=Array.from(l.getChildren()).find((e=>"img"===e.name));n.setAttribute("data-align",a.dataValue,u||l)}return t=>{t.on("attribute:imageStyle",e,{priority:"high"})}}()).add(function(){function e(e,t,i){const{item:s}=t,{consumable:r,writer:n}=i;if(!r.consume(s,e.name))return;const o=i.mapper.toViewElement(s),a=Array.from(o.getChildren()).find((e=>"img"===e.name));n.setAttribute("width",t.attributeNewValue.replace("px",""),a||o)}return t=>{t.on("attribute:width:imageInline",e,{priority:"high"}),t.on("attribute:width:imageBlock",e,{priority:"high"})}}()).add(function(){function e(e,t,i){const{item:s}=t,{consumable:r,writer:n}=i;if(!r.consume(s,e.name))return;const o=i.mapper.toViewElement(s),a=Array.from(o.getChildren()).find((e=>"img"===e.name));n.setAttribute("height",t.attributeNewValue.replace("px",""),a||o)}return t=>{t.on("attribute:height:imageInline",e,{priority:"high"}),t.on("attribute:height:imageBlock",e,{priority:"high"})}}()).add(function(){function e(e,i,s){if(!s.consumable.consume(i.item,e.name))return;const r=s.mapper.toViewElement(i.item),n=s.writer,o=n.createContainerElement("a",{href:i.attributeNewValue});n.insert(n.createPositionBefore(r),o),n.move(n.createRangeOn(r),n.createPositionAt(o,0)),s.consumable.consume(i.item,"attribute:htmlLinkAttributes:imageBlock")&&t(s.writer,i.item.getAttribute("htmlLinkAttributes"),o)}return t=>{t.on("attribute:linkHref:imageBlock",e,{priority:"high"})}}())}}class l extends e.Command{refresh(){const e=this.editor.plugins.get("ImageUtils").getClosestSelectedImageElement(this.editor.model.document.selection);this.isEnabled=!!e,this.isEnabled&&e.hasAttribute("alt")?this.value=e.getAttribute("alt"):this.value=!1}execute(e){const t=this.editor,i=t.plugins.get("ImageUtils"),s=t.model,r=i.getClosestSelectedImageElement(s.document.selection);s.change((t=>{t.setAttribute("alt",e.newValue,r)}))}}class u extends e.Plugin{static get requires(){return["ImageUtils"]}static get pluginName(){return"DrupalImageAlternativeTextEditing"}constructor(e){super(e),this._missingAltTextViewReferences=new Set}init(){const e=this.editor;e.conversion.for("editingDowncast").add(this._imageEditingDowncastConverter("attribute:alt",e)).add(this._imageEditingDowncastConverter("attribute:src",e)),e.commands.add("imageTextAlternative",new l(this.editor)),e.editing.view.on("render",(()=>{for(const e of this._missingAltTextViewReferences)e.button.element.isConnected||(e.destroy(),this._missingAltTextViewReferences.delete(e))}))}_imageEditingDowncastConverter(e){const t=(e,t,i)=>{const s=this.editor;if(!s.plugins.get("ImageUtils").isImage(t.item))return;const r=i.mapper.toViewElement(t.item),n=Array.from(r.getChildren()).find((e=>e.getCustomProperty("drupalImageMissingAltWarning")));if(t.item.hasAttribute("alt"))return void(n&&i.writer.remove(n));if(n)return;const o=s.ui.componentFactory.create("drupalImageAlternativeTextMissing");o.listenTo(s.ui,"update",(()=>{const e=s.model.document.selection.getFirstRange(),i=s.model.createRangeOn(t.item);o.set({isSelected:e.containsRange(i)||e.isIntersecting(i)})})),o.render(),this._missingAltTextViewReferences.add(o);const a=i.writer.createUIElement("span",{class:"image-alternative-text-missing-wrapper"},(function(e){const t=this.toDomElement(e);return t.appendChild(o.element),t}));i.writer.setCustomProperty("drupalImageMissingAltWarning",!0,a),i.writer.insert(i.writer.createPositionAt(r,"end"),a)};return i=>{i.on(e,t,{priority:"low"})}}}var c=i("ckeditor5/src/ui.js");function d(e){const t=e.plugins.get("ContextualBalloon");if(e.plugins.get("ImageUtils").getClosestSelectedImageWidget(e.editing.view.document.selection)){const i=m(e);t.updatePosition(i)}}function m(e){const t=e.editing.view,i=c.BalloonPanelView.defaultPositions,s=e.plugins.get("ImageUtils");return{target:t.domConverter.mapViewToDom(s.getClosestSelectedImageWidget(t.document.selection)),positions:[i.northArrowSouth,i.northArrowSouthWest,i.northArrowSouthEast,i.southArrowNorth,i.southArrowNorthWest,i.southArrowNorthEast,i.viewportStickyNorth]}}var g=i("ckeditor5/src/utils.js");class h extends c.View{constructor(t){super(t),this.focusTracker=new g.FocusTracker,this.keystrokes=new g.KeystrokeHandler,this.decorativeToggle=this._decorativeToggleView(),this.labeledInput=this._createLabeledInputView(),this.saveButtonView=this._createButton(Drupal.t("Save"),e.icons.check,"ck-button-save"),this.saveButtonView.type="submit",this.saveButtonView.bind("isEnabled").to(this.decorativeToggle,"isOn",this.labeledInput,"isEmpty",((e,t)=>e||!t)),this.cancelButtonView=this._createButton(Drupal.t("Cancel"),e.icons.cancel,"ck-button-cancel","cancel"),this._focusables=new c.ViewCollection,this._focusCycler=new c.FocusCycler({focusables:this._focusables,focusTracker:this.focusTracker,keystrokeHandler:this.keystrokes,actions:{focusPrevious:"shift + tab",focusNext:"tab"}}),this.setTemplate({tag:"form",attributes:{class:["ck","ck-text-alternative-form","ck-text-alternative-form--with-decorative-toggle","ck-responsive-form"],tabindex:"-1"},children:[{tag:"div",attributes:{class:["ck","ck-text-alternative-form__decorative-toggle"]},children:[this.decorativeToggle]},this.labeledInput,this.saveButtonView,this.cancelButtonView]}),(0,c.injectCssTransitionDisabler)(this)}render(){super.render(),this.keystrokes.listenTo(this.element),(0,c.submitHandler)({view:this}),[this.decorativeToggle,this.labeledInput,this.saveButtonView,this.cancelButtonView].forEach((e=>{this._focusables.add(e),this.focusTracker.add(e.element)}))}destroy(){super.destroy(),this.focusTracker.destroy(),this.keystrokes.destroy()}_createButton(e,t,i,s){const r=new c.ButtonView(this.locale);return r.set({label:e,icon:t,tooltip:!0}),r.extendTemplate({attributes:{class:i}}),s&&r.delegate("execute").to(this,s),r}_createLabeledInputView(){const e=new c.LabeledFieldView(this.locale,c.createLabeledInputText);return e.bind("class").to(this.decorativeToggle,"isOn",(e=>e?"ck-hidden":"")),e.label=Drupal.t("Text alternative"),e}_decorativeToggleView(){const e=new c.SwitchButtonView(this.locale);return e.set({withText:!0,label:Drupal.t("Decorative image")}),e.on("execute",(()=>{e.set("isOn",!e.isOn)})),e}}class p extends c.View{constructor(e){super(e);const t=this.bindTemplate;this.set("isVisible"),this.set("isSelected");const i=Drupal.t("Add missing alternative text");this.button=new c.ButtonView(e),this.button.set({label:i,tooltip:!1,withText:!0}),this.setTemplate({tag:"span",attributes:{class:["image-alternative-text-missing",t.to("isVisible",(e=>e?"":"ck-hidden"))],title:i},children:[this.button]})}}class b extends e.Plugin{static get requires(){return[c.ContextualBalloon]}static get pluginName(){return"DrupalImageTextAlternativeUI"}init(){if(this._createButton(),this._createForm(),this._createMissingAltTextComponent(),this.editor.plugins.has("ImageUploadEditing")){const e=this.editor.plugins.get("ImageUploadEditing"),t=this.editor.plugins.get("ImageUtils");e.on("uploadComplete",(()=>{t.getClosestSelectedImageWidget(this.editor.editing.view.document.selection)&&this._showForm()}))}}_createMissingAltTextComponent(){this.editor.ui.componentFactory.add("drupalImageAlternativeTextMissing",(e=>{const t=new p(e);return t.listenTo(t.button,"execute",(()=>{this._isInBalloon&&this._balloon.remove(this._form),this._showForm()})),t.listenTo(this.editor.ui,"update",(()=>{t.set({isVisible:!this._isVisible||!t.isSelected})})),t}))}destroy(){super.destroy(),this._form.destroy()}_createButton(){const t=this.editor;t.ui.componentFactory.add("drupalImageAlternativeText",(i=>{const s=t.commands.get("imageTextAlternative"),r=new c.ButtonView(i);return r.set({label:Drupal.t("Change image alternative text"),icon:e.icons.lowVision,tooltip:!0}),r.bind("isEnabled").to(s,"isEnabled"),this.listenTo(r,"execute",(()=>{this._showForm()})),r}))}_createForm(){const e=this.editor,t=e.editing.view.document,i=e.plugins.get("ImageUtils");this._balloon=this.editor.plugins.get("ContextualBalloon"),this._form=new h(e.locale),this._form.render(),this.listenTo(this._form,"submit",(()=>{e.execute("imageTextAlternative",{newValue:this._form.decorativeToggle.isOn?"":this._form.labeledInput.fieldView.element.value}),this._hideForm(!0)})),this.listenTo(this._form,"cancel",(()=>{this._hideForm(!0)})),this.listenTo(this._form.decorativeToggle,"execute",(()=>{d(e)})),this._form.keystrokes.set("Esc",((e,t)=>{this._hideForm(!0),t()})),this.listenTo(e.ui,"update",(()=>{i.getClosestSelectedImageWidget(t.selection)?this._isVisible&&d(e):this._hideForm(!0)})),(0,c.clickOutsideHandler)({emitter:this._form,activator:()=>this._isVisible,contextElements:[this._balloon.view.element],callback:()=>this._hideForm()})}_showForm(){if(this._isVisible)return;const e=this.editor,t=e.commands.get("imageTextAlternative"),i=this._form.decorativeToggle,s=this._form.labeledInput;this._form.disableCssTransitions(),this._isInBalloon||this._balloon.add({view:this._form,position:m(e)}),i.isOn=""===t.value,s.fieldView.element.value=t.value||"",s.fieldView.value=s.fieldView.element.value,i.isOn?i.focus():s.fieldView.select(),this._form.enableCssTransitions()}_hideForm(e){this._isInBalloon&&(this._form.focusTracker.isFocused&&this._form.saveButtonView.focus(),this._balloon.remove(this._form),e&&this.editor.editing.view.focus())}get _isVisible(){return this._balloon.visibleView===this._form}get _isInBalloon(){return this._balloon.hasView(this._form)}}class f extends e.Plugin{static get requires(){return[u,b]}static get pluginName(){return"DrupalImageAlternativeText"}}class w extends e.Plugin{static get requires(){return[a,f]}static get pluginName(){return"DrupalImage"}}const v=w;class y extends e.Plugin{init(){const{editor:e}=this;e.plugins.get("ImageUploadEditing").on("uploadComplete",((t,{data:i,imageElement:s})=>{e.model.change((e=>{e.setAttribute("dataEntityUuid",i.response.uuid,s),e.setAttribute("dataEntityType",i.response.entity_type,s)}))}))}static get pluginName(){return"DrupalImageUploadEditing"}}var x=i("ckeditor5/src/upload.js");class _{constructor(e,t){this.loader=e,this.options=t}upload(){return this.loader.file.then((e=>new Promise(((t,i)=>{this._initRequest(),this._initListeners(t,i,e),this._sendRequest(e)}))))}abort(){this.xhr&&this.xhr.abort()}_initRequest(){this.xhr=new XMLHttpRequest,this.xhr.open("POST",this.options.uploadUrl,!0),this.xhr.responseType="json"}_initListeners(e,t,i){const s=this.xhr,r=this.loader,n=`Couldn't upload file: ${i.name}.`;s.addEventListener("error",(()=>t(n))),s.addEventListener("abort",(()=>t())),s.addEventListener("load",(()=>{const i=s.response;if(!i||i.error)return t(i&&i.error&&i.error.message?i.error.message:n);e({response:i,urls:{default:i.url}})})),s.upload&&s.upload.addEventListener("progress",(e=>{e.lengthComputable&&(r.uploadTotal=e.total,r.uploaded=e.loaded)}))}_sendRequest(e){const t=this.options.headers||{},i=this.options.withCredentials||!1;Object.keys(t).forEach((e=>{this.xhr.setRequestHeader(e,t[e])})),this.xhr.withCredentials=i;const s=new FormData;s.append("upload",e),this.xhr.send(s)}}class V extends e.Plugin{static get requires(){return[x.FileRepository]}static get pluginName(){return"DrupalFileRepository"}init(){const e=this.editor.config.get("drupalImageUpload");e&&(e.uploadUrl?this.editor.plugins.get(x.FileRepository).createUploadAdapter=t=>new _(t,e):(0,g.logWarning)("simple-upload-adapter-missing-uploadurl"))}}class A extends e.Plugin{static get requires(){return[V,y]}static get pluginName(){return"DrupalImageUpload"}}const E={DrupalImage:v,DrupalImageUpload:A}})(),s=s.default})()));