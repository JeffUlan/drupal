!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.drupalMedia=t())}(self,(function(){return(()=>{var e={"ckeditor5/src/core.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/utils.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/utils.js")},"ckeditor5/src/widget.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/widget.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function i(a){var n=t[a];if(void 0!==n)return n.exports;var r=t[a]={exports:{}};return e[a](r,r.exports,i),r.exports}i.d=(e,t)=>{for(var a in t)i.o(t,a)&&!i.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:t[a]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var a={};return(()=>{"use strict";i.d(a,{default:()=>X});var e=i("ckeditor5/src/core.js"),t=i("ckeditor5/src/widget.js");class n extends e.Command{execute(e){const t=this.editor.plugins.get("DrupalMediaEditing"),i=Object.entries(t.attrs).reduce(((e,[t,i])=>(e[i]=t,e)),{}),a=Object.keys(e).reduce(((t,a)=>(i[a]&&(t[i[a]]=e[a]),t)),{});if(this.editor.plugins.has("DrupalElementStyleEditing")){const t=this.editor.plugins.get("DrupalElementStyleEditing");for(const i of t.normalizedStyles)if(e[i.attributeName]&&i.attributeValue===e[i.attributeName]){a.drupalElementStyle=i.name;break}}this.editor.model.change((e=>{this.editor.model.insertContent(function(e,t){return e.createElement("drupalMedia",t)}(e,a))}))}refresh(){const e=this.editor.model,t=e.document.selection,i=e.schema.findAllowedParent(t.getFirstPosition(),"drupalMedia");this.isEnabled=null!==i}}function r(e){return!!e&&e.is("element","drupalMedia")}function l(e){const i=e.getSelectedElement();return i&&function(e){return(0,t.isWidget)(e)&&!!e.getCustomProperty("drupalMedia")}(i)?i:null}function o(e){const t=typeof e;return null!=e&&("object"===t||"function"===t)}function s(e){for(const t of e){if(t.hasAttribute("data-drupal-media-preview"))return t;if(t.childCount){const e=s(t.getChildren());if(e)return e}}return null}class d extends e.Plugin{static get requires(){return[t.Widget]}init(){this.attrs={drupalMediaAlt:"alt",drupalMediaCaption:"data-caption",drupalMediaEntityType:"data-entity-type",drupalMediaEntityUuid:"data-entity-uuid",drupalMediaViewMode:"data-view-mode"};const e=this.editor.config.get("drupalMedia");if(!e)return;const{previewURL:t,themeError:i}=e;this.previewUrl=t,this.labelError=Drupal.t("Preview failed"),this.themeError=i||`\n      <p>${Drupal.t("An error occurred while trying to preview the media. Please save your work and reload this page.")}<p>\n    `,this._defineSchema(),this._defineConverters(),this.editor.commands.add("insertDrupalMedia",new n(this.editor))}async _fetchPreview(e){const t={text:this._renderElement(e),uuid:e.getAttribute("drupalMediaEntityUuid")},i=await fetch(`${this.previewUrl}?${new URLSearchParams(t)}`,{headers:{"X-Drupal-MediaPreview-CSRF-Token":this.editor.config.get("drupalMedia").previewCsrfToken}});if(i.ok){return{label:i.headers.get("drupal-media-label"),preview:await i.text()}}return{label:this.labelError,preview:this.themeError}}_defineSchema(){this.editor.model.schema.register("drupalMedia",{allowWhere:"$block",isObject:!0,isContent:!0,allowAttributes:Object.keys(this.attrs)})}_defineConverters(){const e=this.editor.conversion;e.for("upcast").elementToElement({view:{name:"drupal-media"},model:"drupalMedia"}),e.for("dataDowncast").elementToElement({model:"drupalMedia",view:{name:"drupal-media"}}),e.for("editingDowncast").elementToElement({model:"drupalMedia",view:(e,{writer:i})=>{const a=i.createContainerElement("div",{class:"drupal-media"});if(!this.previewUrl){const e=i.createRawElement("div",{"data-drupal-media-preview":"unavailable"});i.insert(i.createPositionAt(a,0),e)}return i.setCustomProperty("drupalMedia",!0,a),(0,t.toWidget)(a,i,{label:Drupal.t("Media widget")})}}).add((e=>{const t=(e,t,i)=>{const a=i.writer,n=t.item,r=i.mapper.toViewElement(t.item);let l=s(r.getChildren());if(l){if("ready"!==l.getAttribute("data-drupal-media-preview"))return;a.setAttribute("data-drupal-media-preview","loading",l)}else l=a.createRawElement("div",{"data-drupal-media-preview":"loading"}),a.insert(a.createPositionAt(r,0),l);this._fetchPreview(n).then((({label:e,preview:t})=>{l&&this.editor.editing.view.change((i=>{const a=i.createRawElement("div",{"data-drupal-media-preview":"ready","aria-label":e},(e=>{e.innerHTML=t}));i.insert(i.createPositionBefore(l),a),i.remove(l)}))}))};return e.on("attribute:drupalMediaEntityUuid:drupalMedia",t),e.on("attribute:drupalMediaViewMode:drupalMedia",t),e.on("attribute:drupalMediaEntityType:drupalMedia",t),e.on("attribute:drupalMediaAlt:drupalMedia",t),e})),e.for("editingDowncast").add((e=>{e.on("attribute:drupalElementStyle:drupalMedia",((e,t,i)=>{const a={alignLeft:"drupal-media-style-align-left",alignRight:"drupal-media-style-align-right",alignCenter:"drupal-media-style-align-center"},n=i.mapper.toViewElement(t.item),r=i.writer;a[t.attributeOldValue]&&r.removeClass(a[t.attributeOldValue],n),a[t.attributeNewValue]&&i.consumable.consume(t.item,e.name)&&r.addClass(a[t.attributeNewValue],n)}))})),Object.keys(this.attrs).forEach((t=>{const i={model:{key:t,name:"drupalMedia"},view:{name:"drupal-media",key:this.attrs[t]}};e.for("dataDowncast").attributeToAttribute(i),e.for("upcast").attributeToAttribute(i)}))}_renderElement(e){const t=e.getAttributes();let i="<drupal-media";return Array.from(t).forEach((e=>{this.attrs[e[0]]&&"drupalMediaCaption"!==e[0]&&(i+=` ${this.attrs[e[0]]}="${e[1]}"`)})),i+="></drupal-media>",i}static get pluginName(){return"DrupalMediaEditing"}}var u=i("ckeditor5/src/ui.js");class c extends e.Plugin{init(){const e=this.editor,t=this.editor.config.get("drupalMedia");if(!t)return;const{libraryURL:i,openDialog:a,dialogSettings:n={}}=t;i&&"function"==typeof a&&e.ui.componentFactory.add("drupalMedia",(t=>{const r=e.commands.get("insertDrupalMedia"),l=new u.ButtonView(t);return l.set({label:Drupal.t("Insert Drupal Media"),icon:'<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.1873 4.86414L10.2509 6.86414V7.02335H10.2499V15.5091C9.70972 15.1961 9.01793 15.1048 8.34069 15.3136C7.12086 15.6896 6.41013 16.8967 6.75322 18.0096C7.09631 19.1226 8.3633 19.72 9.58313 19.344C10.6666 19.01 11.3484 18.0203 11.2469 17.0234H11.2499V9.80173L18.1803 8.25067V14.3868C17.6401 14.0739 16.9483 13.9825 16.2711 14.1913C15.0513 14.5674 14.3406 15.7744 14.6836 16.8875C15.0267 18.0004 16.2937 18.5978 17.5136 18.2218C18.597 17.8877 19.2788 16.8982 19.1773 15.9011H19.1803V8.02687L19.1873 8.0253V4.86414Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13.5039 0.743652H0.386932V12.1603H13.5039V0.743652ZM12.3379 1.75842H1.55289V11.1454H1.65715L4.00622 8.86353L6.06254 10.861L9.24985 5.91309L11.3812 9.22179L11.7761 8.6676L12.3379 9.45621V1.75842ZM6.22048 4.50869C6.22048 5.58193 5.35045 6.45196 4.27722 6.45196C3.20398 6.45196 2.33395 5.58193 2.33395 4.50869C2.33395 3.43546 3.20398 2.56543 4.27722 2.56543C5.35045 2.56543 6.22048 3.43546 6.22048 4.50869Z" fill="black"/></svg>\n',tooltip:!0}),l.bind("isOn","isEnabled").to(r,"value","isEnabled"),this.listenTo(l,"execute",(()=>{a(i,(({attributes:t})=>{e.execute("insertDrupalMedia",t)}),n)})),l}))}}class m extends e.Plugin{static get requires(){return[t.WidgetToolbarRepository]}static get pluginName(){return"DrupalMediaToolbar"}afterInit(){const{editor:e}=this;var i;e.plugins.get(t.WidgetToolbarRepository).register("drupalMedia",{ariaLabel:Drupal.t("Drupal Media toolbar"),items:(i=e.config.get("drupalMedia.toolbar"),i.map((e=>o(e)?e.name:e))||[]),getRelatedElement:e=>l(e)})}}const g="METADATA_ERROR";class p extends e.Command{refresh(){const e=this.editor.model.document.selection.getSelectedElement();this.isEnabled=r(e)&&e.getAttribute("drupalMediaIsImage")&&e.getAttribute("drupalMediaIsImage")!==g,r(e)&&e.hasAttribute("drupalMediaAlt")?this.value=e.getAttribute("drupalMediaAlt"):this.value=!1}execute(e){const{model:t}=this.editor,i=t.document.selection.getSelectedElement();e.newValue=e.newValue.trim(),t.change((t=>{e.newValue.length>0?t.setAttribute("drupalMediaAlt",e.newValue,i):t.removeAttribute("drupalMediaAlt",i)}))}}class h extends e.Plugin{init(){this._data=new WeakMap}getMetadata(e){if(this._data.get(e))return new Promise((t=>{t(this._data.get(e))}));const t=this.editor.config.get("drupalMedia");if(!t)return new Promise(((e,t)=>{t(new Error("drupalMedia configuration is required for parsing metadata."))}));if(!e.hasAttribute("drupalMediaEntityUuid"))return new Promise(((e,t)=>{t(new Error("drupalMedia element must have drupalMediaEntityUuid attribute to retrieve metadata."))}));const{metadataUrl:i}=t;return(async e=>{const t=await fetch(e);if(t.ok)return JSON.parse(await t.text());throw new Error("Fetching media embed metadata from the server failed.")})(`${i}&${new URLSearchParams({uuid:e.getAttribute("drupalMediaEntityUuid")})}`).then((t=>(this._data.set(e,t),t)))}static get pluginName(){return"DrupalMediaMetadataRepository"}}class f extends e.Plugin{static get requires(){return[h]}static get pluginName(){return"MediaImageTextAlternativeEditing"}_upcastDrupalMediaIsImage(e){const{model:t,plugins:i}=this.editor;i.get("DrupalMediaMetadataRepository").getMetadata(e).then((i=>{e&&t.enqueueChange("transparent",(t=>{t.setAttribute("drupalMediaIsImage",!!i.imageSourceMetadata,e)}))})).catch((i=>{e&&(console.warn(i.toString()),t.enqueueChange("transparent",(t=>{t.setAttribute("drupalMediaIsImage",g,e)})))}))}init(){const{editor:e,editor:{model:t,conversion:i}}=this;t.schema.extend("drupalMedia",{allowAttributes:["drupalMediaIsImage"]}),this.listenTo(t,"insertContent",((e,[t])=>{r(t)&&this._upcastDrupalMediaIsImage(t)})),i.for("upcast").add((e=>{e.on("element:drupal-media",((e,t)=>{const[i]=t.modelRange.getItems();r(i)&&this._upcastDrupalMediaIsImage(i)}),{priority:"lowest"})})),i.for("editingDowncast").add((e=>{e.on("attribute:drupalMediaIsImage",((e,t,i)=>{const{writer:a,mapper:n}=i,r=n.toViewElement(t.item);if(t.attributeNewValue!==g){const e=Array.from(r.getChildren()).find((e=>e.getCustomProperty("drupalMediaMetadataError")));return void(e&&(a.setCustomProperty("widgetLabel",e.getCustomProperty("drupalMediaOriginalWidgetLabel"),e),a.removeElement(e)))}const l=Drupal.t("Not all functionality may be available because some information could not be retrieved."),o=new u.TooltipView;o.text=l,o.position="sw";const s=new u.Template({tag:"span",children:[{tag:"span",attributes:{class:"drupal-media__metadata-error-icon"}},o]}).render(),d=a.createRawElement("div",{class:"drupal-media__metadata-error"},((e,t)=>{t.setContentOf(e,s.outerHTML)}));a.setCustomProperty("drupalMediaMetadataError",!0,d);const c=r.getCustomProperty("widgetLabel");a.setCustomProperty("drupalMediaOriginalWidgetLabel",c,d),a.setCustomProperty("widgetLabel",`${c} (${l})`,r),a.insert(a.createPositionAt(r,0),d)}),{priority:"low"})})),e.commands.add("mediaImageTextAlternative",new p(this.editor))}}function b(e){const t=e.editing.view,i=u.BalloonPanelView.defaultPositions;return{target:t.domConverter.viewToDom(t.document.selection.getSelectedElement()),positions:[i.northArrowSouth,i.northArrowSouthWest,i.northArrowSouthEast,i.southArrowNorth,i.southArrowNorthWest,i.southArrowNorthEast]}}var w=i("ckeditor5/src/utils.js");class y extends u.View{constructor(t){super(t),this.focusTracker=new w.FocusTracker,this.keystrokes=new w.KeystrokeHandler,this.labeledInput=this._createLabeledInputView(),this.saveButtonView=this._createButton(Drupal.t("Save"),e.icons.check,"ck-button-save"),this.saveButtonView.type="submit",this.cancelButtonView=this._createButton(Drupal.t("Cancel"),e.icons.cancel,"ck-button-cancel","cancel"),this._focusables=new u.ViewCollection,this._focusCycler=new u.FocusCycler({focusables:this._focusables,focusTracker:this.focusTracker,keystrokeHandler:this.keystrokes,actions:{focusPrevious:"shift + tab",focusNext:"tab"}}),this.setTemplate({tag:"form",attributes:{class:["ck","ck-text-alternative-form","ck-responsive-form"],tabindex:"-1"},children:[this.labeledInput,this.saveButtonView,this.cancelButtonView]}),(0,u.injectCssTransitionDisabler)(this)}render(){super.render(),this.keystrokes.listenTo(this.element),(0,u.submitHandler)({view:this}),[this.labeledInput,this.saveButtonView,this.cancelButtonView].forEach((e=>{this._focusables.add(e),this.focusTracker.add(e.element)}))}_createButton(e,t,i,a){const n=new u.ButtonView(this.locale);return n.set({label:e,icon:t,tooltip:!0}),n.extendTemplate({attributes:{class:i}}),a&&n.delegate("execute").to(this,a),n}_createLabeledInputView(){const e=new u.LabeledFieldView(this.locale,u.createLabeledInputText);return e.label=Drupal.t("Override text alternative"),e}}class E extends e.Plugin{static get requires(){return[u.ContextualBalloon]}static get pluginName(){return"MediaImageTextAlternativeUi"}init(){this._createButton(),this._createForm()}destroy(){super.destroy(),this._form.destroy()}_createButton(){const t=this.editor;t.ui.componentFactory.add("mediaImageTextAlternative",(i=>{const a=t.commands.get("mediaImageTextAlternative"),n=new u.ButtonView(i);return n.set({label:Drupal.t("Override media image text alternative"),icon:e.icons.lowVision,tooltip:!0}),n.bind("isVisible").to(a,"isEnabled"),this.listenTo(n,"execute",(()=>{this._showForm()})),n}))}_createForm(){const e=this.editor,t=e.editing.view.document;this._balloon=this.editor.plugins.get("ContextualBalloon"),this._form=new y(e.locale),this._form.render(),this.listenTo(this._form,"submit",(()=>{e.execute("mediaImageTextAlternative",{newValue:this._form.labeledInput.fieldView.element.value}),this._hideForm(!0)})),this.listenTo(this._form,"cancel",(()=>{this._hideForm(!0)})),this._form.keystrokes.set("Esc",((e,t)=>{this._hideForm(!0),t()})),this.listenTo(e.ui,"update",(()=>{l(t.selection)?this._isVisible&&function(e){const t=e.plugins.get("ContextualBalloon");if(l(e.editing.view.document.selection)){const i=b(e);t.updatePosition(i)}}(e):this._hideForm(!0)})),(0,u.clickOutsideHandler)({emitter:this._form,activator:()=>this._isVisible,contextElements:[this._balloon.view.element],callback:()=>this._hideForm()})}_showForm(){if(this._isVisible)return;const e=this.editor,t=e.commands.get("mediaImageTextAlternative"),i=this._form.labeledInput;this._form.disableCssTransitions(),this._isInBalloon||this._balloon.add({view:this._form,position:b(e)}),i.fieldView.element.value=t.value||"",i.fieldView.value=i.fieldView.element.value,this._form.labeledInput.fieldView.select(),this._form.enableCssTransitions()}_hideForm(e){this._isInBalloon&&(this._form.focusTracker.isFocused&&this._form.saveButtonView.focus(),this._balloon.remove(this._form),e&&this.editor.editing.view.focus())}get _isVisible(){return this._balloon.visibleView===this._form}get _isInBalloon(){return this._balloon.hasView(this._form)}}class v extends e.Plugin{static get requires(){return[f,E]}static get pluginName(){return"MediaImageTextAlternative"}}function M(e,t,i){if(t.attributes)for(const[a,n]of Object.entries(t.attributes))e.setAttribute(a,n,i);t.styles&&e.setStyle(t.styles,i),t.classes&&e.addClass(t.classes,i)}function k(e){return t=>{t.on("element:drupal-media",((t,i,a)=>{const n=i.viewItem.parent;n.is("element","a")&&function(t,n){const r=e._consumeAllowedAttributes(t,a);r&&a.writer.setAttribute(n,r,i.modelRange)}(n,"htmlLinkAttributes")}),{priority:"low"})}}class A extends e.Plugin{init(){const{editor:e}=this;if(!e.plugins.has("GeneralHtmlSupport"))return;const{schema:t}=e.model,{conversion:i}=e,a=e.plugins.get("DataFilter");t.extend("drupalMedia",{allowAttributes:["htmlLinkAttributes"]}),i.for("upcast").add(k(a)),i.for("editingDowncast").add((e=>e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{if(!i.consumable.consume(t.item,"attribute:htmlLinkAttributes:drupalMedia"))return;const a=i.mapper.toViewElement(t.item),n=function(e,t,i){const a=e.createRangeOn(t);for(const{item:e}of a.getWalker())if(e.is("element",i))return e}(i.writer,a,"a");M(i.writer,t.item.getAttribute("htmlLinkAttributes"),n)}),{priority:"low"}))),i.for("dataDowncast").add((e=>e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{if(!i.consumable.consume(t.item,"attribute:htmlLinkAttributes:drupalMedia"))return;const a=i.mapper.toViewElement(t.item).parent;M(i.writer,t.item.getAttribute("htmlLinkAttributes"),a)}),{priority:"low"})))}static get pluginName(){return"DrupalMediaGeneralHtmlSupport"}}class _ extends e.Plugin{static get requires(){return[d,A,c,m,v]}static get pluginName(){return"DrupalMedia"}}function C(){return e=>{e.on("element:a",((e,t,i)=>{const a=t.viewItem,n=(r=a,Array.from(r.getChildren()).find((e=>"drupal-media"===e.name)));var r;if(!n)return;if(!i.consumable.consume(a,{attributes:["href"]}))return;const l=a.getAttribute("href");if(!l)return;const o=i.convertItem(n,t.modelCursor);t.modelRange=o.modelRange,t.modelCursor=o.modelCursor;const s=t.modelCursor.nodeBefore;s&&s.is("element","drupalMedia")&&i.writer.setAttribute("linkHref",l,s)}),{priority:"high"})}}class x extends e.Plugin{static get requires(){return["LinkEditing","DrupalMediaEditing"]}static get pluginName(){return"DrupalLinkMediaEditing"}init(){const{editor:e}=this;e.model.schema.extend("drupalMedia",{allowAttributes:["linkHref"]}),e.conversion.for("upcast").add(C()),e.conversion.for("editingDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{const{writer:a}=i;if(!i.consumable.consume(t.item,e.name))return;const n=i.mapper.toViewElement(t.item),r=Array.from(n.getChildren()).find((e=>"a"===e.name));if(r)t.attributeNewValue?a.setAttribute("href",t.attributeNewValue,r):(a.move(a.createRangeIn(r),a.createPositionAt(n,0)),a.remove(r));else{const e=Array.from(n.getChildren()).find((e=>e.getAttribute("data-drupal-media-preview"))),i=a.createContainerElement("a",{href:t.attributeNewValue});a.insert(a.createPositionAt(n,0),i),a.move(a.createRangeOn(e),a.createPositionAt(i,0))}}),{priority:"high"})})),e.conversion.for("dataDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{const{writer:a}=i;if(!i.consumable.consume(t.item,e.name))return;const n=i.mapper.toViewElement(t.item),r=a.createContainerElement("a",{href:t.attributeNewValue});a.insert(a.createPositionBefore(n),r),a.move(a.createRangeOn(n),a.createPositionAt(r,0))}),{priority:"high"})}))}}class S extends e.Plugin{static get requires(){return["LinkEditing","LinkUI","DrupalMediaEditing"]}static get pluginName(){return"DrupalLinkMediaUi"}init(){const{editor:e}=this,t=e.editing.view.document;this.listenTo(t,"click",((t,i)=>{this._isSelectedLinkedMedia(e.model.document.selection)&&(i.preventDefault(),t.stop())}),{priority:"high"}),this._createToolbarLinkMediaButton()}_createToolbarLinkMediaButton(){const{editor:e}=this;e.ui.componentFactory.add("drupalLinkMedia",(t=>{const i=new u.ButtonView(t),a=e.plugins.get("LinkUI"),n=e.commands.get("link");return i.set({isEnabled:!0,label:Drupal.t("Link media"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m11.077 15 .991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955.741.741 0 0 1 .12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184z"/></svg>\n',keystroke:"Ctrl+K",tooltip:!0,isToggleable:!0}),i.bind("isEnabled").to(n,"isEnabled"),i.bind("isOn").to(n,"value",(e=>!!e)),this.listenTo(i,"execute",(()=>{this._isSelectedLinkedMedia(e.model.document.selection)?a._addActionsView():a._showUI(!0)})),i}))}_isSelectedLinkedMedia(e){const t=e.getSelectedElement();return!!t&&t.is("element","drupalMedia")&&t.hasAttribute("linkHref")}}class V extends e.Plugin{static get requires(){return[x,S]}static get pluginName(){return"DrupalLinkMedia"}}const{objectFullWidth:I,objectInline:D,objectLeft:L,objectRight:T,objectCenter:B,objectBlockLeft:P,objectBlockRight:N}=e.icons,O={inline:{name:"inline",title:"In line",icon:D,modelElements:["imageInline"],isDefault:!0},alignLeft:{name:"alignLeft",title:"Left aligned image",icon:L,modelElements:["imageBlock","imageInline"],className:"image-style-align-left"},alignBlockLeft:{name:"alignBlockLeft",title:"Left aligned image",icon:P,modelElements:["imageBlock"],className:"image-style-block-align-left"},alignCenter:{name:"alignCenter",title:"Centered image",icon:B,modelElements:["imageBlock"],className:"image-style-align-center"},alignRight:{name:"alignRight",title:"Right aligned image",icon:T,modelElements:["imageBlock","imageInline"],className:"image-style-align-right"},alignBlockRight:{name:"alignBlockRight",title:"Right aligned image",icon:N,modelElements:["imageBlock"],className:"image-style-block-align-right"},block:{name:"block",title:"Centered image",icon:B,modelElements:["imageBlock"],isDefault:!0},side:{name:"side",title:"Side image",icon:T,modelElements:["imageBlock"],className:"image-style-side"}},R={full:I,left:P,right:N,center:B,inlineLeft:L,inlineRight:T,inline:D},j=[{name:"imageStyle:wrapText",title:"Wrap text",defaultItem:"imageStyle:alignLeft",items:["imageStyle:alignLeft","imageStyle:alignRight"]},{name:"imageStyle:breakText",title:"Break text",defaultItem:"imageStyle:block",items:["imageStyle:alignBlockLeft","imageStyle:block","imageStyle:alignBlockRight"]}];function F(e){(0,w.logWarning)("image-style-configuration-definition-invalid",e)}const U={normalizeStyles:function(e){return(e.configuredStyles.options||[]).map((e=>function(e){e="string"==typeof e?O[e]?{...O[e]}:{name:e}:function(e,t){const i={...t};for(const a in e)Object.prototype.hasOwnProperty.call(t,a)||(i[a]=e[a]);return i}(O[e.name],e);"string"==typeof e.icon&&(e.icon=R[e.icon]||e.icon);return e}(e))).filter((t=>function(e,{isBlockPluginLoaded:t,isInlinePluginLoaded:i}){const{modelElements:a,name:n}=e;if(!(a&&a.length&&n))return F({style:e}),!1;{const n=[t?"imageBlock":null,i?"imageInline":null];if(!a.some((e=>n.includes(e))))return(0,w.logWarning)("image-style-missing-dependency",{style:e,missingPlugins:a.map((e=>"imageBlock"===e?"ImageBlockEditing":"ImageInlineEditing"))}),!1}return!0}(t,e)))},getDefaultStylesConfiguration:function(e,t){return e&&t?{options:["inline","alignLeft","alignRight","alignCenter","alignBlockLeft","alignBlockRight","block","side"]}:e?{options:["block","side"]}:t?{options:["inline","alignLeft","alignRight"]}:{}},getDefaultDropdownDefinitions:function(e){return e.has("ImageBlockEditing")&&e.has("ImageInlineEditing")?[...j]:[]},warnInvalidStyle:F,DEFAULT_OPTIONS:O,DEFAULT_ICONS:R,DEFAULT_DROPDOWN_DEFINITIONS:j};function H(e,t){const i=e.getSelectedElement();return i&&t.checkAttribute(i,"drupalElementStyle")?i:e.getFirstPosition().findAncestor((e=>t.checkAttribute(e,"drupalElementStyle")))}class W extends e.Command{constructor(e,t){super(e),this._styles=new Map(t.map((e=>[e.name,e])))}refresh(){const e=this.editor,t=H(e.model.document.selection,e.model.schema);this.isEnabled=!!t,this.isEnabled&&t.hasAttribute("drupalElementStyle")?this.value=t.getAttribute("drupalElementStyle"):this.value=!1}execute(e={}){const t=this.editor.model;t.change((i=>{const a=e.value,n=H(t.document.selection,t.schema);!a||this._styles.get(a).isDefault?i.removeAttribute("drupalElementStyle",n):i.setAttribute("drupalElementStyle",a,n)}))}}function q(e,t){for(const i of t)if(i.name===e)return i}class K extends e.Plugin{init(){const t=this.editor;t.config.define("drupalElementStyles",{options:[]});const i=t.config.get("drupalElementStyles").options;this.normalizedStyles=i.map((t=>("string"==typeof t.icon&&e.icons[t.icon]&&(t.icon=e.icons[t.icon]),t))).filter((e=>e.attributeName&&e.attributeValue?e.modelElements&&Array.isArray(e.modelElements)?!!e.name||(console.warn("drupalElementStyles options must include a name."),!1):(console.warn("drupalElementStyles options must include an array of supported modelElements."),!1):(console.warn("drupalElementStyles options must include attributeName and attributeValue."),!1))),this._setupConversion(),t.commands.add("drupalElementStyle",new W(t,this.normalizedStyles))}_setupConversion(){const e=this.editor,t=e.model.schema,i=(a=this.normalizedStyles,(e,t,i)=>{if(!i.consumable.consume(t.item,e.name))return;const n=q(t.attributeNewValue,a),r=q(t.attributeOldValue,a),l=i.mapper.toViewElement(t.item),o=i.writer;r&&("class"===r.attributeName?o.removeClass(r.attributeValue,l):o.removeAttribute(r.attributeName,l)),n&&("class"===n.attributeName?o.addClass(n.attributeValue,l):o.setAttribute(n.attributeName,n.attributeValue,l))});var a;const n=function(e){const t=e.filter((e=>!e.isDefault));return(e,i,a)=>{if(!i.modelRange)return;const n=i.viewItem,r=(0,w.first)(i.modelRange.getItems());if(r&&a.schema.checkAttribute(r,"drupalElementStyle"))for(const e of t)if("class"===e.attributeName)a.consumable.consume(n,{classes:e.attributeValue})&&a.writer.setAttribute("drupalElementStyle",e.name,r);else if(a.consumable.consume(n,{attributes:[e.attributeName]}))for(const e of t)e.attributeValue===n.getAttribute(e.attributeName)&&a.writer.setAttribute("drupalElementStyle",e.name,r)}}(this.normalizedStyles);e.editing.downcastDispatcher.on("attribute:drupalElementStyle",i),e.data.downcastDispatcher.on("attribute:drupalElementStyle",i);[...new Set(this.normalizedStyles.map((e=>e.modelElements)).flat())].forEach((e=>{t.extend(e,{allowAttributes:"drupalElementStyle"})})),e.data.upcastDispatcher.on("element",n,{priority:"low"})}static get pluginName(){return"DrupalElementStyleEditing"}}const $=e=>e,z=(e,t)=>(e?`${e}: `:"")+t;function Z(e){return`drupalElementStyle:${e}`}class G extends e.Plugin{static get requires(){return[K]}init(){const e=this.editor.plugins,t=this.editor.config.get("drupalMedia.toolbar")||[],i=Object.values(e.get("DrupalElementStyleEditing").normalizedStyles);i.forEach((e=>{this._createButton(e)}));t.filter(o).forEach((e=>{this._createDropdown(e,i)}))}_createDropdown(e,t){const i=this.editor.ui.componentFactory;i.add(e.name,(a=>{let n;const{defaultItem:r,items:l,title:o}=e,s=l.filter((e=>t.find((({name:t})=>Z(t)===e)))).map((e=>{const t=i.create(e);return e===r&&(n=t),t}));l.length!==s.length&&U.warnInvalidStyle({dropdown:e});const d=(0,u.createDropdown)(a,u.SplitButtonView),c=d.buttonView;return(0,u.addToolbarToDropdown)(d,s),c.set({label:z(o,n.label),class:null,tooltip:!0}),c.bind("icon").toMany(s,"isOn",((...e)=>{const t=e.findIndex($);return t<0?n.icon:s[t].icon})),c.bind("label").toMany(s,"isOn",((...e)=>{const t=e.findIndex($);return z(o,t<0?n.label:s[t].label)})),c.bind("isOn").toMany(s,"isOn",((...e)=>e.some($))),c.bind("class").toMany(s,"isOn",((...e)=>e.some($)?"ck-splitbutton_flatten":null)),c.on("execute",(()=>{s.some((({isOn:e})=>e))?d.isOpen=!d.isOpen:n.fire("execute")})),d.bind("isEnabled").toMany(s,"isEnabled",((...e)=>e.some($))),d}))}_createButton(e){const t=e.name;this.editor.ui.componentFactory.add(Z(t),(i=>{const a=this.editor.commands.get("drupalElementStyle"),n=new u.ButtonView(i);return n.set({label:e.title,icon:e.icon,tooltip:!0,isToggleable:!0}),n.bind("isEnabled").to(a,"isEnabled"),n.bind("isOn").to(a,"value",(e=>e===t)),n.on("execute",this._executeCommand.bind(this,t)),n}))}_executeCommand(e){this.editor.execute("drupalElementStyle",{value:e}),this.editor.editing.view.focus()}static get pluginName(){return"DrupalElementStyleUi"}}class J extends e.Plugin{static get requires(){return[K,G]}static get pluginName(){return"DrupalElementStyle"}}const X={DrupalMedia:_,MediaImageTextAlternative:v,MediaImageTextAlternativeEditing:f,MediaImageTextAlternativeUi:E,DrupalLinkMedia:V,DrupalElementStyle:J}})(),a=a.default})()}));